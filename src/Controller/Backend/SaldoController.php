<?php

namespace App\Controller\Backend;


use App\Entity\TnAgencia;
use App\Entity\TnAgente;
use App\Entity\TnSaldoAgencia;
use App\Form\Type\SaldoAgenciaType;
use App\Form\Type\SaldoAgenteType;
use App\Manager\ConfigurationManager;
use App\Repository\NmGrupoPagoRepository;
use App\Repository\TnAgenciaRepository;
use App\Repository\TnAgenteRepository;
use App\Repository\TnSaldoAgenciaRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/backend/saldo")
 */
class SaldoController extends AbstractController
{
    /**
     * @Route("/agencias", name="tn_saldo_agencias_index", methods={"GET"})
     */
    public function indexSaldoAgencias(TnAgenciaRepository $tnAgenciaRepository): Response
    {
        return $this->render('backend/saldo/saldo_agencias.html.twig', [
            'tn_agencias' => $tnAgenciaRepository->findBy(['enabled' => true]),
        ]);
    }

    /**
     * @Route("/agentes", name="tn_saldo_agentes_index", methods={"GET"})
     */
    public function indexSaldoAgentes(TnAgenteRepository $tnAgenteRepository): Response
    {
        $checker = $this->get('security.authorization_checker');
        $tnAgentes = [];
        $tnUser = $this->getUser();
        if ($checker->isGranted("ROLE_SUPER_ADMIN")) {
            $tnAgentes = $tnAgenteRepository->findBy(['enabled' => true]);
        } elseif ($checker->isGranted("ROLE_AGENCIA")) {
            $tnAgencia = $tnUser->getAgencia();
            $tnAgentes = $tnAgencia->getAgentes();
        }

        return $this->render('backend/saldo/saldo_agentes.html.twig', [
            'tn_agentes' => $tnAgentes,
        ]);
    }


    /**
     * @Route("/agencia/{token}/editar", name="tn_saldo_agencias_editar_saldo")
     * @ParamConverter("tnAgencia", options={"mapping": {"token": "token"}})
     */
    public function editarSaldos(Request $request, TnAgencia $tnAgencia, ConfigurationManager $configurationManager): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $form = $this->createForm(SaldoAgenciaType::class, $tnAgencia, [
            'action' => $this->generateUrl('tn_saldo_agencias_editar_saldo', ['token' => $tnAgencia->getToken()])
        ]);
        $saldos = [];
        foreach ($tnAgencia->getSaldoMonedas() as $saldoMoneda) {
            $saldos[$saldoMoneda->getGrupoPago()->getMoneda()->getSimbolo()] = $saldoMoneda->getSaldo();
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($form->getData()->getSaldoMonedas() as $saldoMoneda) {
                $moneda = $saldoMoneda->getGrupoPago()->getMoneda()->getSimbolo();
                //Busco el saldo que tengo en configuracion
                $saldo = $configurationManager->get('saldo_' . strtolower($moneda));
                //Pongo lo anterior
                $saldo += $saldos[$moneda];
                //Quito el saldo nuevo
                if ($saldo >= $saldoMoneda->getSaldo()) {
                    $saldo -= $saldoMoneda->getSaldo();

                    $configurationManager->set('saldo_' . strtolower($moneda), $saldo);//Lo registro

                    $entityManager->persist($saldoMoneda);

                    $entityManager->flush();

                } else {
                    $this->get('session')->getFlashBag()->add('error', "El monto de la moneda " . $moneda . " sobrepasa el límite de saldo, revise.");
                    return $this->redirectToRoute('tn_saldo_agencias_editar_saldo', ['token' => $tnAgencia->getToken()]);
                }
            }

            $this->get('session')->getFlashBag()->add('success', 'Saldos Agencia ' . $tnAgencia->getNombre() . ' actualizados correctamente.');
            return $this->redirectToRoute('tn_saldo_agencias_index');
        }

        return $this->render('backend/saldo/editar_saldo.html.twig', [
            'tnAgencia' => $tnAgencia,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/agente/{token}/editar", name="tn_saldo_agente_editar_saldo")
     * @ParamConverter("tnAgente", options={"mapping": {"token": "token"}})
     */
    public function editarSaldosAgente(Request $request, TnAgente $tnAgente, ConfigurationManager $configurationManager, TnSaldoAgenciaRepository $saldoAgenciaRepository, NmGrupoPagoRepository $grupoPagoRepository): Response
    {
        $checker = $this->get('security.authorization_checker');
        $entityManager = $this->getDoctrine()->getManager();
        $form = $this->createForm(SaldoAgenteType::class, $tnAgente, [
            'action' => $this->generateUrl('tn_saldo_agente_editar_saldo', ['token' => $tnAgente->getToken()])
        ]);
        $saldos = [];
        foreach ($tnAgente->getSaldoMonedas() as $saldoMoneda) {
            $saldos[$saldoMoneda->getGrupoPagoAgente()->getMoneda()->getSimbolo()] = $saldoMoneda->getSaldo();
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($checker->isGranted("ROLE_SUPER_ADMIN")) {
                $tnAgencia = $tnAgente->getAgencia();
            } else {
                $tnAgencia = $this->getUser()->getAgencia();
            }

            if(!is_null($tnAgencia)){
                foreach ($form->getData()->getSaldoMonedas() as $saldoMoneda) {
                    $moneda = $saldoMoneda->getGrupoPagoAgente()->getMoneda()->getSimbolo();
                    $grupoAgenciaMoneda = $grupoPagoRepository->grupoPagoAgencia($tnAgencia, $saldoMoneda->getGrupoPagoAgente()->getMoneda());
                    $saldoAgencia = $saldoAgenciaRepository->findOneBy(
                        ['agencia' => $tnAgencia, 'grupoPago' => $grupoAgenciaMoneda]
                    );
                    if (!is_null($saldoAgencia)) {
                        $saldo = $saldoAgencia->getSaldo();
                        //Pongo lo anterior
                        $saldo += $saldos[$moneda];
                        //Quito el saldo nuevo
                        if ($saldo >= $saldoMoneda->getSaldo()) {
                            $saldo -= $saldoMoneda->getSaldo();

                            $saldoAgencia->setSaldo($saldo);
                            $entityManager->persist($saldoAgencia);

                            $entityManager->persist($saldoMoneda);

                            $entityManager->flush();

                        } else {
                            $this->get('session')->getFlashBag()->add('error', "El monto de la moneda " . $moneda . " sobrepasa el límite de saldo, revise.");
                            return $this->redirectToRoute('tn_saldo_agente_editar_saldo', ['token' => $tnAgente->getToken()]);
                        }
                    } else {
                        $this->get('session')->getFlashBag()->add('error', "Error al encontrar el saldo de la agencia en la moneda " . $moneda . ", no se puedo editar los saldos.");
                        return $this->redirectToRoute('tn_saldo_agente_editar_saldo', ['token' => $tnAgente->getToken()]);
                    }
                }
            }else{
                foreach ($form->getData()->getSaldoMonedas() as $saldoMoneda) {
                    $moneda = $saldoMoneda->getGrupoPagoAgente()->getMoneda()->getSimbolo();
                    //Busco el saldo que tengo en configuracion
                    $saldo = $configurationManager->get('saldo_' . strtolower($moneda));
                    //Pongo lo anterior
                    $saldo += $saldos[$moneda];
                    //Quito el saldo nuevo
                    if ($saldo >= $saldoMoneda->getSaldo()) {
                        $saldo -= $saldoMoneda->getSaldo();

                        $configurationManager->set('saldo_' . strtolower($moneda), $saldo);//Lo registro

                        $entityManager->persist($saldoMoneda);

                        $entityManager->flush();

                    } else {
                        $this->get('session')->getFlashBag()->add('error', "El monto de la moneda " . $moneda . " sobrepasa el límite de saldo, revise.");
                        return $this->redirectToRoute('tn_saldo_agente_editar_saldo', ['token' => $tnAgente->getToken()]);
                    }
                }
            }

            $this->get('session')->getFlashBag()->add('success', 'Saldos Agente ' . $tnAgente->getNombre() . ' actualizados correctamente.');
            return $this->redirectToRoute('tn_saldo_agentes_index');
        }

        return $this->render('backend/saldo/editar_saldo_agente.html.twig', [
            'tnAgente' => $tnAgente,
            'form' => $form->createView()
        ]);
    }
}
