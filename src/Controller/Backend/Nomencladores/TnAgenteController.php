<?php

namespace App\Controller\Backend\Nomencladores;

use App\Entity\NmGrupoPagoTransfAgente;
use App\Entity\TnAgencia;
use App\Entity\TnAgente;
use App\Entity\TnOperacionAgente;
use App\Entity\TnOperacionAgenteTransf;
use App\Entity\TnSaldoAgente;
use App\Entity\TnUser;
use App\Form\TnAgenteType;
use App\Repository\NmGrupoPagoAgenteRepository;
use App\Repository\NmGrupoPagoTransfAgenteRepository;
use App\Repository\TnAgenteRepository;
use App\Repository\TnOperacionAgenciaTransfRepository;
use App\Repository\TnOperacionAgenteRepository;
use App\Repository\TnOperacionAgenteTransfRepository;
use App\Repository\TnSaldoAgenteRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("backend/nom/tn/agente")
 */
class TnAgenteController extends AbstractController
{
    /**
     * @Route("/", name="tn_agente_index", methods={"GET"})
     */
    public function index(TnAgenteRepository $tnAgenteRepository, AuthorizationCheckerInterface $authorizationChecker): Response
    {
        $tnUser = $this->getUser();
        if ($tnUser instanceof TnUser) {
            if ($authorizationChecker->isGranted("ROLE_AGENCIA")) {
                $query = $tnAgenteRepository->createQueryBuilder('f')
                    ->where('f.agencia = :ag')
                    ->setParameter('ag', $tnUser->getAgencia())
                    ->orderBy('f.created', "DESC")
                    ->getQuery();
            } else {
                $query = $tnAgenteRepository->createQueryBuilder('f')->orderBy('f.created', "DESC")->getQuery();
            }
        }

        return $this->render('backend/tn_agente/index.html.twig', [
            'tn_agentes' => $query->getResult(),
        ]);
    }

    /**
     * @Route("/new", name="tn_agente_new", methods={"GET","POST"})
     * @Route("/new/{id}/agencia", name="tn_agente_new_agencia", methods={"GET","POST"})
     */
    public function new(Request $request, AuthorizationCheckerInterface $authorizationChecker, TnAgencia $tnAgencia = null): Response
    {
        $tnAgente = new TnAgente();
        if ($tnAgencia != null) {
            $tnAgente->setAgencia($tnAgencia);
        }
        $form = $this->createForm(TnAgenteType::class, $tnAgente);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $tnUser = $this->getUser();
            if ($tnUser instanceof TnUser) {
                if ($authorizationChecker->isGranted("ROLE_AGENCIA")) {
                    $tnAgente->setAgencia($tnUser->getAgencia());
                }
            }
            $tnAgente->setToken((sha1(uniqid())));
            $entityManager->persist($tnAgente);

            //Creando los saldos agencia según los grupos de pago
            foreach ($tnAgente->getGruposPago() as $grupoPago) {//Primero busco los nuevos añadidos.
                $tnSaldoAgente = new TnSaldoAgente();
                $tnSaldoAgente->setAgente($tnAgente);
                $tnSaldoAgente->setGrupoPagoAgente($grupoPago);
                $tnSaldoAgente->setSaldo(0);
                $entityManager->persist($tnSaldoAgente);

                //Creando el monto de los porcientos para operar.
                $tnOperacioAgente = new TnOperacionAgente();
                $tnOperacioAgente->setAgente($tnAgente);
                $tnOperacioAgente->setGrupoPagoAgente($grupoPago);
                $tnOperacioAgente->setPorcentaje(0);
                $entityManager->persist($tnOperacioAgente);
            }

            //Creando los saldos agencia según los grupos de pago para las transferencias
            foreach ($tnAgente->getGruposPagoTransferencias() as $grupoPago) {//Primero busco los nuevos añadidos.
                //Creando el monto de los porcientos para operar.
                $tnOperacioAgenteTransf = new TnOperacionAgenteTransf();
                $tnOperacioAgenteTransf->setAgente($tnAgente);
                $tnOperacioAgenteTransf->setGrupoPago($grupoPago);
                $tnOperacioAgenteTransf->setPorcentaje(0);
                $entityManager->persist($tnOperacioAgenteTransf);
            }

            $entityManager->flush();

            return $this->redirectToRoute('tn_agente_index');
        }

        return $this->render('backend/tn_agente/new.html.twig', [
            'tn_agente' => $tnAgente,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{token}/edit", name="tn_agente_edit", methods={"GET","POST"})
     * @ParamConverter("tnAgente", options={"mapping": {"token": "token"}})
     */
    public function edit(Request $request, TnAgente $tnAgente, ValidatorInterface $validator, NmGrupoPagoTransfAgenteRepository $nmGrupoPagoTransfAgenteRepository, NmGrupoPagoAgenteRepository $grupoPagoAgenteRepository, TnSaldoAgenteRepository $saldoAgenteRepository, TnOperacionAgenteRepository $operacionAgenteRepository, TnOperacionAgenteTransfRepository $operacionAgenteTransfRepository): Response
    {
        $em = $this->getDoctrine()->getManager();
        $grupos = [];
        foreach ($tnAgente->getGruposPago() as $grupoPago) {
            $grupos[] = $grupoPago->getId();
        }

        $gruposTransf = [];
        foreach ($tnAgente->getGruposPagoTransferencias() as $grupoPago) {
            $gruposTransf[] = $grupoPago->getId();
        }

        $form = $this->createForm(TnAgenteType::class, $tnAgente);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($tnAgente->getGruposPago() as $grupoPago) {//Primero busco los nuevos añadidos.
                $encontrado = false;
                foreach ($grupos as $grupo) {
                    if ($grupo == $grupoPago->getId()) {
                        $encontrado = true;
                        break;
                    }
                }
                if (!$encontrado) {//Si es nuevo lo creo como para poner saldo
                    $tnSaldoAgente = new TnSaldoAgente();
                    $tnSaldoAgente->setAgente($tnAgente);
                    $tnSaldoAgente->setGrupoPagoAgente($grupoPago);
                    $tnSaldoAgente->setSaldo(0);
                    $em->persist($tnSaldoAgente);

                    //Creando el monto de los porcientos para operar.
                    $tnOperacioAgente = new TnOperacionAgente();
                    $tnOperacioAgente->setAgente($tnAgente);
                    $tnOperacioAgente->setGrupoPagoAgente($grupoPago);
                    $tnOperacioAgente->setPorcentaje(0);
                    $em->persist($tnOperacioAgente);
                }
            }
            //Buscar los grupos de pago que tiene y revisar eliminados
            foreach ($grupos as $grupo) {
                $encontrado = false;
                foreach ($tnAgente->getGruposPago() as $grupoPago) {//Primero busco los nuevos añadidos.
                    if ($grupo == $grupoPago->getId()) {
                        $encontrado = true;
                        break;
                    }
                }
                if (!$encontrado) {//Si es nuevo lo creo como para poner saldo
                    $grupoAnterior = $grupoPagoAgenteRepository->find($grupo);
                    $saldoAgente = $saldoAgenteRepository->findOneBy(['agente' => $tnAgente, 'grupoPagoAgente' => $grupoAnterior]);
                    if (!is_null($saldoAgente)) {
                        $em->remove($saldoAgente);
                    }
                    $operaAgente = $operacionAgenteRepository->findOneBy(['agente' => $tnAgente, 'grupoPagoAgente' => $grupoAnterior]);
                    if (!is_null($operaAgente)) {
                        $em->remove($operaAgente);
                    }
                }
            }

            //Para los grupos de pago de las transferencias
            foreach ($tnAgente->getGruposPagoTransferencias() as $gruposPagoT) {//Primero busco los nuevos añadidos.
                $encontrado = false;
                foreach ($gruposTransf as $grupo) {
                    if ($grupo == $gruposPagoT->getId()) {
                        $encontrado = true;
                        break;
                    }
                }
                if (!$encontrado) {//Si es nuevo lo creo como para poner saldo

                    //Creando el monto de los porcientos para operar.
                    $tnOperacioAgenteTranfs = new TnOperacionAgenteTransf();
                    $tnOperacioAgenteTranfs->setAgente($tnAgente);
                    $tnOperacioAgenteTranfs->setGrupoPago($gruposPagoT);
                    $tnOperacioAgenteTranfs->setPorcentaje(0);
                    $em->persist($tnOperacioAgenteTranfs);
                }
            }

            //Buscar los grupos de pago que tiene y revisar eliminados
            foreach ($gruposTransf as $grupo) {
                $encontrado = false;
                foreach ($tnAgente->getGruposPagoTransferencias() as $grupoPago) {//Primero busco los nuevos añadidos.
                    if ($grupo == $grupoPago->getId()) {
                        $encontrado = true;
                        break;
                    }
                }
                if (!$encontrado) {//Si es nuevo lo creo como para poner saldo
                    $grupoAnterior = $nmGrupoPagoTransfAgenteRepository->find($grupo);
                    $operaAgente = $operacionAgenteTransfRepository->findOneBy(['agente' => $tnAgente, 'grupoPago' => $grupoAnterior]);
                    if (!is_null($operaAgente)) {
                        $em->remove($operaAgente);
                    }
                }
            }

            $em->flush();

            return $this->redirectToRoute('tn_agente_index');
        }

        return $this->render('backend/tn_agente/edit.html.twig', [
            'agente' => $tnAgente,
            'form' => $form->createView(),
        ]);
    }
}
