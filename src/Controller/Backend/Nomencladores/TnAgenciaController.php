<?php

namespace App\Controller\Backend\Nomencladores;

use App\Entity\NmGrupoPagoTransf;
use App\Entity\TnAgencia;
use App\Entity\TnOperacionAgencia;
use App\Entity\TnOperacionAgenciaTransf;
use App\Entity\TnSaldoAgencia;
use App\Form\TnAgenciaType;
use App\Repository\NmGrupoPagoRepository;
use App\Repository\NmGrupoPagoTransfRepository;
use App\Repository\TnAgenciaRepository;
use App\Repository\TnOperacionAgenciaRepository;
use App\Repository\TnOperacionAgenciaTransfRepository;
use App\Repository\TnSaldoAgenciaRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/super/nom/tn/agencia")
 */
class TnAgenciaController extends AbstractController
{
    /**
     * @Route("/", name="tn_agencia_index", methods={"GET"})
     */
    public function index(TnAgenciaRepository $tnAgenciaRepository): Response
    {
        return $this->render('backend/tn_agencia/index.html.twig', [
            'tn_agencias' => $tnAgenciaRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="tn_agencia_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $tnAgencium = new TnAgencia();
        $form = $this->createForm(TnAgenciaType::class, $tnAgencium);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $tnAgencium->setToken((sha1(uniqid())));
            $tnAgencium->setUnlimited(false);
            $entityManager->persist($tnAgencium);

            //Creando los saldos agencia según los grupos de pago
            foreach ($tnAgencium->getGruposPago() as $grupoPago) {//Primero busco los nuevos añadidos.
                $tnSaldoAgencia = new TnSaldoAgencia();
                $tnSaldoAgencia->setAgencia($tnAgencium);
                $tnSaldoAgencia->setGrupoPago($grupoPago);
                $tnSaldoAgencia->setSaldo(0);
                $entityManager->persist($tnSaldoAgencia);

                //Creando el monto de los porcientos para operar.
                $tnOperacioAgencia = new TnOperacionAgencia();
                $tnOperacioAgencia->setAgencia($tnAgencium);
                $tnOperacioAgencia->setGrupoPago($grupoPago);
                $tnOperacioAgencia->setPorcentaje(0);
                $entityManager->persist($tnOperacioAgencia);
            }

            //Creando los saldos agencia según los grupos de pago para las transferencias
            foreach ($tnAgencium->getGruposPagoTransferencias() as $grupoPago) {//Primero busco los nuevos añadidos.
                //Creando el monto de los porcientos para operar.
                $tnOperacioAgencia = new TnOperacionAgenciaTransf();
                $tnOperacioAgencia->setAgencia($tnAgencium);
                $tnOperacioAgencia->setGrupoPago($grupoPago);
                $tnOperacioAgencia->setPorcentaje(0);
                $entityManager->persist($tnOperacioAgencia);
            }

            $entityManager->flush();

            return $this->redirectToRoute('tn_agencia_index');
        }

        return $this->render('backend/tn_agencia/new.html.twig', ['tn_agencium' => $tnAgencium,
            'form' => $form->createView(),]);
    }

    /**
     * @Route("/{token}/edit", name="tn_agencia_edit", methods={"GET","POST"})
     * @ParamConverter("tnAgencium", options={"mapping": {"token": "token"}})
     */
    public function edit(Request $request, TnAgencia $tnAgencium, TnSaldoAgenciaRepository $saldoAgenciaRepository, NmGrupoPagoRepository $grupoPagoRepository, NmGrupoPagoTransfRepository $grupoPagoTransfRepository, TnOperacionAgenciaRepository $operacionAgenciaRepository, TnOperacionAgenciaTransfRepository $operacionAgenciaTransfRepository): Response
    {
        $em = $this->getDoctrine()->getManager();
        $grupos = [];
        foreach ($tnAgencium->getGruposPago() as $grupoPago) {
            $grupos[] = $grupoPago->getId();
        }

        $gruposTransf = [];
        foreach ($tnAgencium->getGruposPagoTransferencias() as $grupoPago) {
            $gruposTransf[] = $grupoPago->getId();
        }

        $form = $this->createForm(TnAgenciaType::class, $tnAgencium);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($tnAgencium->getGruposPago() as $grupoPago) {//Primero busco los nuevos añadidos.
                $encontrado = false;
                foreach ($grupos as $grupo) {
                    if ($grupo == $grupoPago->getId()) {
                        $encontrado = true;
                        break;
                    }
                }
                if (!$encontrado) {//Si es nuevo lo creo como para poner saldo
                    $tnSaldoAgencia = new TnSaldoAgencia();
                    $tnSaldoAgencia->setAgencia($tnAgencium);
                    $tnSaldoAgencia->setGrupoPago($grupoPago);
                    $tnSaldoAgencia->setSaldo(0);
                    $em->persist($tnSaldoAgencia);

                    //Creando el monto de los porcientos para operar.
                    $tnOperacioAgencia = new TnOperacionAgencia();
                    $tnOperacioAgencia->setAgencia($tnAgencium);
                    $tnOperacioAgencia->setGrupoPago($grupoPago);
                    $tnOperacioAgencia->setPorcentaje(0);
                    $em->persist($tnOperacioAgencia);
                }
            }
            //Buscar los grupos de pago que tiene y revisar eliminados
            foreach ($grupos as $grupo) {
                $encontrado = false;
                foreach ($tnAgencium->getGruposPago() as $grupoPago) {//Primero busco los nuevos añadidos.
                    if ($grupo == $grupoPago->getId()) {
                        $encontrado = true;
                        break;
                    }
                }
                if (!$encontrado) {//Si es nuevo lo creo como para poner saldo
                    $grupoAnterior = $grupoPagoRepository->find($grupo);
                    $saldoAgencia = $saldoAgenciaRepository->findOneBy(['agencia' => $tnAgencium, 'grupoPago' => $grupoAnterior]);
                    if (!is_null($saldoAgencia)) {
                        $em->remove($saldoAgencia);
                    }
                    $operaAgencia = $operacionAgenciaRepository->findOneBy(['agencia' => $tnAgencium, 'grupoPago' => $grupoAnterior]);
                    if (!is_null($operaAgencia)) {
                        $em->remove($operaAgencia);
                    }
                }
            }
            //Para los grupos de pago de las transferencias
            foreach ($tnAgencium->getGruposPagoTransferencias() as $grupoPago) {//Primero busco los nuevos añadidos.
                $encontrado = false;
                foreach ($gruposTransf as $grupo) {
                    if ($grupo == $grupoPago->getId()) {
                        $encontrado = true;
                        break;
                    }
                }
                if (!$encontrado) {//Si es nuevo lo creo como para poner saldo

                    //Creando el monto de los porcientos para operar.
                    $tnOperacioAgencia = new TnOperacionAgenciaTransf();
                    $tnOperacioAgencia->setAgencia($tnAgencium);
                    $tnOperacioAgencia->setGrupoPago($grupoPago);
                    $tnOperacioAgencia->setPorcentaje(0);
                    $em->persist($tnOperacioAgencia);
                }
            }

            //Buscar los grupos de pago que tiene y revisar eliminados
            foreach ($gruposTransf as $grupo) {
                $encontrado = false;
                foreach ($tnAgencium->getGruposPagoTransferencias() as $grupoPago) {//Primero busco los nuevos añadidos.
                    if ($grupo == $grupoPago->getId()) {
                        $encontrado = true;
                        break;
                    }
                }
                if (!$encontrado) {//Si es nuevo lo creo como para poner saldo
                    $grupoAnterior = $grupoPagoTransfRepository->find($grupo);
                    $operaAgencia = $operacionAgenciaTransfRepository->findOneBy(['agencia' => $tnAgencium, 'grupoPago' => $grupoAnterior]);
                    if (!is_null($operaAgencia)) {
                        $em->remove($operaAgencia);
                    }
                }
            }

            $em->flush();

            return $this->redirectToRoute('tn_agencia_index');
        }

        return $this->render('backend/tn_agencia/edit.html.twig', [
            'agencia' => $tnAgencium,
            'form' => $form->createView(),
        ]);
    }
}
