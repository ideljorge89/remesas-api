<?php

namespace App\Controller\Backend;


use App\Entity\NmMoneda;
use App\Entity\NmProvincia;
use App\Form\Type\PorcentajeAgenciaTransferenciaType;
use App\Form\Type\PorcentajeAgenciaType;
use App\Form\Type\PorcentajeAgenteTransferenciaType;
use App\Form\Type\PorcentajeAgenteType;
use App\Repository\NmProvinciaRepository;
use App\Repository\TnDistribuidorRepository;
use App\Repository\TnHistorialDistribuidorRepository;
use App\Repository\TnRemesaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/backend")
 */
class BackendController extends AbstractController
{
    /**
     * @Route("/", name="homepage_backend")
     */
    public function indexAction(Request $request, TnRemesaRepository $remesaRepository, TnDistribuidorRepository $distribuidorRepository, TnHistorialDistribuidorRepository $historialRepository)
    {
        if (!$this->isGranted('ROLE_DISTRIBUIDOR')) {
            $tnUser = $this->getUser();
            $remesasToday = [];
            $checker = $this->container->get('security.authorization_checker');
            if ($checker->isGranted("ROLE_SUPER_ADMIN")) {
                $remesasTotal = $remesaRepository->totalRemesas(null, NmMoneda::CURRENCY_CUC);
                $remesasTotalCUP = $remesaRepository->totalRemesas(null, NmMoneda::CURRENCY_CUP);
                $remesasTotalUSD = $remesaRepository->totalRemesas(null, NmMoneda::CURRENCY_USD);
                $remesasTotalEUR = $remesaRepository->totalRemesas(null, NmMoneda::CURRENCY_EUR);
                $remesasToday = $remesaRepository->totalRemesasToday(null, NmMoneda::CURRENCY_CUC);
                $remesasTodayCUP = $remesaRepository->totalRemesasToday(null, NmMoneda::CURRENCY_CUP);
                $remesasTodayUSD = $remesaRepository->totalRemesasToday(null, NmMoneda::CURRENCY_USD);
                $remesasTodayEUR = $remesaRepository->totalRemesasToday(null, NmMoneda::CURRENCY_EUR);
            } elseif ($checker->isGranted("ROLE_AGENCIA")) {
                if ($tnUser->getAgencia() != null) {
                    $remesasTotal = $remesaRepository->totalRemesas($tnUser->getAgencia(), NmMoneda::CURRENCY_CUC);
                    $remesasTotalCUP = $remesaRepository->totalRemesas($tnUser->getAgencia(), NmMoneda::CURRENCY_CUP);
                    $remesasTotalUSD = $remesaRepository->totalRemesas($tnUser->getAgencia(), NmMoneda::CURRENCY_USD);
                    $remesasTotalEUR = $remesaRepository->totalRemesas($tnUser->getAgencia(), NmMoneda::CURRENCY_EUR);
                    $remesasToday = $remesaRepository->totalRemesasToday($tnUser->getAgencia(), NmMoneda::CURRENCY_CUC);
                    $remesasTodayCUP = $remesaRepository->totalRemesasToday($tnUser->getAgencia(), NmMoneda::CURRENCY_CUP);
                    $remesasTodayUSD = $remesaRepository->totalRemesasToday($tnUser->getAgencia(), NmMoneda::CURRENCY_USD);
                    $remesasTodayEUR = $remesaRepository->totalRemesasToday($tnUser->getAgencia(), NmMoneda::CURRENCY_EUR);
                } else {
                    $remesasTotal = $remesaRepository->totalRemesas(null, NmMoneda::CURRENCY_CUC);
                    $remesasTotalCUP = $remesaRepository->totalRemesas(null, NmMoneda::CURRENCY_CUP);
                    $remesasTotalUSD = $remesaRepository->totalRemesas(null, NmMoneda::CURRENCY_USD);
                    $remesasTotalEUR = $remesaRepository->totalRemesas(null, NmMoneda::CURRENCY_EUR);
                    $remesasToday = $remesaRepository->totalRemesasToday(null, NmMoneda::CURRENCY_CUC);
                    $remesasTodayCUP = $remesaRepository->totalRemesasToday(null, NmMoneda::CURRENCY_CUP);
                    $remesasTodayUSD = $remesaRepository->totalRemesasToday(null, NmMoneda::CURRENCY_USD);
                    $remesasTodayEUR = $remesaRepository->totalRemesasToday(null, NmMoneda::CURRENCY_EUR);
                }
            } elseif ($checker->isGranted("ROLE_AGENTE")) {
                if ($tnUser->getAgente() != null) {
                    $remesasTotal = $remesaRepository->totalRemesas($tnUser->getAgente(), NmMoneda::CURRENCY_CUC);
                    $remesasTotalCUP = $remesaRepository->totalRemesas($tnUser->getAgente(), NmMoneda::CURRENCY_CUP);
                    $remesasTotalUSD = $remesaRepository->totalRemesas($tnUser->getAgente(), NmMoneda::CURRENCY_USD);
                    $remesasToday = $remesaRepository->totalRemesasToday($tnUser->getAgente(), NmMoneda::CURRENCY_CUC);
                    $remesasTodayCUP = $remesaRepository->totalRemesasToday($tnUser->getAgente(), NmMoneda::CURRENCY_CUP);
                    $remesasTodayUSD = $remesaRepository->totalRemesasToday($tnUser->getAgente(), NmMoneda::CURRENCY_USD);
                } else {
                    $remesasTotal = $remesaRepository->totalRemesas(null, NmMoneda::CURRENCY_CUC);
                    $remesasTotalCUP = $remesaRepository->totalRemesas(null, NmMoneda::CURRENCY_CUP);
                    $remesasTotalUSD = $remesaRepository->totalRemesas(null, NmMoneda::CURRENCY_USD);
                    $remesasTotalEUR = $remesaRepository->totalRemesas(null, NmMoneda::CURRENCY_EUR);
                    $remesasToday = $remesaRepository->totalRemesasToday(null, NmMoneda::CURRENCY_CUC);
                    $remesasTodayCUP = $remesaRepository->totalRemesasToday(null, NmMoneda::CURRENCY_CUP);
                    $remesasTodayEUR = $remesaRepository->totalRemesasToday(null, NmMoneda::CURRENCY_EUR);
                }
            }

            return $this->render('backend/index.html.twig', array(
                'totalRemesas' => $remesasTotal[0][1],
                'totalRemesasCUP' => $remesasTotalCUP[0][1],
                'totalRemesasUSD' => $remesasTotalUSD[0][1],
                'totalRemesasEUR' => $remesasTotalEUR[0][1],
                'montoTotalRemesas' => $remesasTotal[0][2],
                'montoTotalRemesasCUP' => $remesasTotalCUP[0][2],
                'montoTotalRemesasUSD' => $remesasTotalUSD[0][2],
                'montoTotalRemesasEUR' => $remesasTotalEUR[0][2],
                'totalRemesasHoy' => $remesasToday[0][1],
                'totalRemesasHoyCUP' => $remesasTodayCUP[0][1],
                'totalRemesasHoyUSD' => $remesasTodayUSD[0][1],
                'totalRemesasHoyEUR' => $remesasTodayEUR[0][1],
                'montoRemesasHoy' => $remesasToday[0][2],
                'montoRemesasHoyCUP' => $remesasTodayCUP[0][2],
                'montoRemesasHoyUSD' => $remesasTodayUSD[0][2],
                'montoRemesasHoyEUR' => $remesasTodayEUR[0][2],
            ));
        } else {
            throw new AccessDeniedException("Usted no tiene acceso al servicio solicitado");
        }
    }


    /**
     * @Route("/backend/provincias_ajax", name="homepage_backend_provincias_ajax")
     */
    public function centrosAction(Request $request, TnRemesaRepository $remesaRepository, NmProvinciaRepository $repository)
    {
        $tnUser = $this->getUser();
        $checker = $this->container->get('security.authorization_checker');
        if ($checker->isGranted("ROLE_SUPER_ADMIN")) {
            $values = $repository->getLocalizationsProvinciasRemesas();
        } elseif ($checker->isGranted("ROLE_AGENCIA")) {
            if ($tnUser->getAgencia() != null) {
                $values = $repository->getLocalizationsProvinciasRemesas($tnUser->getAgencia());
            } else {
                $values = $repository->getLocalizationsProvinciasRemesas();
            }
        } elseif ($checker->isGranted("ROLE_AGENTE")) {
            if ($tnUser->getAgente() != null) {
                $values = $repository->getLocalizationsProvinciasRemesas($tnUser->getAgente());
            } else {
                $values = $repository->getLocalizationsProvinciasRemesas();
            }
        }

        $d = array();
        foreach ($values as $val) {
            $val['url'] = $this->generateUrl('homepage_backend_pronvicia_ajax_info', array(
                'id' => $val['id']
            ));
            $d[] = $val;
        }


        return new JsonResponse($d);
    }

    /**
     * @Route("/backend/provincias_ajax/{id}", name="homepage_backend_pronvicia_ajax_info")
     */
    public function provinciasInfoAction(Request $request, TnRemesaRepository $remesaRepository, NmProvincia $provincia)
    {
        $tnUser = $this->getUser();
        $checker = $this->container->get('security.authorization_checker');
        if ($checker->isGranted("ROLE_SUPER_ADMIN")) {
            $remesas = $remesaRepository->totalRemesasProvincia($provincia, null, NmMoneda::CURRENCY_CUC);
            $remesasCUP = $remesaRepository->totalRemesasProvincia($provincia, null, NmMoneda::CURRENCY_CUP);
        } elseif ($checker->isGranted("ROLE_AGENCIA")) {
            if ($tnUser->getAgencia() != null) {
                $remesas = $remesaRepository->totalRemesasProvincia($provincia, $tnUser->getAgencia(), NmMoneda::CURRENCY_CUC);
                $remesasCUP = $remesaRepository->totalRemesasProvincia($provincia, $tnUser->getAgencia(), NmMoneda::CURRENCY_CUP);
            } else {
                $remesas = $remesaRepository->totalRemesasProvincia($provincia, null, NmMoneda::CURRENCY_CUC);
                $remesasCUP = $remesaRepository->totalRemesasProvincia($provincia, null, NmMoneda::CURRENCY_CUP);
            }
        } elseif ($checker->isGranted("ROLE_AGENTE")) {
            if ($tnUser->getAgente() != null) {
                $remesas = $remesaRepository->totalRemesasProvincia($provincia, $tnUser->getAgente(), NmMoneda::CURRENCY_CUC);
                $remesasCUP = $remesaRepository->totalRemesasProvincia($provincia, $tnUser->getAgente(), NmMoneda::CURRENCY_CUP);
            } else {
                $remesas = $remesaRepository->totalRemesasProvincia($provincia, null, NmMoneda::CURRENCY_CUC);
                $remesasCUP = $remesaRepository->totalRemesasProvincia($provincia, null, NmMoneda::CURRENCY_CUP);
            }
        }

        return $this->render('backend/nm_provincia/info.html.twig', [
            'provincia' => $provincia,
            'totalRemesas' => $remesas[0][1],
            'totalRemesasCUP' => $remesasCUP[0][1],
            'montoRemesas' => $remesas[0][2],
            'montoRemesasCUP' => $remesasCUP[0][2],
        ]);
    }

    /**
     * @Route("/backend/agencia/porcentajes", name="user_backend_agencia_editar_porcentajes")
     */
    public function usuarioAgenciaEditarPorcentajesAction(Request $request)
    {
        $tnAgencia = $this->getUser()->getAgencia();
        $entityManager = $this->getDoctrine()->getManager();
        $form = $this->createForm(PorcentajeAgenciaType::class, $tnAgencia, [
            'action' => $this->generateUrl('user_backend_agencia_editar_porcentajes')
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->flush();

            $this->get('session')->getFlashBag()->add('success', 'Porcientos Agencia ' . $tnAgencia->getNombre() . ' actualizados correctamente.');
            return $this->redirectToRoute('fos_user_profile_show');
        }

        return $this->render('backend/configurations/editar_porcentajes.html.twig', [
            'tnAgencia' => $tnAgencia,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/backend/agencia/porcentajes/transferencias", name="user_backend_agencia_editar_porcentajes_transferencias")
     */
    public function usuarioAgenciaEditarPorcentajesTransferenciasAction(Request $request)
    {
        $tnAgencia = $this->getUser()->getAgencia();
        $entityManager = $this->getDoctrine()->getManager();
        $form = $this->createForm(PorcentajeAgenciaTransferenciaType::class, $tnAgencia, [
            'action' => $this->generateUrl('user_backend_agencia_editar_porcentajes_transferencias')
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->flush();

            $this->get('session')->getFlashBag()->add('success', 'Porcientos Transferencia de Agencia ' . $tnAgencia->getNombre() . ' actualizados correctamente.');
            return $this->redirectToRoute('fos_user_profile_show');
        }

        return $this->render('backend/configurations/editar_porcentajes_transf.html.twig', [
            'tnAgencia' => $tnAgencia,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/backend/agente/porcentajes", name="user_backend_agente_editar_porcentajes")
     */
    public function usuarioAgenteEditarPorcentajesAction(Request $request)
    {
        $tnAgente = $this->getUser()->getAgente();
        $entityManager = $this->getDoctrine()->getManager();
        $form = $this->createForm(PorcentajeAgenteType::class, $tnAgente, [
            'action' => $this->generateUrl('user_backend_agente_editar_porcentajes')
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->flush();

            $this->get('session')->getFlashBag()->add('success', 'Porcientos Agente ' . $tnAgente->getNombre() . ' actualizados correctamente.');
            return $this->redirectToRoute('fos_user_profile_show');
        }

        return $this->render('backend/configurations/editar_porcentajes_agente.html.twig', [
            'tnAgente' => $tnAgente,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/backend/agente/porcentajes/transferencias", name="user_backend_agente_editar_porcentajes_transferencias")
     */
    public function usuarioAgenteEditarPorcentajesTransferenciasAction(Request $request)
    {
        $tnAgente = $this->getUser()->getAgente();
        $entityManager = $this->getDoctrine()->getManager();
        $form = $this->createForm(PorcentajeAgenteTransferenciaType::class, $tnAgente, [
            'action' => $this->generateUrl('user_backend_agente_editar_porcentajes_transferencias')
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->flush();

            $this->get('session')->getFlashBag()->add('success', 'Porcientos Transferencia de Agente ' . $tnAgente->getNombre() . ' actualizados correctamente.');
            return $this->redirectToRoute('fos_user_profile_show');
        }

        return $this->render('backend/configurations/editar_porcentajes_transf_agente.html.twig', [
            'tnAgente' => $tnAgente,
            'form' => $form->createView()
        ]);
    }

}
