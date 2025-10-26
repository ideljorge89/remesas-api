<?php

namespace App\Controller\Backend;


use App\Entity\NmMoneda;
use App\Entity\TnCierreDistribuidor;
use App\Entity\TnHistorialDistribuidor;
use App\Entity\TnOperacionDist;
use App\Form\TnOperacionDistType;
use App\Form\Type\CierreDistribuidorType;
use App\Manager\TcPdfManager;
use App\Repository\NmMonedaRepository;
use App\Repository\TnCierreDistribuidorRepository;
use App\Repository\TnCreditoRepository;
use App\Repository\TnDistribuidorRepository;
use App\Repository\TnHistorialDistribuidorRepository;
use App\Repository\TnOperacionDistRepository;
use App\Repository\TnRemesaRepository;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/backend/contabiliad")
 */
class ContabilidadController extends AbstractController
{
    /**
     * @Route("/operaciones", name="tn_operaciones_distribuidor_index", methods={"GET"})
     */
    public function index(TnOperacionDistRepository $tnOperacionDistRepository, PaginatorInterface $paginator): Response
    {

        $request = $this->get('request_stack')->getCurrentRequest();

        $query = $tnOperacionDistRepository->createQueryBuilder('tnOperacionDist')
            ->orderBy('tnOperacionDist.created', "DESC")
            ->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            100
        );

        return $this->render('backend/contabilidad/operaciones/index.html.twig', [
            'operaciones' => $pagination,
        ]);
    }

    /**
     * @Route("/operaciones/new", name="tn_operaciones_distribuidor_new", methods={"GET","POST"})
     */
    public function new(Request $request, TnCreditoRepository $tnCreditoRepository): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $tnOperacionDist = new TnOperacionDist();
        $form = $this->createForm(TnOperacionDistType::class, $tnOperacionDist);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //Descontado el crédito al distribuidor
            $tnCredito = $tnCreditoRepository->findOneBy(['moneda' => $tnOperacionDist->getMoneda(), 'distribuidor' => $tnOperacionDist->getDistribuidor()]);
            if (!is_null($tnCredito)) {//Si el crédito en esa moneda para ese distribuidor existe lo modifico, si no lo creo.
                if ($tnOperacionDist->getTipo() == TnOperacionDist::OPERACION_RECIBIDO || $tnOperacionDist->getTipo() == TnOperacionDist::OPERACION_TRANSFERIDO) {
                    $tnCredito->setCredito($tnCredito->getCredito() + $tnOperacionDist->getImporte());
                    $entityManager->persist($tnCredito);
                } else {
                    $tnCredito->setCredito($tnCredito->getCredito() - $tnOperacionDist->getImporte());
                    $entityManager->persist($tnCredito);
                }

                $entityManager->persist($tnOperacionDist);
                $entityManager->flush();

                $this->get('session')->getFlashBag()->add('info', "Operación registrada correctamente.");
            } else {
                $this->get('session')->getFlashBag()->add('error', "Error al registrar la operación, no existe crédito creado para el Distribuidor/Moneda.");
            }

            return $this->redirectToRoute('tn_operaciones_distribuidor_new');
        }

        return $this->render('backend/contabilidad/operaciones/new.html.twig', [
            'tn_operacion_dist' => $tnOperacionDist,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/operaciones/historial/distribuidor", name="tn_operaciones_distribuidor_historial")
     * @Method({"GET", "POST"})
     */
    public function historialDistribuidor(Request $request, TnDistribuidorRepository $distribuidorRepository, TnOperacionDistRepository $tnOperacionDistRepository)
    {
        if ($request->isXmlHttpRequest()) {
            $idDistribuidor = $request->get('distribuidor');
            if (isset($idDistribuidor) && $idDistribuidor != "") {
                $distribuidor = $distribuidorRepository->find($idDistribuidor);
                return $this->render('backend/contabilidad/operaciones_distribuidor.html.twig', array(
                    'distribuidor' => $distribuidor,
                    'operaciones' => $tnOperacionDistRepository->findOperacionesDistribuidor($distribuidor)
                ));
            } else {
                return $this->render('backend/contabilidad/operaciones_distribuidor.html.twig', array());
            }
        }
    }

    /**
     * @Route("/credito/diario", name="tn_operaciones_creditos_diario", methods={"GET"})
     */
    public function creditosDiarios(TnCreditoRepository $tnCreditoRepository, TnOperacionDistRepository $operacionDistRepository, TnHistorialDistribuidorRepository $historialRepository, TnDistribuidorRepository $distribuidorRepository, NmMonedaRepository $monedaRepository): Response
    {
        $creditos = [];
        $distribuidores = $distribuidorRepository->findBy(['enabled' => true]);//Distribuidores habilitados
        $monedas = $monedaRepository->findBy(['enabled' => true]); // Monedas habilitadas y activadas
        $residuosCancelados = [];
        $totalResiduos = 0;
        foreach ($monedas as $moneda) {
            $monedaDistribuidor = [];
            $totales = [
                'inicial' => 0,
                'recibido' => 0,
                'entregado' => 0,
                'envios' => 0,
                'comision' => 0,
                'cancelado' => 0,
                'transferido' => 0,
                'gastos' => 0,
                'disponible' => 0

            ];
            foreach ($distribuidores as $distribuidor) {
                $credito = $tnCreditoRepository->findOneBy(['moneda' => $moneda, 'distribuidor' => $distribuidor]);
                $inicio = $credito->getLastCierre(); //Busco las operaciones desde el último cierre
                $fin = new \DateTime('now'); //Hasta el momento

                $historiales = $historialRepository->findHistorialesDistribuidorParams($distribuidor, $moneda, $inicio, $fin);
                $totalRemesasEfectivas = 0;
                $totalComision = 0;
                $totalEnvios = 0;
                foreach ($historiales as $historial) {
                    if ($historial instanceof TnHistorialDistribuidor) {
                        if ($historial->getEstado() == TnHistorialDistribuidor::ESTADO_EFECTIVA) {
                            $totalRemesasEfectivas += $historial->getImporteRemesa();
                            $totalComision += $historial->getTasaDistrucion();
                            $totalEnvios++;
                        }
                    }
                }
                $canceladas = [];
                $totalCanceladas = 0;
                //Buscando las canceladas en ese rango de fechas.
                $historialesCanceladas = $historialRepository->findHistorialesDistribuidorParamsCanceladas($distribuidor, $moneda, $inicio, $fin);
                foreach ($historialesCanceladas as $historial) {
                    if ($historial instanceof TnHistorialDistribuidor) {
                        if ($moneda->getComision()) {//Si es la moneda de la comisión, le sumo la cantidad que otras monedas que tiene ese distribuidor
                            $totalCanceladas += $historial->getImporteRemesa() + $historial->getTasaDistrucion();
                        } else {
                            $totalCanceladas += $historial->getImporteRemesa();
                            if (array_key_exists($distribuidor->getId(), $residuosCancelados)) {
                                $residuosCancelados[$distribuidor->getId()] = $residuosCancelados[$distribuidor->getId()] + $historial->getTasaDistrucion();
                            } else {
                                $residuosCancelados[$distribuidor->getId()] = $historial->getTasaDistrucion();
                            }
                            $totalResiduos += $historial->getTasaDistrucion();
                        }
                        $canceladas[] = $historial->getRemesa()->getFactura()->getNoFactura();
                    }
                }

                $totalRecibido = $operacionDistRepository->findOperacionesDistribuidorParams($distribuidor, $moneda, $inicio, $fin, TnOperacionDist::OPERACION_RECIBIDO);
                $totalTransferdido = $operacionDistRepository->findOperacionesDistribuidorParams($distribuidor, $moneda, $inicio, $fin, TnOperacionDist::OPERACION_TRANSFERIDO);
                $totalGastos = $operacionDistRepository->findOperacionesDistribuidorParams($distribuidor, $moneda, $inicio, $fin, TnOperacionDist::OPERACION_GASTOS);

                if ($moneda->getComision()) {//Si es la moneda de la comisión, le sumo la cantidad que otras monedas que tiene ese distribuidor
                    $totalComision = $totalComision + $distribuidor->getComision();
                }
                $monedaDistribuidor[] = [
                    'distribuidor_id' => $distribuidor->getId(),
                    'distribuidor' => $distribuidor->getNombre() . " " . $distribuidor->getApellidos() . "-" . $distribuidor->getPhone(),
                    'credito' => $credito,
                    'entregado' => number_format($totalRemesasEfectivas, 2),
                    'cancelado' => number_format($totalCanceladas, 2),
                    'facturaCanceladas' => $canceladas,
                    'comision' => number_format($totalComision, 2),
                    'envios' => $totalEnvios,
                    'totalRecibido' => number_format($totalRecibido, 2),
                    'totalTransferdido' => number_format($totalTransferdido, 2),
                    'totalGastos' => number_format($totalGastos, 2)
                ];

                $totales['inicial'] = $totales['inicial'] + $credito->getLastCredito();
                $totales['recibido'] = $totales['recibido'] + ($totalRecibido != null ? $totalRecibido : 0);
                $totales['entregado'] = $totales['entregado'] + $totalRemesasEfectivas;
                $totales['envios'] = $totales['envios'] + $totalEnvios;
                $totales['comision'] = $totales['comision'] + $totalComision;
                $totales['cancelado'] = $totales['cancelado'] + $totalCanceladas;
                $totales['transferido'] = $totales['transferido'] + ($totalTransferdido != null ? $totalTransferdido : 0);
                $totales['gastos'] = $totales['gastos'] + ($totalGastos != null ? $totalGastos : 0);
                $totales['disponible'] = $totales['disponible'] + $credito->getCredito();

            }

            $creditos[] = [
                'moneda' => $moneda,
                'distribuidores' => $monedaDistribuidor,
                'totales' => $totales
            ];
        }
        if ($totalResiduos != 0) {//Poniendo el residuo de cancelados en la moneda que lleva
            $pos = 0;
            foreach ($creditos as $credito) {
                if ($credito['moneda']->getComision()) {
                    foreach ($residuosCancelados as $key => $residuo) {
                        $posDist = 0;
                        foreach ($credito['distribuidores'] as $distribuidor) {
                            if ($distribuidor['distribuidor_id'] == $key) {
                                $creditos[$pos]['distribuidores'][$posDist]['cancelado'] = $creditos[$pos]['distribuidores'][$posDist]['cancelado'] + $residuo;
                                break;
                            }
                            $posDist++;
                        }

                    }
                    $creditos[$pos]['totales']['cancelado'] = $creditos[$pos]['totales']['cancelado'] + $totalResiduos;
                }
                $pos++;
            }
        }


        return $this->render('backend/contabilidad/creditos_diario.html.twig', [
            'creditos' => $creditos
        ]);
    }

    /**
     * @Route("/cierres/distribuidor", name="tn_operaciones_cierresDistribuidor")
     */
    public function remesasDistribuidorAction(Request $request, TnCierreDistribuidorRepository $tnCierreDistribuidorRepository)
    {
        $form = $this->createForm(CierreDistribuidorType::class);
        $form->handleRequest($request);
        $cierres = [];
        $totales = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $params = [];
            $params['fi'] = new \DateTime(date($data['fechaInicio']->format('Y/m/d') . " 00:00:00"));
            $params['ff'] = new \DateTime(date($data['fechaFin']->format('Y/m/d') . " 23:59:59"));

            if (count($data['distribuidor']) > 0) {//Para filtrar por las monedas.
                $temp = [];
                foreach ($data['distribuidor'] as $dist) {
                    $temp [] = $dist->getId();
                }
                $params['distribuidores'] = $temp;

            }
            if (count($data['moneda']) > 0) {//Para filtrar por las monedas.
                $temp = [];
                foreach ($data['moneda'] as $mon) {
                    $temp [] = $mon->getId();
                }
                $params['monedas'] = $temp;
            }

            $cierres = $tnCierreDistribuidorRepository->findCierresDistribuidorParams($params);

            foreach ($cierres as $cierre) {
                if (array_key_exists($cierre->getMoneda()->getSimbolo(), $totales)) {
                    $totales[$cierre->getMoneda()->getSimbolo()]['distribuidores'] = $totales[$cierre->getMoneda()->getSimbolo()]['distribuidores'] + 1;
                    $totales[$cierre->getMoneda()->getSimbolo()]['inicial'] = $totales[$cierre->getMoneda()->getSimbolo()]['inicial'] + $cierre->getSaldoInicial();
                    $totales[$cierre->getMoneda()->getSimbolo()]['entregado'] = $totales[$cierre->getMoneda()->getSimbolo()]['entregado'] + $cierre->getEntregado();
                    $totales[$cierre->getMoneda()->getSimbolo()]['envios'] = $totales[$cierre->getMoneda()->getSimbolo()]['envios'] + $cierre->getEnvios();
                    $totales[$cierre->getMoneda()->getSimbolo()]['comision'] = $totales[$cierre->getMoneda()->getSimbolo()]['comision'] + $cierre->getComision();
                    $totales[$cierre->getMoneda()->getSimbolo()]['disponible'] = $totales[$cierre->getMoneda()->getSimbolo()]['disponible'] + $cierre->getCredito();
                } else {
                    $totales[$cierre->getMoneda()->getSimbolo()] = [
                        'distribuidores' => 1,
                        'inicial' => $cierre->getSaldoInicial(),
                        'entregado' => $cierre->getEntregado(),
                        'envios' => $cierre->getEnvios(),
                        'comision' => $cierre->getComision(),
                        'disponible' => $cierre->getCredito(),
                    ];
                }
            }
        }

        return $this->render('backend/contabilidad/cierres_distribuidores.html.twig', [
            'form' => $form->createView(),
            'cierres' => $cierres,
            'totales' => $totales
        ]);
    }


    /**
     * @Route("/historiales/distribuidor", name="tn_historiales_cierres_distribuidor")
     */
    public function historialesDistribuidoresAction(Request $request, TnHistorialDistribuidorRepository $historialDistribuidorRepository)
    {
        $form = $this->createForm(CierreDistribuidorType::class);
        $form->handleRequest($request);
        $historiales = [];
        $totales = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $params = [];
            $params['fi'] = new \DateTime(date($data['fechaInicio']->format('Y/m/d') . " 00:00:00"));
            $params['ff'] = new \DateTime(date($data['fechaFin']->format('Y/m/d') . " 23:59:59"));

            if (count($data['distribuidor']) > 0) {//Para filtrar por las monedas.
                $temp = [];
                foreach ($data['distribuidor'] as $dist) {
                    $temp [] = $dist->getId();
                }
                $params['distribuidores'] = $temp;

            }
            if (count($data['moneda']) > 0) {//Para filtrar por las monedas.
                $temp = [];
                foreach ($data['moneda'] as $mon) {
                    $temp [] = $mon->getId();
                }
                $params['monedas'] = $temp;
            }

            //Buscar el historial de los distribuidores con los parámetros especificados.
            $historiales = $historialDistribuidorRepository->reportHistorialesDistribuidores($params);
        }

        return $this->render('backend/contabilidad/historial_distribuidores.html.twig', [
            'form' => $form->createView(),
            'historiales' => $historiales,
            'totales' => $totales
        ]);
    }

    /**
     * @Route("/print/{id}/pdf", name="tn_cierre_distribuidor_print", methods={"GET"})
     */
    public function exportRemesasReportePdf(Request $request, TnCierreDistribuidor $cierreDistribuidor, TcPdfManager $tcPdfManager): Response
    {
        //Exportando remesas a PDF
        $html = $this->renderView('backend/contabilidad/cierre_distribuidor_pdf.html.twig', array(
            'cierre' => $cierreDistribuidor,
            'historiales' => $cierreDistribuidor->getHistoriales()
        ));

        $date = new \DateTime('now');
        $tcPdfManager->getDocumentCierre('QM_Cierre_' . $cierreDistribuidor->getDistribuidor() . "_" . ($date->format('d-m-Y')), $html, 'I', ['title' => 'REPORTE DE CIERRE', 'date' => ['fecha' => $cierreDistribuidor->getCreated()->format(date('d/m/Y')), 'hora' => $cierreDistribuidor->getCreated()->format(date('H:i:s'))], 'user' => $cierreDistribuidor->getDistribuidor()]);
    }
}
