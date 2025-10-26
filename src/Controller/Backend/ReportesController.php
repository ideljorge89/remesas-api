<?php

namespace App\Controller\Backend;

use App\Entity\NmEstado;
use App\Entity\TnAgente;
use App\Entity\TnApoderadoFactura;
use App\Entity\TnDestinatario;
use App\Entity\TnDistribuidor;
use App\Entity\TnReporteEnvio;
use App\Form\Type\PanelEstadisticasType;
use App\Form\Type\ReporteAdminAgenciasType;
use App\Form\Type\ReporteAdminApoderadosType;
use App\Form\Type\ReporteAdminDistribuidorType;
use App\Form\Type\ReporteAdminRemesasType;
use App\Form\Type\ReporteAgenciaApoderadosType;
use App\Form\Type\ReporteAgentesRemesasType;
use App\Form\Type\ReporteAgentesUtilidadType;
use App\Form\Type\ReporteDestinatarioType;
use App\Form\Type\ReporteDistribuidorRemesasType;
use App\Form\Type\ReportesEnvioType;
use App\Manager\PhpExcelManager;
use App\Manager\TcPdfManager;
use App\Model\EditarReporteEnvioModel;
use App\Model\ReporteEnvioModel;
use App\Repository\TnAgenteRepository;
use App\Repository\TnApoderadoFacturaRepository;
use App\Repository\TnDestinatarioRepository;
use App\Repository\TnFacturaRepository;
use App\Repository\TnRemesaRepository;
use App\Repository\TnReporteEnvioRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/backend/reportes")
 * @package App\Controller\Backend
 */
class ReportesController extends AbstractController
{

    /**
     * @Route("/admin/panel/estadisticas", name="reportes_admin_panel_estadisticas")
     */
    public function panelEstdisticasAction(Request $request, SessionInterface $sessionBag, TnFacturaRepository $tnFacturaRepository, TnRemesaRepository $tnRemesaRepository, TcPdfManager $tcPdfManager)
    {
        $form = $this->createForm(PanelEstadisticasType::class);
        $form->handleRequest($request);

        $params = [];
        $result = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $params['fi'] = new \DateTime(date($data['fechaInicio']->format('Y/m/d') . " 00:00:00"));
            $params['ff'] = new \DateTime(date($data['fechaFin']->format('Y/m/d') . " 23:59:59"));

            if (count($data['distribuidor']) > 0) {//Para filtrar por los distribuidores.
                $temp = [];
                $tempShow = [];
                foreach ($data['distribuidor'] as $dist) {
                    $temp [] = $dist->getId();
                    $tempShow[] = $dist;
                }
                $params['distribuidores'] = $temp;

            }

            if (count($data['agencia']) > 0) {
                $temp = [];
                $tempAgentes = [];
                $tempShow = [];
                foreach ($data['agencia'] as $agencia) {
                    $temp [] = $agencia->getId();
                    $tempShow[] = $agencia;
                    foreach ($agencia->getAgentes() as $agente) {
                        $tempAgentes[] = $agente->getId();
                    }
                }
                $params['agencias'] = $temp;
                $params['agentes'] = $tempAgentes;

            }
            if (count($data['moneda']) > 0) {//Para filtrar por las monedas.
                $temp = [];
                $tempShow = [];
                foreach ($data['moneda'] as $mon) {
                    $temp [] = $mon->getId();
                    $tempShow[] = $mon->getSimbolo();
                }
                $params['monedas'] = $temp;
            }

            $totalAgencias = $tnFacturaRepository->findPanelFacturasAgenciaParams($params);

            $totalAgenciasSinDistribuir = $tnFacturaRepository->findPanelFacturasAgenciaParams($params, true);

            $totalDistribuidasAll = $tnRemesaRepository->findPanelDistribuidoresInRango($params);

            $totalDistribuidasInRange = $tnRemesaRepository->findPanelDistribuidoresInRango($params, true);

            $totalDistribuidasOutRange = $tnRemesaRepository->findPanelDistribuidoresInRango($params, false);
        } else {
            $params['fi'] = new \DateTime(date("Y-m-d 00:00:00"));
            $params['ff'] = new \DateTime(date("Y-m-d 23:59:59"));

            $totalAgencias = $tnFacturaRepository->findPanelFacturasAgenciaParams($params);

            $totalAgenciasSinDistribuir = $tnFacturaRepository->findPanelFacturasAgenciaParams($params, true);

            $totalDistribuidasAll = $tnRemesaRepository->findPanelDistribuidoresInRango($params);

            $totalDistribuidasInRange = $tnRemesaRepository->findPanelDistribuidoresInRango($params, true);

            $totalDistribuidasOutRange = $tnRemesaRepository->findPanelDistribuidoresInRango($params, false);
        }

        $result = [
            'totalAgencias' => $totalAgencias,
            'totalDistribuidasAll' => $totalDistribuidasAll,
            'totalAgenciasSinDistribuir' => $totalAgenciasSinDistribuir,
            'totalDistribuidasInRange' => $totalDistribuidasInRange,
            'totalDistribuidasOutRange' => $totalDistribuidasOutRange
        ];

        return $this->render('backend/reportes/panel_estadisticas.html.twig', [
            'form' => $form->createView(),
            'totales' => $result
        ]);
    }

    /**
     * @Route("/distrubuidor/remesas", name="reportes_distribuidor_remeas")
     */
    public function estadisticasAction(Request $request, TnRemesaRepository $tnRemesaRepository, TcPdfManager $tcPdfManager)
    {
        $user = $this->getUser();
        $form = $this->createForm(ReporteDistribuidorRemesasType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $params = [];
            $paramsShow = [];
            $params['fi'] = new \DateTime(date($data['fechaInicio']->format('Y/m/d') . " 00:00:00"));
            $paramsShow['fi'] = date($data['fechaInicio']->format('Y/m/d') . " 00:00:00");
            $params['ff'] = new \DateTime(date($data['fechaFin']->format('Y/m/d') . " 23:59:59"));
            $paramsShow['ff'] = date($data['fechaFin']->format('Y/m/d') . " 23:59:59");
            if (count($data['zonas']) > 0) {
                $temp = [];
                $tempShow = [];
                foreach ($data['zonas'] as $zona) {
                    $temp [] = $zona->getId();
                    $tempShow[] = $zona->getName();
                }
                $params['zonas'] = $temp;
                $paramsShow['zonas'] = implode(', ', $tempShow);

            } else {
                $paramsShow['zonas'] = 'Todas';
            }
            if (count($data['estado']) > 0) {
                $temp = [];
                $tempShow = [];
                foreach ($data['estado'] as $estado) {
                    if ($estado == 'P') {
                        $temp[] = '02';
                        $temp[] = '03';
                        $tempShow[] = "Pendiente";
                    } elseif ($estado == 'E') {
                        $temp[] = '04';
                        $tempShow[] = "Ejecutada";
                    } elseif ($estado == 'C') {
                        $temp[] = '05';
                        $tempShow[] = "Cancelada";
                    }
                }
                $params['estados'] = $temp;
                $paramsShow['estados'] = implode(', ', $tempShow);
            } else {
                $paramsShow['estados'] = 'Todos';
            }
            $remesas = $tnRemesaRepository->findRemesasDistribuidorParams($params, $user->getDistribuidor());
            //Exportando remesas a PDF
            $html = $this->renderView('backend/reportes/distribuidor_remesas_pdf.html.twig', array(
                'remesas' => $remesas,
                'params' => $paramsShow
            ));

            $date = new \DateTime('now');
            $tcPdfManager->getDocumentReport('QM_Remesas_' . $user->getDistribuidor() . ($date->format('d-m-Y')), $html, 'D', ['title' => 'REPORTE DE REMESAS - ' . $this->getUser()->getUsername()]);
        }
        return $this->render('backend/reportes/distribuidor_remesas.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/distribuidor/remesas", name="reportes_admin_distribuidor_remeas")
     */
    public function remesasDistribuidorAction(Request $request, SessionInterface $sessionBag, TnRemesaRepository $tnRemesaRepository, TcPdfManager $tcPdfManager)
    {
        $form = $this->createForm(ReporteAdminRemesasType::class);
        $form->handleRequest($request);
        $result = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $params = [];
            $paramsShow = [];
            $params['fi'] = new \DateTime(date($data['fechaInicio']->format('Y/m/d') . " 00:00:00"));
            $paramsShow['fi'] = date($data['fechaInicio']->format('Y/m/d') . " 00:00:00");
            $params['ff'] = new \DateTime(date($data['fechaFin']->format('Y/m/d') . " 23:59:59"));
            $paramsShow['ff'] = date($data['fechaFin']->format('Y/m/d') . " 23:59:59");

            if (count($data['distribuidor']) > 0) {//Para filtrar por las monedas.
                $temp = [];
                $tempShow = [];
                foreach ($data['distribuidor'] as $dist) {
                    $temp [] = $dist->getId();
                    $tempShow[] = $dist;
                }
                $params['distribuidores'] = $temp;
                $paramsShow['distribuidores'] = implode(', ', $tempShow);

            } else {
                $paramsShow['distribuidores'] = 'Todos';
            }
            if (count($data['moneda']) > 0) {//Para filtrar por las monedas.
                $temp = [];
                $tempShow = [];
                foreach ($data['moneda'] as $mon) {
                    $temp [] = $mon->getId();
                    $tempShow[] = $mon->getSimbolo();
                }
                $params['monedas'] = $temp;
                $paramsShow['monedas'] = implode(', ', $tempShow);

            } else {
                $paramsShow['distribuidores'] = 'Todos';
            }
            if (count($data['estado']) > 0) {
                $tempShow = [];
                foreach ($data['estado'] as $estado) {
                    if ($estado == '02' || $estado == '03') {
                        $tempShow[] = "Pendiente";
                    } elseif ($estado == '04') {
                        $tempShow[] = "Ejecutada";
                    } elseif ($estado == '05') {
                        $tempShow[] = "Cancelada";
                    }
                }
                $params['estados'] = $data['estado'];
                $paramsShow['estados'] = implode(', ', $tempShow);
            } else {
                $paramsShow['estados'] = 'Todos';
            }
            //Si envía el estado de Cancelado solo en el reporte, tengo que buscar las canceladas solamente con los demás datos.
            if (isset($params['estados']) && count($params['estados']) == 1 && in_array(NmEstado::ESTADO_CANCELADA_ID, $params['estados'])) {
                $remesas = $tnRemesaRepository->findRemesasAdminParamsDistribuidorCanceladas($params);
            } else {//Si no, normal la consulta.
                $remesas = $tnRemesaRepository->findRemesasAdminParamsDistribuidor($params);
            }
            foreach ($remesas as $remesa) {
                if (array_key_exists($remesa->getDistribuidor()->showData(), $result)) {
                    $provDest = $remesa->getDestinatario()->getProvincia()->getName();
                    if (array_key_exists($provDest, $result[$remesa->getDistribuidor()->showData()])) {
                        $result[$remesa->getDistribuidor()->showData()][$provDest][] = $remesa;
                    } else {
                        $result[$remesa->getDistribuidor()->showData()][$provDest] = [$remesa];
                    }

                } else {
                    $provDest = $remesa->getDestinatario()->getProvincia()->getName();
                    $result[$remesa->getDistribuidor()->showData()][$provDest] = [$remesa];
                }
            }

            $sessionBag->set('reportes/admin_reporte_distribuidores', [
                'params' => $paramsShow,
                'search' => $params
            ]);
        }

        if ($request->getMethod() == 'GET') {
            $sessionBag->remove('reportes/admin_reporte_distribuidores');
        }

        return $this->render('backend/reportes/admin_remesas.html.twig', [
            'form' => $form->createView(),
            'remesas' => $result
        ]);
    }

    /**
     * @Route("/print/distribuidor/remesas", name="reportes_admin_distribuidor_remeas_print")
     */
    public function printRemesasDistribuidorAction(Request $request, SessionInterface $sessionBag, TcPdfManager $tcPdfManager, TnRemesaRepository $tnRemesaRepository)
    {
        $user = $this->getUser();
        if ($sessionBag->has('reportes/admin_reporte_distribuidores')) {
            $reporte = $sessionBag->get('reportes/admin_reporte_distribuidores');
            $result = [];
            $remesas = $tnRemesaRepository->findRemesasAdminParamsDistribuidor($reporte['search']);
            foreach ($remesas as $remesa) {
                if (array_key_exists($remesa->getDistribuidor()->showData(), $result)) {
                    $provDest = $remesa->getDestinatario()->getProvincia()->getName();
                    if (array_key_exists($provDest, $result[$remesa->getDistribuidor()->showData()])) {
                        $result[$remesa->getDistribuidor()->showData()][$provDest][] = $remesa;
                    } else {
                        $result[$remesa->getDistribuidor()->showData()][$provDest] = [$remesa];
                    }

                } else {
                    $provDest = $remesa->getDestinatario()->getProvincia()->getName();
                    $result[$remesa->getDistribuidor()->showData()][$provDest] = [$remesa];
                }
            }
            //Exportando remesas a PDF
            $html = $this->renderView('backend/reportes/admin_remesas_pdf.html.twig', array(
                'remesas' => $result,
                'params' => $reporte['params']
            ));

            $date = new \DateTime('now');
            $tcPdfManager->getDocumentReport('QM_Remesas_' . $user->getUsername() . ($date->format('d-m-Y')), $html, 'I', ['title' => 'REPORTE DE DISTRIBUIDOR - ' . $this->getUser()->getUsername()]);
        } else {
            return $this->redirectToRoute('reportes_admin_distribuidor_remeas');
        }

    }


    /**
     * @Route("/admin/destinatario/remesas", name="reportes_admin_destinatario_remeas")
     */
    public function remesasDestinatarioAction(Request $request, TnRemesaRepository $tnRemesaRepository)
    {
        $form = $this->createForm(ReporteDestinatarioType::class);
        $form->handleRequest($request);
        $destinatarios = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $params = [];
            $paramsShow = [];
            $params['fi'] = new \DateTime(date($data['fechaInicio']->format('Y/m/d') . " 00:00:00"));
            $paramsShow['fi'] = date($data['fechaInicio']->format('Y/m/d') . " 00:00:00");
            $params['ff'] = new \DateTime(date($data['fechaFin']->format('Y/m/d') . " 23:59:59"));
            $paramsShow['ff'] = date($data['fechaFin']->format('Y/m/d') . " 23:59:59");

            if (count($data['agencia']) > 0) {
                $temp = [];
                $tempAgentes = [];
                $tempShow = [];
                foreach ($data['agencia'] as $agencia) {
                    $temp [] = $agencia->getId();
                    $tempShow[] = $agencia;
                    foreach ($agencia->getAgentes() as $agente) {
                        $tempAgentes[] = $agente->getId();
                    }
                }
                $params['agencias'] = $temp;
                $params['agentes'] = $tempAgentes;

            }

            $result = $tnRemesaRepository->findRemesasDestinatario($params);

            foreach ($result as $remesa) {
                $nombre = $remesa->getDestinatario()->getNombre();
                $apellidos = $remesa->getDestinatario()->getApellidos();
                if (array_key_exists($nombre . " " . $apellidos, $destinatarios)) {
                    $destinatarios[$nombre . " " . $apellidos]['total'] = $destinatarios[$nombre . " " . $apellidos]['total'] + 1;
                    $destinatarios[$nombre . " " . $apellidos]['remesas'][] = [
                        'importe' => $remesa->getImporteEntregar(),
                        'moneda' => $remesa->getMoneda()->getSimbolo(),
                        'estado' => $remesa->getFactura() ? $remesa->getFactura()->getEstado() : '',
                        'fecha' => $remesa->getFactura() ? $remesa->getFactura()->getCreated()->format('d/m/Y') : '',
                        'nro' => $remesa->getFactura() ? $remesa->getFactura()->getNoFactura() : '',
                        'distribuidor' => $remesa->getDistribuidor() ? ($remesa->getDistribuidor()->getNombre() . " " . $remesa->getDistribuidor()->getApellidos()) : ''];
                } else {
                    $destinatarios[$nombre . " " . $apellidos] = [
                        'total' => 1,
                        'remesas' => [
                            ['importe' => $remesa->getImporteEntregar(),
                                'moneda' => $remesa->getMoneda()->getSimbolo(),
                                'estado' => $remesa->getFactura() ? $remesa->getFactura()->getEstado() : '',
                                'fecha' => $remesa->getFactura() ? $remesa->getFactura()->getCreated()->format('d/m/Y') : '',
                                'nro' => $remesa->getFactura() ? $remesa->getFactura()->getNoFactura() : '',
                                'distribuidor' => $remesa->getDistribuidor() ? ($remesa->getDistribuidor()->getNombre() . " " . $remesa->getDistribuidor()->getApellidos()) : '']
                        ]
                    ];
                }
            }
            arsort($destinatarios);
        }

        return $this->render('backend/reportes/admin_destinatarios.html.twig', [
            'form' => $form->createView(),
            'remesas' => $destinatarios
        ]);
    }

    /**
     * @Route("/admin/agencias/remesas", name="reportes_admin_agencia_remeas")
     */
    public function remesasAgenciaAction(Request $request, SessionInterface $sessionBag, TnFacturaRepository $tnFacturaRepository, TcPdfManager $tcPdfManager)
    {
        ini_set('memory_limit', -1);

        $form = $this->createForm(ReporteAdminAgenciasType::class);
        $form->handleRequest($request);
        $result = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $params = [];
            $paramsShow = [];
            $params['fi'] = new \DateTime(date($data['fechaInicio']->format('Y/m/d') . " 00:00:00"));
            $paramsShow['fi'] = date($data['fechaInicio']->format('Y/m/d') . " 00:00:00");
            $params['ff'] = new \DateTime(date($data['fechaFin']->format('Y/m/d') . " 23:59:59"));
            $paramsShow['ff'] = date($data['fechaFin']->format('Y/m/d') . " 23:59:59");
            if (count($data['agencia']) > 0) {
                $temp = [];
                $tempAgentes = [];
                $tempShow = [];
                foreach ($data['agencia'] as $agencia) {
                    $temp [] = $agencia->getId();
                    $tempShow[] = $agencia;
                    foreach ($agencia->getAgentes() as $agente) {
                        $tempAgentes[] = $agente->getId();
                    }
                }
                $params['agencias'] = $temp;
                $params['agentes'] = $tempAgentes;
                $paramsShow['agencias'] = implode(', ', $tempShow);

            } else {
                $paramsShow['agencias'] = 'Todas';
            }
            if (count($data['moneda']) > 0) {//Para filtrar por las monedas.
                $temp = [];
                $tempShow = [];
                foreach ($data['moneda'] as $mon) {
                    $temp [] = $mon->getId();
                    $tempShow[] = $mon->getSimbolo();
                }
                $params['monedas'] = $temp;
                $paramsShow['monedas'] = implode(', ', $tempShow);

            } else {
                $paramsShow['distribuidores'] = 'Todos';
            }
            if (count($data['estado']) > 0) {
                $tempShow = [];
                foreach ($data['estado'] as $estado) {
                    if ($estado == '01') {
                        $tempShow[] = "Pendiente";
                    } elseif ($estado == '02') {
                        $tempShow[] = "Aprobada";
                    } elseif ($estado == '03') {
                        $tempShow[] = "Distribución";
                    } elseif ($estado == '04') {
                        $tempShow[] = "Ejecutada";
                    } elseif ($estado == '05') {
                        $tempShow[] = "Cancelada";
                    }
                }
                $params['estados'] = $data['estado'];
                $paramsShow['estados'] = implode(', ', $tempShow);
            } else {
                $paramsShow['estados'] = 'Todos';
            }
            $facturas = $tnFacturaRepository->findFacturasAgenciaAdminParams($params);

            foreach ($facturas as $factura) {
                if ($factura->getAgencia() != null) {
                    if (array_key_exists($factura->getAgencia()->getNombre(), $result)) {
                        $result[$factura->getAgencia()->getNombre()][] = $factura;
                    } else {
                        $result[$factura->getAgencia()->getNombre()] = [$factura];
                    }
                } elseif ($factura->getAgente() != null) {
                    if (array_key_exists($factura->getAgente()->getAgencia()->getNombre(), $result)) {
                        $result[$factura->getAgente()->getAgencia()->getNombre()][] = $factura;
                    } else {
                        $result[$factura->getAgente()->getAgencia()->getNombre()] = [$factura];
                    }
                }
            }

            $sessionBag->set('reportes/admin_reporte_agencias', [
                'params' => $paramsShow,
                'search' => $params
            ]);
        }

        if ($request->getMethod() == 'GET') {
            $sessionBag->remove('reportes/admin_reporte_agencias');
        }
        return $this->render('backend/reportes/admin_agencias.html.twig', [
            'form' => $form->createView(),
            'facturas' => $result
        ]);
    }

    /**
     * @Route("/admin/distribucion/agencias/", name="reportes_admin_agencia_distribucion")
     */
    public function remesasAgenciaDistribucionAction(Request $request, SessionInterface $sessionBag, TnFacturaRepository $tnFacturaRepository, TcPdfManager $tcPdfManager)
    {
        ini_set('memory_limit', -1);

        $form = $this->createForm(ReporteAdminAgenciasType::class);
        $form->handleRequest($request);
        $result = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $params = [];
            $paramsShow = [];
            $params['fi'] = new \DateTime(date($data['fechaInicio']->format('Y/m/d') . " 00:00:00"));
            $paramsShow['fi'] = date($data['fechaInicio']->format('Y/m/d') . " 00:00:00");
            $params['ff'] = new \DateTime(date($data['fechaFin']->format('Y/m/d') . " 23:59:59"));
            $paramsShow['ff'] = date($data['fechaFin']->format('Y/m/d') . " 23:59:59");
            if (count($data['agencia']) > 0) {
                $temp = [];
                $tempAgentes = [];
                $tempShow = [];
                foreach ($data['agencia'] as $agencia) {
                    $temp [] = $agencia->getId();
                    $tempShow[] = $agencia;
                    foreach ($agencia->getAgentes() as $agente) {
                        $tempAgentes[] = $agente->getId();
                    }
                }
                $params['agencias'] = $temp;
                $params['agentes'] = $tempAgentes;
                $paramsShow['agencias'] = implode(', ', $tempShow);

            } else {
                $paramsShow['agencias'] = 'Todas';
            }
            if (count($data['moneda']) > 0) {//Para filtrar por las monedas.
                $temp = [];
                $tempShow = [];
                foreach ($data['moneda'] as $mon) {
                    $temp [] = $mon->getId();
                    $tempShow[] = $mon->getSimbolo();
                }
                $params['monedas'] = $temp;
                $paramsShow['monedas'] = implode(', ', $tempShow);

            } else {
                $paramsShow['distribuidores'] = 'Todos';
            }
            if (count($data['estado']) > 0) {
                $tempShow = [];
                foreach ($data['estado'] as $estado) {
                    if ($estado == '01') {
                        $tempShow[] = "Pendiente";
                    } elseif ($estado == '02') {
                        $tempShow[] = "Aprobada";
                    } elseif ($estado == '03') {
                        $tempShow[] = "Distribución";
                    } elseif ($estado == '04') {
                        $tempShow[] = "Ejecutada";
                    } elseif ($estado == '05') {
                        $tempShow[] = "Cancelada";
                    }
                }
                $params['estados'] = $data['estado'];
                $paramsShow['estados'] = implode(', ', $tempShow);
            } else {
                $paramsShow['estados'] = 'Todos';
            }
            $facturas = $tnFacturaRepository->findFacturasAgenciaDistribucionAdminParams($params);

            foreach ($facturas as $factura) {
                if ($factura->getAgencia() != null) {
                    if (array_key_exists($factura->getAgencia()->getNombre(), $result)) {
                        $result[$factura->getAgencia()->getNombre()][] = $factura;
                    } else {
                        $result[$factura->getAgencia()->getNombre()] = [$factura];
                    }
                } elseif ($factura->getAgente() != null) {
                    if (array_key_exists($factura->getAgente()->getAgencia()->getNombre(), $result)) {
                        $result[$factura->getAgente()->getAgencia()->getNombre()][] = $factura;
                    } else {
                        $result[$factura->getAgente()->getAgencia()->getNombre()] = [$factura];
                    }
                }
            }

            $sessionBag->set('reportes/admin_reporte_agencias_dist', [
                'params' => $paramsShow,
                'search' => $params
            ]);
        }

        if ($request->getMethod() == 'GET') {
            $sessionBag->remove('reportes/admin_reporte_agencias_dist');
        }
        return $this->render('backend/reportes/admin_agencias_dist.html.twig', [
            'form' => $form->createView(),
            'facturas' => $result
        ]);
    }

    /**
     * @Route("/agencia/apoderados/costos", name="reportes_agencia_apoderados_costos")
     */
    public function agenciaApoderadosCostosAction(Request $request, SessionInterface $sessionBag, TnApoderadoFacturaRepository $tnApoderadoFacturaRepository, TcPdfManager $tcPdfManager)
    {
        $form = $this->createForm(ReporteAgenciaApoderadosType::class);
        $form->handleRequest($request);
        $result = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $params = [];
            $paramsShow = [];
            $params['fi'] = new \DateTime(date($data['fechaInicio']->format('Y/m/d') . " 00:00:00"));
            $paramsShow['fi'] = date($data['fechaInicio']->format('Y/m/d') . " 00:00:00");
            $params['ff'] = new \DateTime(date($data['fechaFin']->format('Y/m/d') . " 23:59:59"));
            $paramsShow['ff'] = date($data['fechaFin']->format('Y/m/d') . " 23:59:59");

            $apoderado = $this->getUser()->getAgencia()->getApoderado();
            $params['apoderado'] = $apoderado->getId();
            $paramsShow['apoderado'] = $apoderado->getNombre() . " - " . $apoderado->getAgencia()->getNombre();

            if (count($data['agencia']) > 0) {
                $temp = [];
                $tempShow = [];
                foreach ($data['agencia'] as $agencia) {
                    $temp [] = $agencia->getId();
                    $tempShow[] = $agencia;
                }
                $params['agencias'] = $temp;
                $paramsShow['agencias'] = implode(', ', $tempShow);

            } else {
                $tempShow = [];
                foreach ($apoderado->getSubordinadas() as $agencia) {
                    $tempShow[] = $agencia;
                }
                $paramsShow['agencias'] = implode(', ', $tempShow);
            }

            if (count($data['estado']) > 0) {
                $tempShow = [];
                foreach ($data['estado'] as $estado) {
                    if ($estado == '01') {
                        $tempShow[] = "Pendiente";
                    } elseif ($estado == '02') {
                        $tempShow[] = "Aprobada";
                    } elseif ($estado == '03') {
                        $tempShow[] = "Distribución";
                    } elseif ($estado == '04') {
                        $tempShow[] = "Ejecutada";
                    } elseif ($estado == '05') {
                        $tempShow[] = "Cancelada";
                    }
                }
                $params['estados'] = $data['estado'];
                $paramsShow['estados'] = implode(', ', $tempShow);
            } else {
                $paramsShow['estados'] = 'Todos';
            }

            $apoderadoFacturas = $tnApoderadoFacturaRepository->findCostosAgenciaApoderadoParams($params);

            foreach ($apoderadoFacturas as $apodFactura) {
                if ($apodFactura instanceof TnApoderadoFactura) {
                    if ($apodFactura->getFactura()->getAgencia() != null) {
                        if (array_key_exists($apodFactura->getFactura()->getAgencia()->getNombre(), $result)) {
                            $result[$apodFactura->getFactura()->getAgencia()->getNombre()][] = $apodFactura;
                        } else {
                            $result[$apodFactura->getFactura()->getAgencia()->getNombre()] = [$apodFactura];
                        }
                    } elseif ($apodFactura->getFactura()->getAgente() != null) {
                        if (array_key_exists($apodFactura->getFactura()->getAgente()->getAgencia()->getNombre(), $result)) {
                            $result[$apodFactura->getFactura()->getAgente()->getAgencia()->getNombre()][] = $apodFactura;
                        } else {
                            $result[$apodFactura->getFactura()->getAgente()->getAgencia()->getNombre()] = [$apodFactura];
                        }
                    }
                }
            }

            $sessionBag->set('reportes/agencia_reporte_apoderado', [
                'params' => $paramsShow,
                'facturas' => $result,
            ]);
        }

        if ($request->getMethod() == 'GET') {
            $sessionBag->remove('reportes/agencia_reporte_apoderado');
        }
        return $this->render('backend/reportes/agencia_apoderados.html.twig', [
            'form' => $form->createView(),
            'facturas' => $result
        ]);
    }

    /**
     * @Route("/print/agencia/apoderados/costos", name="reportes_agencia_apoderado_costos_print")
     */
    public function agenciaApoderadosCostosPrintAction(Request $request, SessionInterface $sessionBag, TcPdfManager $tcPdfManager)
    {
        $user = $this->getUser();
        if ($sessionBag->has('reportes/agencia_reporte_apoderado')) {
            $reporte = $sessionBag->get('reportes/agencia_reporte_apoderado');
            //Exportando remesas a PDF
            $html = $this->renderView('backend/reportes/agencia_apoderados_pdf.html.twig', array(
                'facturas' => $reporte['facturas'],
                'params' => $reporte['params']
            ));

            $date = new \DateTime('now');
            $tcPdfManager->getDocumentReport('QM_Remesas_' . $user->getUsername() . ($date->format('d - m - Y')), $html, 'I', ['title' => 'REPORTE DE APODERADO - ' . $this->getUser()->getUsername()]);
        } else {
            return $this->redirectToRoute('reportes_agencia_apoderados_costos');
        }

    }


    /**
     * @Route("/agencia/apoderados/venta", name="reportes_agencia_apoderados_venta")
     */
    public function agenciaApoderadosVentaAction(Request $request, SessionInterface $sessionBag, TnApoderadoFacturaRepository $tnApoderadoFacturaRepository, TcPdfManager $tcPdfManager)
    {
        $form = $this->createForm(ReporteAgenciaApoderadosType::class);
        $form->handleRequest($request);
        $result = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $params = [];
            $paramsShow = [];
            $params['fi'] = new \DateTime(date($data['fechaInicio']->format('Y/m/d') . " 00:00:00"));
            $paramsShow['fi'] = date($data['fechaInicio']->format('Y/m/d') . " 00:00:00");
            $params['ff'] = new \DateTime(date($data['fechaFin']->format('Y/m/d') . " 23:59:59"));
            $paramsShow['ff'] = date($data['fechaFin']->format('Y/m/d') . " 23:59:59");

            $apoderado = $this->getUser()->getAgencia()->getApoderado();
            $params['apoderado'] = $apoderado->getId();
            $paramsShow['apoderado'] = $apoderado->getNombre() . " - " . $apoderado->getAgencia()->getNombre();

            if (count($data['agencia']) > 0) {
                $temp = [];
                $tempShow = [];
                foreach ($data['agencia'] as $agencia) {
                    $temp [] = $agencia->getId();
                    $tempShow[] = $agencia;
                }
                $params['agencias'] = $temp;
                $paramsShow['agencias'] = implode(', ', $tempShow);

            } else {
                $tempShow = [];
                foreach ($apoderado->getSubordinadas() as $agencia) {
                    $tempShow[] = $agencia;
                }
                $paramsShow['agencias'] = implode(', ', $tempShow);
            }

            if (count($data['estado']) > 0) {
                $tempShow = [];
                foreach ($data['estado'] as $estado) {
                    if ($estado == '01') {
                        $tempShow[] = "Pendiente";
                    } elseif ($estado == '02') {
                        $tempShow[] = "Aprobada";
                    } elseif ($estado == '03') {
                        $tempShow[] = "Distribución";
                    } elseif ($estado == '04') {
                        $tempShow[] = "Ejecutada";
                    } elseif ($estado == '05') {
                        $tempShow[] = "Cancelada";
                    }
                }
                $params['estados'] = $data['estado'];
                $paramsShow['estados'] = implode(', ', $tempShow);
            } else {
                $paramsShow['estados'] = 'Todos';
            }

            $apoderadoFacturas = $tnApoderadoFacturaRepository->findCostosAgenciaApoderadoParams($params);

            foreach ($apoderadoFacturas as $apodFactura) {
                if ($apodFactura instanceof TnApoderadoFactura) {
                    if ($apodFactura->getFactura()->getAgencia() != null) {
                        if (array_key_exists($apodFactura->getFactura()->getAgencia()->getNombre(), $result)) {
                            $result[$apodFactura->getFactura()->getAgencia()->getNombre()][] = $apodFactura;
                        } else {
                            $result[$apodFactura->getFactura()->getAgencia()->getNombre()] = [$apodFactura];
                        }
                    } elseif ($apodFactura->getFactura()->getAgente() != null) {
                        if (array_key_exists($apodFactura->getFactura()->getAgente()->getAgencia()->getNombre(), $result)) {
                            $result[$apodFactura->getFactura()->getAgente()->getAgencia()->getNombre()][] = $apodFactura;
                        } else {
                            $result[$apodFactura->getFactura()->getAgente()->getAgencia()->getNombre()] = [$apodFactura];
                        }
                    }
                }
            }

            $sessionBag->set('reportes/agencia_apoderado_venta', [
                'params' => $paramsShow,
                'facturas' => $result,
            ]);
        }

        if ($request->getMethod() == 'GET') {
            $sessionBag->remove('reportes/agencia_apoderado_venta');
        }
        return $this->render('backend/reportes/agencia_apoderados_venta.html.twig', [
            'form' => $form->createView(),
            'facturas' => $result
        ]);
    }

    /**
     * @Route("/print/agencia/apoderados/venta", name="reportes_agencia_apoderado_venta_print")
     */
    public function agenciaApoderadosVentaPrintAction(Request $request, SessionInterface $sessionBag, TcPdfManager $tcPdfManager)
    {
        $user = $this->getUser();
        if ($sessionBag->has('reportes/agencia_apoderado_venta')) {
            $reporte = $sessionBag->get('reportes/agencia_apoderado_venta');
            //Exportando remesas a PDF
            $html = $this->renderView('backend/reportes/agencia_apoderados_venta_pdf.html.twig', array(
                'facturas' => $reporte['facturas'],
                'params' => $reporte['params']
            ));

            $date = new \DateTime('now');
            $tcPdfManager->getDocumentReport('QM_Remesas_' . $user->getUsername() . ($date->format('d - m - Y')), $html, 'I', ['title' => 'REPORTE DE APODERADO - ' . $this->getUser()->getUsername()]);
        } else {
            return $this->redirectToRoute('reportes_agencia_apoderados_venta');
        }

    }

    /**
     * @Route("agencia/apoderados/utilidades", name="reportes_agencia_apoderados_utilidades")
     */
    public function agenciaApoderadoUtilidadesAction(Request $request, SessionInterface $sessionBag, TnApoderadoFacturaRepository $tnApoderadoFacturaRepository, TcPdfManager $tcPdfManager)
    {
        $form = $this->createForm(ReporteAgenciaApoderadosType::class);
        $form->handleRequest($request);
        $result = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $params = [];
            $paramsShow = [];
            $params['fi'] = new \DateTime(date($data['fechaInicio']->format('Y/m/d') . " 00:00:00"));
            $paramsShow['fi'] = date($data['fechaInicio']->format('Y/m/d') . " 00:00:00");
            $params['ff'] = new \DateTime(date($data['fechaFin']->format('Y/m/d') . " 23:59:59"));
            $paramsShow['ff'] = date($data['fechaFin']->format('Y/m/d') . " 23:59:59");

            $apoderado = $this->getUser()->getAgencia()->getApoderado();
            $params['apoderado'] = $apoderado->getId();
            $paramsShow['apoderado'] = $apoderado->getNombre() . " - " . $apoderado->getAgencia()->getNombre();

            if (count($data['agencia']) > 0) {
                $temp = [];
                $tempShow = [];
                foreach ($data['agencia'] as $agencia) {
                    $temp [] = $agencia->getId();
                    $tempShow[] = $agencia;
                }
                $params['agencias'] = $temp;
                $paramsShow['agencias'] = implode(', ', $tempShow);

            } else {
                $tempShow = [];
                foreach ($apoderado->getSubordinadas() as $agencia) {
                    $tempShow[] = $agencia;
                }
                $paramsShow['agencias'] = implode(', ', $tempShow);
            }

            if (count($data['estado']) > 0) {
                $tempShow = [];
                foreach ($data['estado'] as $estado) {
                    if ($estado == '01') {
                        $tempShow[] = "Pendiente";
                    } elseif ($estado == '02') {
                        $tempShow[] = "Aprobada";
                    } elseif ($estado == '03') {
                        $tempShow[] = "Distribución";
                    } elseif ($estado == '04') {
                        $tempShow[] = "Ejecutada";
                    } elseif ($estado == '05') {
                        $tempShow[] = "Cancelada";
                    }
                }
                $params['estados'] = $data['estado'];
                $paramsShow['estados'] = implode(', ', $tempShow);
            } else {
                $paramsShow['estados'] = 'Todos';
            }

            $apoderadoFacturas = $tnApoderadoFacturaRepository->findCostosAgenciaApoderadoParams($params);

            foreach ($apoderadoFacturas as $apodFactura) {
                if ($apodFactura instanceof TnApoderadoFactura) {
                    if ($apodFactura->getFactura()->getAgencia() != null) {
                        if (array_key_exists($apodFactura->getFactura()->getAgencia()->getNombre(), $result)) {
                            $result[$apodFactura->getFactura()->getAgencia()->getNombre()][] = $apodFactura;
                        } else {
                            $result[$apodFactura->getFactura()->getAgencia()->getNombre()] = [$apodFactura];
                        }
                    } elseif ($apodFactura->getFactura()->getAgente() != null) {
                        if (array_key_exists($apodFactura->getFactura()->getAgente()->getAgencia()->getNombre(), $result)) {
                            $result[$apodFactura->getFactura()->getAgente()->getAgencia()->getNombre()][] = $apodFactura;
                        } else {
                            $result[$apodFactura->getFactura()->getAgente()->getAgencia()->getNombre()] = [$apodFactura];
                        }
                    }
                }
            }

            $sessionBag->set('reportes/agencia_apoderado_utilidades', [
                'params' => $paramsShow,
                'facturas' => $result,
            ]);
        }

        if ($request->getMethod() == 'GET') {
            $sessionBag->remove('reportes/agencia_apoderado_utilidades');
        }

        return $this->render('backend/reportes/agencia_apoderado_utilidad.html.twig', [
            'facturas' => $result,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/print/utilidades/apoderado", name="reportes_utilidades_agencia_apoderado_print")
     */
    public function utilidadesAgenciaApoderadoPrintAction(Request $request, SessionInterface $sessionBag, TcPdfManager $tcPdfManager)
    {
        $user = $this->getUser();
        if ($sessionBag->has('reportes/agencia_apoderado_utilidades')) {
            $reporte = $sessionBag->get('reportes/agencia_apoderado_utilidades');
            //Exportando remesas a PDF
            $html = $this->renderView('backend/reportes/agencia_apoderado_utilidad_pdf.html.twig', array(
                'facturas' => $reporte['facturas'],
                'params' => $reporte['params']
            ));

            $date = new \DateTime('now');
            if ($user->getFilePath()) {
                $tcPdfManager->getDocumentReport('QM_Remesas_' . $user->getUsername() . ($date->format('d - m - Y')), $html, 'I', ['avatar' => $user->getFilePath(), 'title' => 'REPORTE DE UTILIDAD APODERADO- ' . $this->getUser()->getUsername()]);
            } else {
                $tcPdfManager->getDocumentReport('QM_Remesas_' . $user->getUsername() . ($date->format('d - m - Y')), $html, 'I', ['title' => 'REPORTE DE UTILIDAD APODERADO- ' . $this->getUser()->getUsername()]);
            }
        } else {
            return $this->redirectToRoute('reportes_agentes_remeas');
        }

    }

    /**
     * @Route("/admin/apoderados/remesas", name="reportes_admin_apoderados_remesas")
     */
    public function remesasApoderadosAction(Request $request, SessionInterface $sessionBag, TnApoderadoFacturaRepository $tnApoderadoFacturaRepository, TcPdfManager $tcPdfManager)
    {
        $form = $this->createForm(ReporteAdminApoderadosType::class);
        $form->handleRequest($request);
        $result = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $params = [];
            $paramsShow = [];
            $params['fi'] = new \DateTime(date($data['fechaInicio']->format('Y/m/d') . " 00:00:00"));
            $paramsShow['fi'] = date($data['fechaInicio']->format('Y/m/d') . " 00:00:00");
            $params['ff'] = new \DateTime(date($data['fechaFin']->format('Y/m/d') . " 23:59:59"));
            $paramsShow['ff'] = date($data['fechaFin']->format('Y/m/d') . " 23:59:59");

            $apoderado = $data['apoderado'];
            $params['apoderado'] = $apoderado->getId();
            $paramsShow['apoderado'] = $apoderado->getNombre() . " - " . $apoderado->getAgencia()->getNombre();

            if (count($data['estado']) > 0) {
                $tempShow = [];
                foreach ($data['estado'] as $estado) {
                    if ($estado == '01') {
                        $tempShow[] = "Pendiente";
                    } elseif ($estado == '02') {
                        $tempShow[] = "Aprobada";
                    } elseif ($estado == '03') {
                        $tempShow[] = "Distribución";
                    } elseif ($estado == '04') {
                        $tempShow[] = "Ejecutada";
                    } elseif ($estado == '05') {
                        $tempShow[] = "Cancelada";
                    }
                }
                $params['estados'] = $data['estado'];
                $paramsShow['estados'] = implode(', ', $tempShow);
            } else {
                $paramsShow['estados'] = 'Todos';
            }

            $apoderadoFacturas = $tnApoderadoFacturaRepository->findFacturasApoderadoParams($params);

            foreach ($apoderadoFacturas as $apodFactura) {
                if ($apodFactura instanceof TnApoderadoFactura) {
                    if ($apodFactura->getFactura()->getAgencia() != null) {
                        if (array_key_exists($apodFactura->getFactura()->getAgencia()->getNombre(), $result)) {
                            $result[$apodFactura->getFactura()->getAgencia()->getNombre()][] = $apodFactura;
                        } else {
                            $result[$apodFactura->getFactura()->getAgencia()->getNombre()] = [$apodFactura];
                        }
                    } elseif ($apodFactura->getFactura()->getAgente() != null) {
                        if (array_key_exists($apodFactura->getFactura()->getAgente()->getAgencia()->getNombre(), $result)) {
                            $result[$apodFactura->getFactura()->getAgente()->getAgencia()->getNombre()][] = $apodFactura;
                        } else {
                            $result[$apodFactura->getFactura()->getAgente()->getAgencia()->getNombre()] = [$apodFactura];
                        }
                    }
                }
            }

            $sessionBag->set('reportes/admin_reporte_apoderado', [
                'params' => $paramsShow,
                'facturas' => $result,
            ]);
        }

        if ($request->getMethod() == 'GET') {
            $sessionBag->remove('reportes/admin_reporte_apoderado');
        }
        return $this->render('backend/reportes/admin_apoderados.html.twig', [
            'form' => $form->createView(),
            'facturas' => $result
        ]);
    }

    /**
     * @Route("/print/apoderado/remesas", name="reportes_admin_apoderado_remeas_print")
     */
    public function remesasApoderadosPrintAction(Request $request, SessionInterface $sessionBag, TcPdfManager $tcPdfManager)
    {
        $user = $this->getUser();
        if ($sessionBag->has('reportes/admin_reporte_apoderado')) {
            $reporte = $sessionBag->get('reportes/admin_reporte_apoderado');
            //Exportando remesas a PDF
            $html = $this->renderView('backend/reportes/admin_apoderados_pdf.html.twig', array(
                'facturas' => $reporte['facturas'],
                'params' => $reporte['params']
            ));

            $date = new \DateTime('now');
            $tcPdfManager->getDocumentReport('QM_Remesas_' . $user->getUsername() . ($date->format('d - m - Y')), $html, 'I', ['title' => 'REPORTE DE APODERADO - ' . $this->getUser()->getUsername()]);
        } else {
            return $this->redirectToRoute('reportes_admin_apoderados_remesas');
        }

    }

    /**
     * @Route("/print/agencias/remesas", name="reportes_admin_agencia_remeas_print")
     */
    public function remesasAgenciaPrintAction(Request $request, SessionInterface $sessionBag, TcPdfManager $tcPdfManager, TnFacturaRepository $tnFacturaRepository)
    {
        $user = $this->getUser();
        if ($sessionBag->has('reportes/admin_reporte_agencias')) {
            ini_set('memory_limit', -1);
            ini_set('max_execution_time', 300);

            $reporte = $sessionBag->get('reportes/admin_reporte_agencias');

            $facturas = $tnFacturaRepository->findFacturasAgenciaAdminParams($reporte['search']);

            $result = [];
            foreach ($facturas as $factura) {
                if ($factura->getAgencia() != null) {
                    if (array_key_exists($factura->getAgencia()->getNombre(), $result)) {
                        $result[$factura->getAgencia()->getNombre()][] = $factura;
                    } else {
                        $result[$factura->getAgencia()->getNombre()] = [$factura];
                    }
                } elseif ($factura->getAgente() != null) {
                    if (array_key_exists($factura->getAgente()->getAgencia()->getNombre(), $result)) {
                        $result[$factura->getAgente()->getAgencia()->getNombre()][] = $factura;
                    } else {
                        $result[$factura->getAgente()->getAgencia()->getNombre()] = [$factura];
                    }
                }
            }
            //Exportando remesas a PDF
            $html = $this->renderView('backend/reportes/admin_agencias_pdf.html.twig', array(
                'facturas' => $result,
                'params' => $reporte['params']
            ));

            $date = new \DateTime('now');
            $tcPdfManager->getDocumentReport('QM_Remesas_' . $user->getUsername() . ($date->format('d - m - Y')), $html, 'I', ['title' => 'REPORTE DE AGENCIA - ' . $this->getUser()->getUsername()]);
        } else {
            return $this->redirectToRoute('reportes_admin_agencia_remeas');
        }

    }


    /**
     * @Route("/excel/agencias/remesas", name="reportes_admin_agencia_remeas_excel", methods={"GET"})
     */
    public function remesasAgenciaExcelAction(Request $request, SessionInterface $sessionBag, PhpExcelManager $phpExcelManager, TnFacturaRepository $tnFacturaRepository): Response
    {
        $user = $this->getUser();
        if ($sessionBag->has('reportes/admin_reporte_agencias')) {
            ini_set('memory_limit', -1);
            ini_set('max_execution_time', 300);

            $reporte = $sessionBag->get('reportes/admin_reporte_agencias');

            $facturas = $tnFacturaRepository->findFacturasAgenciaAdminParams($reporte['search']);

            $result = [];
            foreach ($facturas as $factura) {
                if ($factura->getAgencia() != null) {
                    if (array_key_exists($factura->getAgencia()->getNombre(), $result)) {
                        $result[$factura->getAgencia()->getNombre()][] = $factura;
                    } else {
                        $result[$factura->getAgencia()->getNombre()] = [$factura];
                    }
                } elseif ($factura->getAgente() != null) {
                    if (array_key_exists($factura->getAgente()->getAgencia()->getNombre(), $result)) {
                        $result[$factura->getAgente()->getAgencia()->getNombre()][] = $factura;
                    } else {
                        $result[$factura->getAgente()->getAgencia()->getNombre()] = [$factura];
                    }
                }
            }
            //Exportando remesas a EXCEL

            $report = $phpExcelManager->exportDocumentoReporteAgencias($result);

            $date = new \DateTime('now');
            return
                $phpExcelManager->outputFile(
                    $phpExcelManager->getContent(
                        $report
                    ),
                    'QM_Remesas_Reporte_Agencia' . $user->getUsername() . ($date->format('d - m - Y'))
                );
        } else {
            return $this->redirectToRoute('reportes_admin_agencia_remeas');
        }

        $transferencias = $tnReporteTransferencia->getTransferencias();
        $result = [];
        foreach ($transferencias as $transferencia) {
            if ($transferencia instanceof TnTransferencia) {
                $result[] = [
                    'transaction_id' => $transferencia->getCodigo(),
                    'sender_user' => '', //Inicialmente vacío no se va a enviar.
                    'card_number' => $transferencia->getNumeroTarjeta(),
                    'card_holder_name' => $transferencia->getTitularTarjeta(),
                    'amount' => $transferencia->getMonto(),
                    'currency' => $transferencia->getMoneda()->getSimbolo()
                ];
            }
        }
    }


    /**
     * @Route("/print/agencias/dist/remesas", name="reportes_admin_agencia_dist_remeas_print")
     */
    public function remesasAgenciaDistPrintAction(Request $request, SessionInterface $sessionBag, TcPdfManager $tcPdfManager, TnFacturaRepository $tnFacturaRepository)
    {
        $user = $this->getUser();
        if ($sessionBag->has('reportes/admin_reporte_agencias_dist')) {
            ini_set('memory_limit', -1);
            ini_set('max_execution_time', 300);

            $reporte = $sessionBag->get('reportes/admin_reporte_agencias_dist');

            $facturas = $tnFacturaRepository->findFacturasAgenciaDistribucionAdminParams($reporte['search']);

            $result = [];
            foreach ($facturas as $factura) {
                if ($factura->getAgencia() != null) {
                    if (array_key_exists($factura->getAgencia()->getNombre(), $result)) {
                        $result[$factura->getAgencia()->getNombre()][] = $factura;
                    } else {
                        $result[$factura->getAgencia()->getNombre()] = [$factura];
                    }
                } elseif ($factura->getAgente() != null) {
                    if (array_key_exists($factura->getAgente()->getAgencia()->getNombre(), $result)) {
                        $result[$factura->getAgente()->getAgencia()->getNombre()][] = $factura;
                    } else {
                        $result[$factura->getAgente()->getAgencia()->getNombre()] = [$factura];
                    }
                }
            }
            //Exportando remesas a PDF
            $html = $this->renderView('backend/reportes/admin_agencias_pdf.html.twig', array(
                'facturas' => $result,
                'params' => $reporte['params']
            ));

            $date = new \DateTime('now');
            $tcPdfManager->getDocumentReport('QM_Remesas_' . $user->getUsername() . ($date->format('d - m - Y')), $html, 'I', ['title' => 'REPORTE DE AGENCIA - ' . $this->getUser()->getUsername()]);
        } else {
            return $this->redirectToRoute('reportes_admin_agencia_remeas');
        }

    }

    /**
     * @Route("/agentes/remesas", name="reportes_agentes_remeas")
     */
    public function remesasAgenteAction(Request $request, SessionInterface $sessionBag, TnFacturaRepository $tnFacturaRepository, TcPdfManager $tcPdfManager)
    {
        $user = $this->getUser();
        $securityChecker = $this->get('security.authorization_checker');
        $form = $this->createForm(ReporteAgentesRemesasType::class);
        $form->handleRequest($request);

        $result = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $params = [];
            $paramsShow = [];
            $params['fi'] = new \DateTime(date($data['fechaInicio']->format('Y/m/d') . " 00:00:00"));
            $paramsShow['fi'] = date($data['fechaInicio']->format('Y/m/d') . " 00:00:00");
            $params['ff'] = new \DateTime(date($data['fechaFin']->format('Y/m/d') . " 23:59:59"));
            $paramsShow['ff'] = date($data['fechaFin']->format('Y/m/d') . " 23:59:59");
            if (count($data['agente']) > 0) {
                $temp = [];
                $tempShow = [];
                foreach ($data['agente'] as $agente) {
                    $temp [] = $agente->getId();
                    $tempShow[] = $agente;
                }
                $params['agentes'] = $temp;
                $paramsShow['agentes'] = implode(', ', $tempShow);

            } else {
                $paramsShow['agentes'] = 'Todos';
            }
            if (count($data['estado']) > 0) {
                $tempShow = [];
                foreach ($data['estado'] as $estado) {
                    if ($estado == '01') {
                        $tempShow[] = "Pendiente";
                    } elseif ($estado == '02') {
                        $tempShow[] = "Aprobada";
                    } elseif ($estado == '03') {
                        $tempShow[] = "Distribución";
                    } elseif ($estado == '04') {
                        $tempShow[] = "Ejecutada";
                    } elseif ($estado == '05') {
                        $tempShow[] = "Cancelada";
                    }
                }
                $params['estados'] = $data['estado'];
                $paramsShow['estados'] = implode(', ', $tempShow);
            } else {
                $paramsShow['estados'] = 'Todos';
            }

            if ($securityChecker->isGranted("ROLE_AGENCIA")) {
                $facturas = $tnFacturaRepository->findFacturasAgenteAdminParams($params, $user->getAgencia()->getId());
            } else {
                $facturas = $tnFacturaRepository->findFacturasAgenteAdminParams($params);
            }
            foreach ($facturas as $factura) {
                if ($factura->getAgente() != null) {
                    if (array_key_exists($factura->getAgente()->getNombre(), $result)) {
                        $result[$factura->getAgente()->getNombre()][] = $factura;
                    } else {
                        $result[$factura->getAgente()->getNombre()] = [$factura];
                    }
                }
            }

            $sessionBag->set('reportes/admin_reporte_agentes', [
                'params' => $paramsShow,
                'facturas' => $result,
            ]);
        }

        if ($request->getMethod() == 'GET') {
            $sessionBag->remove('reportes/admin_reporte_agentes');
        }

        return $this->render('backend/reportes/admin_agentes.html.twig', [
            'form' => $form->createView(),
            'facturas' => $result
        ]);
    }

    /**
     * @Route("/print/agentes/remesas", name="reportes_agentes_remeas_print")
     */
    public function remesasAgentesPrintAction(Request $request, SessionInterface $sessionBag, TcPdfManager $tcPdfManager)
    {
        $user = $this->getUser();
        if ($sessionBag->has('reportes/admin_reporte_agentes')) {
            $reporte = $sessionBag->get('reportes/admin_reporte_agentes');
            //Exportando remesas a PDF
            $html = $this->renderView('backend/reportes/admin_agentes_pdf.html.twig', array(
                'facturas' => $reporte['facturas'],
                'params' => $reporte['params']
            ));

            $date = new \DateTime('now');
            if ($user->getFilePath()) {
                $tcPdfManager->getDocumentReport('QM_Remesas_' . $user->getUsername() . ($date->format('d - m - Y')), $html, 'I', ['avatar' => $user->getFilePath(), 'title' => 'REPORTE DE AGENTE - ' . $this->getUser()->getUsername()]);
            } else {
                $tcPdfManager->getDocumentReport('QM_Remesas_' . $user->getUsername() . ($date->format('d - m - Y')), $html, 'I', ['title' => 'REPORTE DE AGENTE - ' . $this->getUser()->getUsername()]);
            }
        } else {
            return $this->redirectToRoute('reportes_agentes_remeas');
        }

    }

    /**
     * @Route("/utilidades/remesas", name="reportes_utilidades_remeas")
     */
    public function utilidadesRemesasAction(Request $request, SessionInterface $sessionBag, TnFacturaRepository $tnFacturaRepository, TcPdfManager $tcPdfManager)
    {
        $user = $this->getUser();
        $securityChecker = $this->get('security.authorization_checker');
        $form = $this->createForm(ReporteAgentesRemesasType::class);
        $form->handleRequest($request);
        $result = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $params = [];
            $paramsShow = [];
            $params['fi'] = new \DateTime(date($data['fechaInicio']->format('Y/m/d') . " 00:00:00"));
            $paramsShow['fi'] = date($data['fechaInicio']->format('Y/m/d') . " 00:00:00");
            $params['ff'] = new \DateTime(date($data['fechaFin']->format('Y/m/d') . " 23:59:59"));
            $paramsShow['ff'] = date($data['fechaFin']->format('Y/m/d') . " 23:59:59");
            if (count($data['agente']) > 0) {
                $temp = [];
                $tempShow = [];
                foreach ($data['agente'] as $agente) {
                    $temp [] = $agente->getId();
                    $tempShow[] = $agente;
                }
                $params['agentes'] = $temp;
                $paramsShow['agentes'] = implode(', ', $tempShow);

            } else {
                $paramsShow['agentes'] = 'Todos';
            }
            if (count($data['estado']) > 0) {
                $tempShow = [];
                foreach ($data['estado'] as $estado) {
                    if ($estado == '01') {
                        $tempShow[] = "Pendiente";
                    } elseif ($estado == '02') {
                        $tempShow[] = "Aprobada";
                    } elseif ($estado == '03') {
                        $tempShow[] = "Distribución";
                    } elseif ($estado == '04') {
                        $tempShow[] = "Ejecutada";
                    } elseif ($estado == '05') {
                        $tempShow[] = "Cancelada";
                    }
                }
                $params['estados'] = $data['estado'];
                $paramsShow['estados'] = implode(', ', $tempShow);
            } else {
                $paramsShow['estados'] = 'Todos';
            }

            if ($securityChecker->isGranted("ROLE_AGENCIA")) {
                $facturas = $tnFacturaRepository->findUtilidadRemesaParams($params, 'Agencia', $user->getAgencia()->getId());
            } elseif ($securityChecker->isGranted("ROLE_AGENTE")) {
                $facturas = $tnFacturaRepository->findUtilidadRemesaParams($params, 'Agente', $user->getAgente()->getId());
            }

            foreach ($facturas as $factura) {
                if ($factura->getAgencia() != null) {
                    if ($factura->getAgente() != null) {
                        if (array_key_exists($factura->getAgente()->getNombre(), $result)) {
                            $result[$factura->getAgente()->getNombre()][] = $factura;
                        } else {
                            $result[$factura->getAgente()->getNombre()] = [$factura];
                        }
                    } else {
                        if (array_key_exists($factura->getAgencia()->getNombre(), $result)) {
                            $result[$factura->getAgencia()->getNombre()][] = $factura;
                        } else {
                            $result[$factura->getAgencia()->getNombre()] = [$factura];
                        }
                    }
                } elseif ($factura->getAgente() != null) {
                    if (array_key_exists($factura->getAgente()->getNombre(), $result)) {
                        $result[$factura->getAgente()->getNombre()][] = $factura;
                    } else {
                        $result[$factura->getAgente()->getNombre()] = [$factura];
                    }
                }
            }

            $sessionBag->set('reportes/admin_utilidad_remesas', [
                'params' => $paramsShow,
                'facturas' => $result,
            ]);
        }

        if ($request->getMethod() == 'GET') {
            $sessionBag->remove('reportes/admin_utilidad_remesas');
        }

        return $this->render('backend/reportes/utilidad_remsas.html.twig', [
            'facturas' => $result,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/print/utilidades/remesas", name="reportes_utilidades_remeas_print")
     */
    public function utilidadesRemesasPrintAction(Request $request, SessionInterface $sessionBag, TcPdfManager $tcPdfManager)
    {
        $user = $this->getUser();
        if ($sessionBag->has('reportes/admin_utilidad_remesas')) {
            $reporte = $sessionBag->get('reportes/admin_utilidad_remesas');
            //Exportando remesas a PDF
            $html = $this->renderView('backend/reportes/utilidad_pdf.html.twig', array(
                'facturas' => $reporte['facturas'],
                'params' => $reporte['params']
            ));

            $date = new \DateTime('now');
            if ($user->getFilePath()) {
                $tcPdfManager->getDocumentReport('QM_Remesas_' . $user->getUsername() . ($date->format('d - m - Y')), $html, 'I', ['avatar' => $user->getFilePath(), 'title' => 'REPORTE DE UTILIDAD - ' . $this->getUser()->getUsername()]);
            } else {
                $tcPdfManager->getDocumentReport('QM_Remesas_' . $user->getUsername() . ($date->format('d - m - Y')), $html, 'I', ['title' => 'REPORTE DE UTILIDAD - ' . $this->getUser()->getUsername()]);
            }
        } else {
            return $this->redirectToRoute('reportes_agentes_remeas');
        }

    }

    /**
     * @Route("/agente/utilidades/remesas", name="reportes_agente_utilidades_remeas")
     */
    public function agenteUtilidadesRemesasAction(Request $request, SessionInterface $sessionBag, TnFacturaRepository $tnFacturaRepository, TcPdfManager $tcPdfManager)
    {
        $user = $this->getUser();
        $form = $this->createForm(ReporteAgentesUtilidadType::class);
        $form->handleRequest($request);

        $facturas = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $params = [];
            $paramsShow = [];
            $params['fi'] = new \DateTime(date($data['fechaInicio']->format('Y/m/d') . " 00:00:00"));
            $paramsShow['fi'] = date($data['fechaInicio']->format('Y/m/d') . " 00:00:00");
            $params['ff'] = new \DateTime(date($data['fechaFin']->format('Y/m/d') . " 23:59:59"));
            $paramsShow['ff'] = date($data['fechaFin']->format('Y/m/d') . " 23:59:59");

            if (count($data['estado']) > 0) {
                $tempShow = [];
                foreach ($data['estado'] as $estado) {
                    if ($estado == '01') {
                        $tempShow[] = "Pendiente";
                    } elseif ($estado == '02') {
                        $tempShow[] = "Aprobada";
                    } elseif ($estado == '03') {
                        $tempShow[] = "Distribución";
                    } elseif ($estado == '04') {
                        $tempShow[] = "Ejecutada";
                    } elseif ($estado == '05') {
                        $tempShow[] = "Cancelada";
                    }
                }
                $params['estados'] = $data['estado'];
                $paramsShow['estados'] = implode(', ', $tempShow);
            } else {
                $paramsShow['estados'] = 'Todos';
            }

            $facturas = $tnFacturaRepository->findUtilidadRemesaParams($params, 'Agente', $user->getAgente()->getId());

            $sessionBag->set('reportes/agente_utilidad_remesas', [
                'params' => $paramsShow,
                'facturas' => $facturas
            ]);
        }

        if ($request->getMethod() == 'GET') {
            $sessionBag->remove('reportes/agente_utilidad_remesas');
        }

        return $this->render('backend/reportes/utilidad_agente_remsas.html.twig', [
            'form' => $form->createView(),
            'facturas' => $facturas
        ]);
    }

    /**
     * @Route("/print/agente/utilidades/remesas", name="reportes_agente_utilidades_remeas_print")
     */
    public function agenteUtilidadesRemesasPrintAction(Request $request, SessionInterface $sessionBag, TcPdfManager $tcPdfManager)
    {
        $user = $this->getUser();
        if ($sessionBag->has('reportes/agente_utilidad_remesas')) {
            $reporte = $sessionBag->get('reportes/agente_utilidad_remesas');
            //Exportando remesas a PDF
            //Exportando facturas a PDF
            $html = $this->renderView('backend/reportes/utilidad_agente_pdf.html.twig', array(
                'facturas' => $reporte['facturas'],
                'params' => $reporte['params']
            ));

            $date = new \DateTime('now');
            if ($user->getFilePath()) {
                $tcPdfManager->getDocumentReport('QM_Remesas_' . $user->getUsername() . ($date->format('d - m - Y')), $html, 'I', ['avatar' => $user->getFilePath(), 'title' => 'REPORTE DE UTILIDAD - ' . $this->getUser()->getUsername()]);
            } else {
                $tcPdfManager->getDocumentReport('QM_Remesas_' . $user->getUsername() . ($date->format('d - m - Y')), $html, 'I', ['title' => 'REPORTE DE UTILIDAD - ' . $this->getUser()->getUsername()]);
            }
        } else {
            return $this->redirectToRoute('reportes_agentes_remeas');
        }

    }

    /**
     * @Route("/admin_export/distribuidor/remesas", name="admin_export_distribuidor_remeas")
     */
    public function adminRemesasDistribuidorAction(Request $request, TnRemesaRepository $tnRemesaRepository, TcPdfManager $tcPdfManager)
    {
        $form = $this->createForm(ReporteAdminDistribuidorType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            if ($data['distribuidor']) {
                $distribuidor = $data['distribuidor'];
                return $this->redirectToRoute('admin_entrega_remesas_pendientes_export_pdf', ['id' => $distribuidor->getId()]);
            }
        }
        return $this->render('backend/reportes/admin_distribuidor_pendientes.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin_export_remesas/{id}/pdf", name="admin_entrega_remesas_pendientes_export_pdf", methods={"GET"})
     */
    public function adminExportRemesasPendientesPdf(Request $request, TnDistribuidor $tnDistribuidor, TnReporteEnvioRepository $tnReporteEnvioRepository, TnRemesaRepository $tnRemesaRepository, TcPdfManager $tcPdfManager)
    {
        $em = $this->getDoctrine()->getManager();
        $authorizationChecker = $this->get('security.authorization_checker');
        $remesas = [];
        if ($authorizationChecker->isGranted("ROLE_SUPER_ADMIN")) {
            $reporte = $tnReporteEnvioRepository->findLastReporteEnvio($tnDistribuidor);
            if ($reporte instanceof TnReporteEnvio) {
                $lastDate = $reporte->getLastDate();
                $remesas = $tnRemesaRepository->createQueryBuilder('tr')
                    ->join('tr.factura', 'factura')
                    ->join('factura.estado', 'estado')
                    ->where('estado.codigo IN (:estados)')
                    ->andWhere('tr.distribuidor = :dist')
                    ->andWhere('tr.entregada = false')
                    ->andWhere('factura.aprobadaAt > :aprov or factura.distribuidaAt > :aprov')
                    ->andWhere('tr.reporteEnvio IS NULL')
                    ->setParameter('dist', $tnDistribuidor)
                    ->setParameter('aprov', $lastDate)
                    ->setParameter('estados', [NmEstado::ESTADO_APROBADA, NmEstado::ESTADO_DISTRIBUCION])
                    ->orderBy('tr.created', "ASC")
                    ->getQuery()->getResult();
            } else {
                $remesas = $tnRemesaRepository->createQueryBuilder('tr')
                    ->join('tr.factura', 'factura')
                    ->join('factura.estado', 'estado')
                    ->where('estado.codigo IN (:estados)')
                    ->andWhere('tr.distribuidor = :dist')
                    ->andWhere('tr.entregada = false')
                    ->andWhere('tr.reporteEnvio IS NULL')
                    ->setParameter('dist', $tnDistribuidor)
                    ->setParameter('estados', [NmEstado::ESTADO_APROBADA, NmEstado::ESTADO_DISTRIBUCION])
                    ->orderBy('tr.created', "ASC")
                    ->getQuery()->getResult();
            }

            if (count($remesas) > 0) {
                $importe = 0;
                foreach ($remesas as $remesa) {
                    $importe += $remesa->getImporteEntregar();
                }
                //Creando el reporte de envio que se realizó
                $tnReporteEnvio = new TnReporteEnvio();
                $tnReporteEnvio->setTotalRemesas(count($remesas));
                $tnReporteEnvio->setLastDate(new \DateTime('now'));
                $tnReporteEnvio->setDistribuidor($tnDistribuidor);
                $tnReporteEnvio->setImporte($importe);
                $tnReporteEnvio->setToken((sha1(uniqid())));
                $em->persist($tnReporteEnvio);

                foreach ($remesas as $remesa) {
                    $remesa->setReporteEnvio($tnReporteEnvio);
                    $em->persist($remesa);
                }
                $em->flush();
            }
        }

        if (count($remesas) > 0) {

            $result = [];
            foreach ($remesas as $remesa) {
                $provDest = $remesa->getDestinatario()->getProvincia()->getName();
                if (array_key_exists($provDest, $result)) {
                    $result[$provDest][] = $remesa;
                } else {
                    $result[$provDest] = [$remesa];
                }
            }

            $date = new \DateTime('now');
            $tcPdfManager->getDocumentReportEnvio('QM_Remesas_' . $tnDistribuidor . "_" . ($date->format('d-m-Y')), $result, 'D', ['title' => 'REPORTE DE ENVIO', 'user' => $tnDistribuidor]);
        } else {
            $this->get('session')->getFlashBag()->add('warning', "El distribuidor no posee nuevas remesas pendientes en este momento.");

            return $this->redirectToRoute('admin_export_distribuidor_remeas');
        }
        throw new NotFoundException("Ha ocurrido un error al exportar las remesas pendientes.");
    }

    /**
     * @Route("/admin/reporte_envio/distribuidor", name="admin_reporte_envio_distribuidor")
     */
    public function reportesEnvioDistribuidor(Request $request)
    {
        $form = $this->createForm(ReporteAdminDistribuidorType::class);
        $form->handleRequest($request);

        return $this->render('backend/reportes/reportes_destinatario_admin.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/editar/{token}/reporte_envio", name="admin_reporte_envio_editar_remesas")
     * @ParamConverter("tnReporteEnvio", options={"mapping": {"token": "token"}})
     */
    public function reportesEnvioEditarRemsesas(Request $request, TnReporteEnvio $tnReporteEnvio)
    {
        $form = $this->createForm(ReporteAdminDistribuidorType::class);
        $form->handleRequest($request);

        //Creando el model de reportes para el formulario
        $editarEnvioModel = new EditarReporteEnvioModel();
        $editarEnvioModel->setReporte($tnReporteEnvio);
        $editarEnvioModel->setRemesas($tnReporteEnvio->getRemesas());
        //Creando el formuarlio con los datos obtenidos
        $form = $this->createForm(ReportesEnvioType::class, $reportesEnvioModel, [
            'action' => $this->generateUrl('multiple_reporte_export_pdf')
        ]);

        return $this->render('backend/reportes/reportes_destinatario_admin.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/reporte_envio/ajax", name="admin_reporte_envio_distribuidor_ajax")
     * @Method({"GET", "POST"})
     */
    public function ajaxReportesEnvioDistribuidor(Request $request, TnReporteEnvioRepository $tnReporteEnvioRepository)
    {
        if ($request->isXmlHttpRequest()) {
            $fin = new \DateTime();
            $init = clone $fin;
            $init->modify("-1week");

            $reportesEnvio = $tnReporteEnvioRepository->createQueryBuilder('tr')
                ->where('tr.distribuidor = :dist')
                ->andWhere('tr.created >= :ini and tr.created <= :fin')
                ->setParameter('dist', $request->get('distribuidor'))
                ->setParameter('ini', $init)
                ->setParameter('fin', $fin)
                ->orderBy('tr.created', "DESC")
                ->getQuery()->getResult();
            //Creando el model de reportes para el formulario
            $reportesEnvioModel = new ReporteEnvioModel();
            $reportesEnvioModel->setReportes($reportesEnvio);
            //Creando el formuarlio con los datos obtenidos
            $form = $this->createForm(ReportesEnvioType::class, $reportesEnvioModel, [
                'action' => $this->generateUrl('multiple_reporte_export_pdf')
            ]);
            //Guardando los reportes que obtuve al buscar en la sesion
            $resportesResult = array();
            foreach ($reportesEnvio as $reporte) {
                $resportesResult[] = $reporte->getId();
            }
            $this->get('session')->set('multi/reportes/export', $resportesResult);

            return $this->render('backend/reportes/reportes_destinatario.html.twig', array(
                'form' => $form->createView()
            ));
        }
    }

    /**
     * @Route("/mutiple_reporte/pdf", name="multiple_reporte_export_pdf", methods={"GET", "POST"})
     */
    public function multiExportRemesasReportePdf(Request $request, TnReporteEnvioRepository $tnReporteEnvioRepository, TcPdfManager $tcPdfManager)
    {
        $reportesResult = $this->get('session')->get('multi/reportes/export', array());
        $reportes = array();
        foreach ($reportesResult as $reporte) {
            $reportes[] = $tnReporteEnvioRepository->find($reporte);
        }

        $reportesEnvioModel = new ReporteEnvioModel();
        $reportesEnvioModel->setReportes($reportes);

        $form = $this->createForm(ReportesEnvioType::class, $reportesEnvioModel, [
            'action' => $this->generateUrl('multiple_reporte_export_pdf')
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $remesas = [];
            $reportesEnvio = $form->get('reportes')->getData();
            foreach ($reportesEnvio as $reporte) {
                foreach ($reporte->getRemesas() as $remesa) {
                    $remesas[] = $remesa;
                }
            }
            $result = [];
            foreach ($remesas as $remesa) {
                $provDest = $remesa->getDestinatario()->getProvincia()->getName();
                if (array_key_exists($provDest, $result)) {
                    $result[$provDest][] = $remesa;
                } else {
                    $result[$provDest] = [$remesa];
                }
            }

            $date = new \DateTime('now');
            $tcPdfManager->getDocumentReportEnvio('QM_Remesas_' . $reporte->getDistribuidor() . "_" . ($date->format('d-m-Y')), $result, 'D', ['title' => 'REPORTE DE ENVIO', 'date' => ['fecha' => $reporte->getCreated()->format(date('d/m/Y')), 'hora' => $reporte->getCreated()->format(date('H:i:s'))], 'user' => $reporte->getDistribuidor()]);
        }

        $formReporte = $this->createForm(ReporteAdminDistribuidorType::class);
        $formReporte->handleRequest($request);

        return $this->render('backend/reportes/reportes_destinatario_admin.html.twig', [
            'form' => $formReporte->createView(),
        ]);
    }

    /**
     * @Route("/agencias/costos", name="reportes_agencia_costos_remeas")
     */
    public function remesasAgenciaCostosAction(Request $request, SessionInterface $sessionBag, TnFacturaRepository $tnFacturaRepository)
    {
        $user = $this->getUser();
        $form = $this->createForm(ReporteAgentesRemesasType::class);
        $form->handleRequest($request);

        $result = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $params = [];
            $paramsShow = [];
            $params['fi'] = new \DateTime(date($data['fechaInicio']->format('Y/m/d') . " 00:00:00"));
            $paramsShow['fi'] = date($data['fechaInicio']->format('Y/m/d') . " 00:00:00");
            $params['ff'] = new \DateTime(date($data['fechaFin']->format('Y/m/d') . " 23:59:59"));
            $paramsShow['ff'] = date($data['fechaFin']->format('Y/m/d') . " 23:59:59");
            if (count($data['agente']) > 0) {
                $temp = [];
                $tempShow = [];
                foreach ($data['agente'] as $agente) {
                    $temp [] = $agente->getId();
                    $tempShow[] = $agente;
                }
                $params['agentes'] = $temp;
                $paramsShow['agentes'] = implode(', ', $tempShow);

            } else {
                $paramsShow['agentes'] = 'Todas';
            }
            $params['agencias'] = [$user->getAgencia()];//Para la agencia que esta logueada
            if (count($data['estado']) > 0) {
                $tempShow = [];
                foreach ($data['estado'] as $estado) {
                    if ($estado == '01') {
                        $tempShow[] = "Pendiente";
                    } elseif ($estado == '02') {
                        $tempShow[] = "Aprobada";
                    } elseif ($estado == '03') {
                        $tempShow[] = "Distribución";
                    } elseif ($estado == '04') {
                        $tempShow[] = "Ejecutada";
                    } elseif ($estado == '05') {
                        $tempShow[] = "Cancelada";
                    }
                }
                $params['estados'] = $data['estado'];
                $paramsShow['estados'] = implode(', ', $tempShow);
            } else {
                $paramsShow['estados'] = 'Todos';
            }

            $facturas = $tnFacturaRepository->findFacturasAgenciaAgentesParams($params);
            foreach ($facturas as $factura) {
                if ($factura->getAgente() != null) {
                    if (array_key_exists($factura->getAgente()->getNombre(), $result)) {
                        $result[$factura->getAgente()->getNombre()][] = $factura;
                    } else {
                        $result[$factura->getAgente()->getNombre()] = [$factura];
                    }
                } elseif ($factura->getAgencia() != null) {
                    if (array_key_exists($factura->getAgencia()->getNombre(), $result)) {
                        $result[$factura->getAgencia()->getNombre()][] = $factura;
                    } else {
                        $result[$factura->getAgencia()->getNombre()] = [$factura];
                    }
                }
            }

            $sessionBag->set('reportes/admin_reporte_agencia_costos', [
                'params' => $paramsShow,
                'facturas' => $result,
            ]);
        }

        if ($request->getMethod() == 'GET') {
            $sessionBag->remove('reportes/admin_reporte_agencia_costos');
        }


        return $this->render('backend/reportes/costos_agencias.html.twig', [
            'facturas' => $result,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/print/agencias/costos", name="reportes_agencia_costos_remeas_print")
     */
    public function remesasAgenciaCostosPrint(Request $request, SessionInterface $sessionBag, TcPdfManager $tcPdfManager)
    {
        $user = $this->getUser();
        if ($sessionBag->has('reportes/admin_reporte_agencia_costos')) {
            $reporte = $sessionBag->get('reportes/admin_reporte_agencia_costos');
            //Exportando facturas a PDF
            $html = $this->renderView('backend/reportes/costo_agencia_pdf.html.twig', array(
                'facturas' => $reporte['facturas'],
                'params' => $reporte['params']
            ));

            $date = new \DateTime('now');
            if ($user->getFilePath()) {
                $tcPdfManager->getDocumentReport('QM_Remesas_' . $user->getUsername() . ($date->format('d - m - Y')), $html, 'I', ['avatar' => $user->getFilePath(), 'title' => 'REPORTE DE AGENCIA - ' . $this->getUser()->getUsername()]);
            } else {
                $tcPdfManager->getDocumentReport('QM_Remesas_' . $user->getUsername() . ($date->format('d - m - Y')), $html, 'I', ['title' => 'REPORTE DE AGENCIA - ' . $this->getUser()->getUsername()]);
            }

        } else {
            return $this->redirectToRoute('reportes_agentes_remeas');
        }

    }
}
