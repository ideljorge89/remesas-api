<?php

namespace App\Controller\Backend;

use App\Entity\NmEstado;
use App\Entity\NmEstadoTransferencia;
use App\Entity\TnAgente;
use App\Entity\TnApoderadoFactura;
use App\Entity\TnDestinatario;
use App\Entity\TnDistribuidor;
use App\Entity\TnRepartidor;
use App\Entity\TnReporteEnvio;
use App\Entity\TnReporteTransferencia;
use App\Entity\TnTransferencia;
use App\Entity\TnUser;
use App\Form\Type\EnvioAdminRepartidorType;
use App\Form\Type\ReporteAdminAgenciasType;
use App\Form\Type\ReporteAdminApoderadosType;
use App\Form\Type\ReporteAdminDistribuidorType;
use App\Form\Type\ReporteAdminRemesasType;
use App\Form\Type\ReporteAgenciaApoderadosType;
use App\Form\Type\ReporteAgenciasTransferenciasType;
use App\Form\Type\ReporteAgentesRemesasType;
use App\Form\Type\ReporteAgentesTransfType;
use App\Form\Type\ReporteAgentesUtilidadType;
use App\Form\Type\ReporteAgtTransfType;
use App\Form\Type\ReporteDestinatarioType;
use App\Form\Type\ReporteDistribuidorRemesasType;
use App\Form\Type\ReportesEnvioTransferenciaType;
use App\Form\Type\ReportesEnvioType;
use App\Form\Type\TnEvidenciaTransferenciaType;
use App\Manager\PhpExcelManager;
use App\Manager\TcPdfManager;
use App\Model\EditarReporteEnvioModel;
use App\Model\ReporteEnvioModel;
use App\Model\ReporteEnvioTransferenciaModel;
use App\Repository\NmEstadoTransferenciaRepository;
use App\Repository\TnAgenteRepository;
use App\Repository\TnApoderadoFacturaRepository;
use App\Repository\TnDestinatarioRepository;
use App\Repository\TnFacturaRepository;
use App\Repository\TnRemesaRepository;
use App\Repository\TnReporteEnvioRepository;
use App\Repository\TnReporteTransferenciaRepository;
use App\Repository\TnTransferenciaRepository;
use App\Util\DirectoryNamerUtil;
use Liip\ImagineBundle\Exception\Config\Filter\NotFoundException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/backend/reportes/transferencia")
 * @package App\Controller\Backend
 */
class ReportesTransferenciaController extends AbstractController
{
    /**
     * @Route("/asignaciones/repartidor", name="envio_transferencias_asignaciones_repartidor")
     */
    public function asignacionesRepartidor(Request $request)
    {
        $form = $this->createForm(EnvioAdminRepartidorType::class);
        $form->handleRequest($request);

        return $this->render('backend/tn_transferencia/repartidor/envios_repartidor_admin.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/repartidor/asignaciones/ajax", name="envio_transferencias_asignaciones_repartidor_ajax")
     * @Method({"GET", "POST"})
     */
    public function ajaxAsignacionesRepartidor(Request $request, TnReporteTransferenciaRepository $tnReporteTransferenciaRepository)
    {
        if ($request->isXmlHttpRequest()) {
            $fin = new \DateTime();
            $init = clone $fin;
            $init->modify("-10week");

            $reportesEnvio = $tnReporteTransferenciaRepository->createQueryBuilder('trt')
                ->where('trt.repartidor = :reprt')
                ->andWhere('trt.created >= :ini and trt.created <= :fin')
                ->setParameter('reprt', $request->get('repartidor'))
                ->setParameter('ini', $init)
                ->setParameter('fin', $fin)
                ->orderBy('trt.created', "DESC")
                ->getQuery()->getResult();

            //Creando el model de reportes para el formulario
            $reportesEnvioModel = new ReporteEnvioTransferenciaModel();
            $reportesEnvioModel->setReportes($reportesEnvio);
            //Creando el formuarlio con los datos obtenidos
            $form = $this->createForm(ReportesEnvioTransferenciaType::class, $reportesEnvioModel, [
                'action' => $this->generateUrl('transferencia_multiple_reporte_export_excel')
            ]);

            $reportes = array();
            foreach ($reportesEnvio as $reporte) {
                $reportes[] = $reporte->getId();
            }

            $this->get('session')->set('multi_transferencia/reportes/export', $reportes);

            return $this->render('backend/tn_transferencia/repartidor/asignaciones_repartidor.html.twig', [
                'form' => $form->createView(),
                'repartidor' => $request->get('repartidor')
            ]);
        }
    }

    /**
     * @Route("/export/{id}/excel", name="envio_pendientes_repartidor_export_excel", methods={"GET"})
     */
    public function exportPendientesRepartidorExcel(Request $request, TnRepartidor $tnRepartidor, NmEstadoTransferenciaRepository $nmEstadoTransferenciaRepository, TnReporteTransferenciaRepository $tnReporteTransferenciaRepository, TnTransferenciaRepository $tnTransferenciaRepository, PhpExcelManager $phpExcelManager)
    {
        $em = $this->getDoctrine()->getManager();
        $tnUser = $this->getUser();
        $authorizationChecker = $this->get('security.authorization_checker');

        $em->getConnection()->beginTransaction();
        try {
            $transfAsignadas = [];
            if ($tnUser instanceof TnUser) {
                if ($authorizationChecker->isGranted("ROLE_SUPER_ADMIN")) {

                    $transfAsignadas = $tnTransferenciaRepository->createQueryBuilder('tr')
                        ->join('tr.estado', 'estado')
                        ->where('estado.codigo IN (:estados)')
                        ->andWhere('tr.reporteTransferencia IS NULL')
                        ->andWhere('tr.repartidor = :rpart')
                        ->setParameter('rpart', $tnRepartidor->getId())
                        ->setParameter('estados', [NmEstadoTransferencia::ESTADO_ASINGADA])
                        ->orderBy('tr.created', "ASC")
                        ->getQuery()->getResult();

                    if (count($transfAsignadas) > 0) {
                        $importe = 0;
                        foreach ($transfAsignadas as $transferencia) {
                            $importe += $transferencia->getImporte();
                        }
                        //Creando el reporte de envio que se realizó
                        $tnReporteTransferencia = new TnReporteTransferencia();
                        $tnReporteTransferencia->setTotalTransferencias(count($transfAsignadas));
                        $tnReporteTransferencia->setLastDate(new \DateTime('now'));
                        $tnReporteTransferencia->setImporte($importe);
                        $tnReporteTransferencia->setRepartidor($tnRepartidor);
                        $tnReporteTransferencia->setToken((sha1(uniqid())));
                        $em->persist($tnReporteTransferencia);

                        $nmEstadoEnviado = $nmEstadoTransferenciaRepository->findOneBy(['codigo' => NmEstadoTransferencia::ESTADO_ENVIADO]);
                        foreach ($transfAsignadas as $transferencia) {
                            $transferencia->setReporteTransferencia($tnReporteTransferencia);
                            $transferencia->setEstado($nmEstadoEnviado);//Cambiando el estado a enviada de la transferencia.
                            $em->persist($transferencia);
                        }

                        $em->flush();

                        $em->getConnection()->commit();
                    }
                }
            }

            if (count($transfAsignadas) > 0) {
                $result = [];
                foreach ($transfAsignadas as $transferencia) {
                    if ($transferencia instanceof TnTransferencia) {
                        $result[] = [
                            'transaction_id' => $transferencia->getCodigo(),
                            'sender_user' => '', //Inicialmente vacío no se va a enviar.
                            'card_number' => $transferencia->getNumeroTarjeta(),
                            'card_holder_name' => $transferencia->getTitularTarjeta(),
                            'phone' => $transferencia->getPhone() ? $transferencia->getPhone() : '-',
                            'amount' => $transferencia->getMonto(),
                            'currency' => $transferencia->getMoneda()->getSimbolo()
                        ];
                    }
                }

                $report = $phpExcelManager->exportDocumentoEnvioTransferencias($result);

                $fecha = new \DateTime('now');
                return
                    $phpExcelManager->outputFile(
                        $phpExcelManager->getContent(
                            $report
                        ),
                        'QM_Reporte_Transferencias_' . $fecha->format('d-m-Y') . "_" . $tnReporteTransferencia->getRepartidor()->getNombre()
                    );

            } else {
                $this->get('session')->getFlashBag()->add('warning', "Usted no posee nuevas transferencias pendientes en este momento, compruebe el listado de transferencias.");

                return $this->redirectToRoute('envio_transferencias_asignaciones_repartidor');
            }
        } catch (\Exception $e) {
            $em->getConnection()->rollback();
            $this->get('session')->getFlashBag()->add('warning', "Ha ocurrido un error al exportar las transferencias pendientes.");
            // Rollback the failed transaction attempt
            return $this->redirectToRoute('envio_transferencias_asignaciones_repartidor');
        }
    }

    /**
     * @Route("/add/{token}/evidencia", name="envio_transferencias_add_evidencia", methods={"GET","POST"}).
     * @ParamConverter("tnTransferencia", options={"mapping": {"token": "token"}})
     */
    public function addEvidencia(Request $request, TnTransferencia $tnTransferencia, DirectoryNamerUtil $directoryNamerUtil): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        //Buscando los archivos de las evidencias que tenía antes para compararlos al editar.
        $fileOldEvidencia = null;
        if ($tnTransferencia->getEvidencia() != null) {
            $fileOldEvidencia = $tnTransferencia->getEvidencia();
            $tnTransferencia->setEvidencia(new File($this->getParameter('evidencia_transferencia_path') . '/' . $tnTransferencia->getEvidencia()));
        }

        $form = $this->createForm(TnEvidenciaTransferenciaType::class, $tnTransferencia);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $errores = false;
            /** @var UploadedFile $brochureFile */
            $fileEvidData = $form->get('evidencia')->getData();

            $objectFile = $form->getData();
            if ($fileEvidData) {
                // Move the file to the directory where brochures are stored
                $newFilename = $directoryNamerUtil->getNamer("evidencia_" . $tnTransferencia->getToken() . "_", $fileEvidData);
                try {
                    $objectFile->setEvidencia($newFilename);
                    $fileEvidData->move(
                        $directoryNamerUtil->getDocumentDirPath($objectFile),
                        $newFilename
                    );

                    //Actualizando  y guardando la transferencia.
                    $entityManager->persist($tnTransferencia);

                    //Verificando si tenía algo antes y eliminarlo
                    if ($objectFile->getEvidencia() != $fileOldEvidencia) {
                        @unlink($this->getParameter('evidencia_transferencia_path') . '/' . $fileOldEvidencia);

                    }

                } catch (FileException $e) {
                    $errores = true;
                    $form->get('evidencia')->addError(new FormError("Error en el fichero: " . $e->getMessage()));

                }
            } else {
                if ($fileOldEvidencia != null) {
                    if (file_exists($this->getParameter('evidencia_transferencia_path') . '/' . $fileOldEvidencia)) {
                        $objectFile->setEvidencia($fileOldEvidencia);
                        $entityManager->persist($objectFile);
                    }
                } else {
                    $errores = true;
                    $form->get('evidencia')->addError(new FormError("Error en el fichero: Requerido"));
                }
            }

            if (!$errores) {
                $entityManager->flush();
                return $this->redirectToRoute('tn_transferencia_index');
            } else {
                $form->isValid();
            }
        }


        return $this->render('backend/tn_transferencia/editar_evidencia.html.twig', [
            'tnTransferencia' => $tnTransferencia,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/evidencia/show/{id}", name="envio_transferencias_evidencia_show", methods={"GET"})
     */
    public function showEvidenciaTransferencia(TnTransferencia $tnTransferencia): Response
    {
        $fileEvidenciaData = null;
        if ($tnTransferencia->getEvidencia() != null) {
            $fileEvidenciaData = new File($this->getParameter('evidencia_transferencia_path') . '/' . $tnTransferencia->getEvidencia());
        }

        return $this->render('backend/tn_transferencia/show_evidencia.html.twig', [
            'tn_transferencia' => $tnTransferencia,
            'fileEvidenciaData' => $fileEvidenciaData
        ]);
    }

    /**
     * @Route("/export_transferencias/excel", name="envio_transferencias_pendientes_export_excel", methods={"GET"})
     */
    public function exportTransferenciasPendientesExcel(Request $request, NmEstadoTransferenciaRepository $nmEstadoTransferenciaRepository, TnReporteTransferenciaRepository $tnReporteTransferenciaRepository, TnTransferenciaRepository $tnTransferenciaRepository, PhpExcelManager $phpExcelManager)
    {
        $em = $this->getDoctrine()->getManager();
        $tnUser = $this->getUser();
        $authorizationChecker = $this->get('security.authorization_checker');
        $transferencias = [];
        if ($tnUser instanceof TnUser) {
            if ($authorizationChecker->isGranted("ROLE_SUPER_ADMIN")) {
                $reporte = $tnReporteTransferenciaRepository->findLastReporteEnvioTransferencia();

                if ($reporte instanceof TnReporteTransferencia) {
                    $lastDate = $reporte->getLastDate();
                    $transferencias = $tnTransferenciaRepository->createQueryBuilder('tr')
                        ->join('tr.estado', 'estado')
                        ->where('estado.codigo IN (:estados)')
                        ->andWhere('tr.reporteTransferencia IS NULL')
                        ->andWhere('tr.created > :lastD')
                        ->setParameter('lastD', $lastDate)
                        ->setParameter('estados', [NmEstadoTransferencia::ESTADO_ASINGADA])
                        ->orderBy('tr.created', "ASC")
                        ->getQuery()->getResult();
                } else {
                    $transferencias = $tnTransferenciaRepository->createQueryBuilder('tr')
                        ->join('tr.estado', 'estado')
                        ->where('estado.codigo IN (:estados)')
                        ->andWhere('tr.reporteTransferencia IS NULL')
                        ->setParameter('estados', [NmEstadoTransferencia::ESTADO_ASINGADA])
                        ->orderBy('tr.created', "ASC")
                        ->getQuery()->getResult();
                }

                if (count($transferencias) > 0) {
                    $importe = 0;
                    foreach ($transferencias as $transferencia) {
                        $importe += $transferencia->getImporte();
                    }
                    //Creando el reporte de envio que se realizó
                    $tnReporteTransferencia = new TnReporteTransferencia();
                    $tnReporteTransferencia->setTotalTransferencias(count($transferencias));
                    $tnReporteTransferencia->setLastDate(new \DateTime('now'));
                    $tnReporteTransferencia->setImporte($importe);
                    $tnReporteTransferencia->setToken((sha1(uniqid())));
                    $em->persist($tnReporteTransferencia);

                    $nmEstadoEnviado = $nmEstadoTransferenciaRepository->findOneBy(['codigo' => NmEstadoTransferencia::ESTADO_ENVIADO]);
                    foreach ($transferencias as $transferencia) {
                        $transferencia->setReporteTransferencia($tnReporteTransferencia);
                        $transferencia->setEstado($nmEstadoEnviado);//Cambiando el estado a enviada de la transferencia.
                        $em->persist($transferencia);
                    }
                    $em->flush();
                }
            }
        }

        if (count($transferencias) > 0) {
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

            $report = $phpExcelManager->exportDocumentoEnvioTransferencias($result);

            $fecha = new \DateTime('now');
            return
                $phpExcelManager->outputFile(
                    $phpExcelManager->getContent(
                        $report
                    ),
                    'QM_Reporte_Transferencias_' . $fecha->format('d-m-Y')
                );

        } else {
            $this->get('session')->getFlashBag()->add('warning', "Usted no posee nuevas transferencias pendientes en este momento, compruebe el listado de transferencias.");

            return $this->redirectToRoute('transferencias_reportes_envio_index');
        }

        throw new NotFoundException("Ha ocurrido un error al exportar las transferencias pendientes.");
    }

    /**
     * @Route("/reportes/envio", name="transferencias_reportes_envio_index", methods={"GET", "POST"})
     */
    public function reportesEnvioTransferencias(Request $request, TnReporteTransferenciaRepository $tnReporteTransferenciaRepository, PhpExcelManager $phpExcelManager): Response
    {
        $authorizationChecker = $this->get('security.authorization_checker');
        $tnUser = $this->getUser();

        if ($tnUser instanceof TnUser) {
            if ($authorizationChecker->isGranted("ROLE_SUPER_ADMIN")) {
                $reportesEnvio = $tnReporteTransferenciaRepository->createQueryBuilder('tr')
                    ->orderBy('tr.created', "DESC")
                    ->getQuery()->getResult();
            }
        }

        //Creando el model de reportes para el formulario
        $reportesEnvioModel = new ReporteEnvioTransferenciaModel();
        $reportesEnvioModel->setReportes($reportesEnvio);
        //Creando el formuarlio con los datos obtenidos
        $form = $this->createForm(ReportesEnvioTransferenciaType::class, $reportesEnvioModel, [
            'action' => $this->generateUrl('transferencia_multiple_reporte_export_excel')
        ]);

        $reportes = array();
        foreach ($reportesEnvio as $reporte) {
            $reportes[] = $reporte->getId();
        }

        $this->get('session')->set('multi_transferencia/reportes/export', $reportes);

        return $this->render('backend/reportes/transferencias/reportes_envio.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/mutiple_reporte/pdf", name="transferencia_multiple_reporte_export_excel", methods={"GET", "POST"})
     */
    public function multiExportTransferenciasReporteExcel(Request $request, ValidatorInterface $validator, TnReporteTransferenciaRepository $tnReporteTransferenciaRepository, PhpExcelManager $phpExcelManager)
    {
        $reportesResult = $this->get('session')->get('multi_transferencia/reportes/export', array());
        $reportes = array();
        foreach ($reportesResult as $reporte) {
            $reportes[] = $tnReporteTransferenciaRepository->find($reporte);
        }

        //Creando el model de reportes para el formulario
        $reportesEnvioModel = new ReporteEnvioTransferenciaModel();
        $reportesEnvioModel->setReportes($reportes);
        //Creando el formuarlio con los datos obtenidos
        $form = $this->createForm(ReportesEnvioTransferenciaType::class, $reportesEnvioModel, [
            'action' => $this->generateUrl('transferencia_multiple_reporte_export_excel')
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $result = [];
            $reportesEnvio = $form->get('reportes')->getData();
            $repartidor = '';
            foreach ($reportesEnvio as $reporte) {
                foreach ($reporte->getTransferencias() as $transferencia) {
                    if ($transferencia instanceof TnTransferencia) {
                        $result[] = [
                            'transaction_id' => $transferencia->getCodigo(),
                            'sender_user' => '', //Inicialmente vacío no se va a enviar.
                            'card_number' => $transferencia->getNumeroTarjeta(),
                            'card_holder_name' => $transferencia->getTitularTarjeta(),
                            'phone' => $transferencia->getPhone() ? $transferencia->getPhone() : '-',
                            'amount' => $transferencia->getMonto(),
                            'currency' => $transferencia->getMoneda()->getSimbolo()
                        ];
                    }
                }
                $repartidor = $reporte->getRepartidor()->getNombre();
            }


            $report = $phpExcelManager->exportDocumentoEnvioTransferencias($result);

            $fecha = new \DateTime('now');
            return
                $phpExcelManager->outputFile(
                    $phpExcelManager->getContent(
                        $report
                    ),
                    'QM_Reporte_Transferencias_' . $fecha->format('d-m-Y') . "_" . $repartidor
                );

        }

        return $this->redirectToRoute('transferencias_reportes_envio_index');
    }

    /**
     *
     * @Route("/show/{token}/reporte", name="transferencias_entrega_reporte_envio_show")
     * @ParamConverter("tnReporteTransferencia", options={"mapping": {"token": "token"}})
     * @Method("GET")
     */
    public function detallesReporteEnvio(TnReporteTransferencia $tnReporteTransferencia)
    {
        return $this->render('backend/reportes/transferencias/reporte_show.html.twig', array(
            'tnReporteTransferencia' => $tnReporteTransferencia,
        ));
    }


    /**
     * @Route("/transferecias_reporte/{token}/pdf", name="envio_transferencias_reporte_export_pdf", methods={"GET"})
     * @ParamConverter("tnReporteTransferencia", options={"mapping": {"token": "token"}})
     */
    public function exportTransferenciasReportePdf(Request $request, TnReporteTransferencia $tnReporteTransferencia, PhpExcelManager $phpExcelManager): Response
    {
        $transferencias = $tnReporteTransferencia->getTransferencias();
        $result = [];
        foreach ($transferencias as $transferencia) {
            if ($transferencia instanceof TnTransferencia) {
                $result[] = [
                    'transaction_id' => $transferencia->getCodigo(),
                    'sender_user' => '', //Inicialmente vacío no se va a enviar.
                    'card_number' => $transferencia->getNumeroTarjeta(),
                    'card_holder_name' => $transferencia->getTitularTarjeta(),
                    'phone' => $transferencia->getPhone() ? $transferencia->getPhone() : '-',
                    'amount' => $transferencia->getMonto(),
                    'currency' => $transferencia->getMoneda()->getSimbolo()
                ];
            }
        }

        $report = $phpExcelManager->exportDocumentoEnvioTransferencias($result);

        return
            $phpExcelManager->outputFile(
                $phpExcelManager->getContent(
                    $report
                ),
                'QM_Reporte_Transferencias_' . $tnReporteTransferencia->getCreated()->format('d-m-Y') . "_" . $tnReporteTransferencia->getRepartidor()->getNombre()
            );
//        foreach ($transferencias as $transferencia) {
//            if ($transferencia instanceof TnTransferencia) {
//                $result[] = [
//                    'fecha' => $transferencia->getCreated()->format('d/m/Y'),
//                    'codigo' => $transferencia->getCodigo(),
//                    'titular_tarjeta' => $transferencia->getTitularTarjeta(),
//                    'tarjeta' => $transferencia->getNumeroTarjeta(),
//                    'importe' => $transferencia->getImporte(),
//                    'moneda' => $transferencia->getMoneda()->getSimbolo()
//                ];
//            }
//        }
//
//        //Exportando facturas a PDF
//        $html = $this->renderView('backend/reportes/transferencias/transferencias_pdf.html.twig', array(
//            'result' => $result
//        ));
//
//        $date = new \DateTime('now');
//        $tcPdfManager->getDocumentReport('QM_Transferencias_' . $tnReporteTransferencia->getToken() . ($date->format('d - m - Y')), $html, 'I', ['title' => 'REPORTE DE TRANSFERENCIAS - ' . $this->getUser()->getUsername()]);
    }

    /**
     * @Route("/transferecias_desasociar/{token}", name="envio_transferencias_reporte_desasociar", methods={"GET"})
     * @ParamConverter("tnReporteTransferencia", options={"mapping": {"token": "token"}})
     */
    public function desasociarTransferenciasReporte(Request $request, TnReporteTransferencia $tnReporteTransferencia, PhpExcelManager $phpExcelManager): Response
    {
        $transferencias = $tnReporteTransferencia->getTransferencias();
        $em = $this->getDoctrine()->getManager();

        $estadoPendiente = $em->getRepository(NmEstadoTransferencia::class)->findOneBy(['codigo' => NmEstadoTransferencia::ESTADO_PENDIENTE]);

        $em->getConnection()->beginTransaction();
        try {
            foreach ($transferencias as $transferencia) {
                if ($transferencia instanceof TnTransferencia) {
                    $transferencia->setEstado($estadoPendiente);
                    $transferencia->setReporteTransferencia(null);
                    $transferencia->setRepartidor(null);
                    $em->persist($transferencia);

                }
            }

            $em->flush();

            $em->getConnection()->commit();

            $this->get('session')->getFlashBag()->add('info', "Transferencias desasociadas correctamente.");

            return $this->redirectToRoute('envio_transferencias_asignaciones_repartidor');

        } catch (\Exception $e) {
            $em->getConnection()->rollback();
            $this->get('session')->getFlashBag()->add('warning', "Ha ocurrido un error al desasociar las transferencias pendientes.");
            // Rollback the failed transaction attempt
            return $this->redirectToRoute('envio_transferencias_asignaciones_repartidor');
        }
    }

    /**
     * @Route("/admin/agencias/transferencias", name="reportes_admin_agencia_transferencias")
     */
    public function remesasAgenciaAction(Request $request, SessionInterface $sessionBag, TnTransferenciaRepository $tnTransferenciaRepository)
    {
        ini_set('memory_limit', -1);
        ini_set('max_execution_time', 300);

        $form = $this->createForm(ReporteAgenciasTransferenciasType::class);
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
                        $tempShow[] = "Enviada";
                    } elseif ($estado == '03') {
                        $tempShow[] = "Cancelada";
                    }
                }
                $params['estados'] = $data['estado'];
                $paramsShow['estados'] = implode(', ', $tempShow);
            } else {
                $paramsShow['estados'] = 'Todos';
            }

            $transferencias = $tnTransferenciaRepository->findTransfenciasAgenciaAdminParams($params);

            foreach ($transferencias as $transferencia) {
                if ($transferencia->getAgencia() != null) {
                    if (array_key_exists($transferencia->getAgencia()->getNombre(), $result)) {
                        $result[$transferencia->getAgencia()->getNombre()][] = $transferencia;
                    } else {
                        $result[$transferencia->getAgencia()->getNombre()] = [$transferencia];
                    }
                } elseif ($transferencia->getAgente() != null) {
                    if (array_key_exists($transferencia->getAgente()->getAgencia()->getNombre(), $result)) {
                        $result[$transferencia->getAgente()->getAgencia()->getNombre()][] = $transferencia;
                    } else {
                        $result[$transferencia->getAgente()->getAgencia()->getNombre()] = [$transferencia];
                    }
                }
            }

            $sessionBag->set('reportes/admin_reporte_agencias_transf', [
                'params' => $paramsShow,
                'search' => $params
            ]);
        }

        if ($request->getMethod() == 'GET') {
            $sessionBag->remove('reportes/admin_reporte_agencias_transf');
        }
        return $this->render('backend/reportes/transferencias/agencias_transfererencias.html.twig', [
            'form' => $form->createView(),
            'transferencias' => $result
        ]);
    }

    /**
     * @Route("/print/agencias/remesas", name="reportes_admin_agencia_transferencias_print")
     */
    public function remesasAgenciaPrintAction(Request $request, SessionInterface $sessionBag, TcPdfManager $tcPdfManager, TnTransferenciaRepository $tnTransferenciaRepository)
    {
        $user = $this->getUser();
        if ($sessionBag->has('reportes/admin_reporte_agencias_transf')) {
            ini_set('memory_limit', -1);
            ini_set('max_execution_time', 300);

            $reporte = $sessionBag->get('reportes/admin_reporte_agencias_transf');

            $transferencias = $tnTransferenciaRepository->findTransfenciasAgenciaAdminParams($reporte['search']);

            $result = [];
            foreach ($transferencias as $transferencia) {
                if ($transferencia->getAgencia() != null) {
                    if (array_key_exists($transferencia->getAgencia()->getNombre(), $result)) {
                        $result[$transferencia->getAgencia()->getNombre()][] = $transferencia;
                    } else {
                        $result[$transferencia->getAgencia()->getNombre()] = [$transferencia];
                    }
                } elseif ($transferencia->getAgente() != null) {
                    if (array_key_exists($transferencia->getAgente()->getAgencia()->getNombre(), $result)) {
                        $result[$transferencia->getAgente()->getAgencia()->getNombre()][] = $transferencia;
                    } else {
                        $result[$transferencia->getAgente()->getAgencia()->getNombre()] = [$transferencia];
                    }
                }
            }
            //Exportando transferencias a PDF
            $html = $this->renderView('backend/reportes/transferencias/admin_agencias_transf_pdf.html.twig', array(
                'transferencias' => $result,
                'params' => $reporte['params']
            ));

            $date = new \DateTime('now');
            $tcPdfManager->getDocumentReport('QM_Transferencias_' . $user->getUsername() . ($date->format('d - m - Y')), $html, 'I', ['title' => 'REPORTE DE TRANSFERENCIAS - ' . $this->getUser()->getUsername()]);
        } else {
            return $this->redirectToRoute('reportes_admin_agencia_transferencias');
        }
    }

    /**
     * @Route("/agencias/costos", name="reportes_agencia_costos_transferencias")
     */
    public function transfererenciasAgenciaCostosAction(Request $request, SessionInterface $sessionBag, TnTransferenciaRepository $tnTransferenciaRepository)
    {
        $user = $this->getUser();
        $form = $this->createForm(ReporteAgentesTransfType::class);
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
            $params['agencias'] = [$user->getAgencia()];//Para la agencia que esta logueada
            if (count($data['estado']) > 0) {
                $tempShow = [];
                foreach ($data['estado'] as $estado) {
                    if ($estado == '01') {
                        $tempShow[] = "Pendiente";
                    } elseif ($estado == '02') {
                        $tempShow[] = "Enviada";
                    } elseif ($estado == '03') {
                        $tempShow[] = "Cancelada";
                    }
                }
                $params['estados'] = $data['estado'];
                $paramsShow['estados'] = implode(', ', $tempShow);
            } else {
                $paramsShow['estados'] = 'Todos';
            }

            $transferencias = $tnTransferenciaRepository->findTransferenciasAgenciaAgentesParams($params);

            foreach ($transferencias as $transferencia) {
                if ($transferencia->getAgente() != null) {
                    if (array_key_exists($transferencia->getAgente()->getNombre(), $result)) {
                        $result[$transferencia->getAgente()->getNombre()][] = $transferencia;
                    } else {
                        $result[$transferencia->getAgente()->getNombre()] = [$transferencia];
                    }
                } elseif ($transferencia->getAgencia() != null) {
                    if (array_key_exists($transferencia->getAgencia()->getNombre(), $result)) {
                        $result[$transferencia->getAgencia()->getNombre()][] = $transferencia;
                    } else {
                        $result[$transferencia->getAgencia()->getNombre()] = [$transferencia];
                    }
                }
            }

            $sessionBag->set('reportes/admin_reporte_agencia_costos_transf', [
                'params' => $paramsShow,
                'transferencias' => $result,
            ]);
        }

        if ($request->getMethod() == 'GET') {
            $sessionBag->remove('reportes/admin_reporte_agencia_costos_transf');
        }


        return $this->render('backend/reportes/transferencias/transf_costos_agencias.html.twig', [
            'transferencias' => $result,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/print/costos/agencias", name="reportes_admin_costos_agencia_transferencias_print")
     */
    public function transferenciasAgenciaAgenciasPrintAction(Request $request, SessionInterface $sessionBag, TcPdfManager $tcPdfManager)
    {
        $user = $this->getUser();
        if ($sessionBag->has('reportes/admin_reporte_agencia_costos_transf')) {
            ini_set('memory_limit', -1);
            ini_set('max_execution_time', 300);

            $reporte = $sessionBag->get('reportes/admin_reporte_agencia_costos_transf');

            //Exportando transferencias a PDF
            $html = $this->renderView('backend/reportes/transferencias/transf_agencias_costos_pdf.html.twig', array(
                'transferencias' => $reporte['transferencias'],
                'params' => $reporte['params']
            ));

            $date = new \DateTime('now');
            $tcPdfManager->getDocumentReport('QM_Transferencias_' . $user->getUsername() . ($date->format('d - m - Y')), $html, 'I', ['title' => 'TRANSFERENCIAS AGENCIA - ' . $this->getUser()->getUsername()]);
        } else {
            return $this->redirectToRoute('reportes_agencia_costos_transferencias');
        }
    }

    /**
     * @Route("/agente/costos", name="reportes_agente_costos_transferencias")
     */
    public function transfererenciasAgenteCostosAction(Request $request, SessionInterface $sessionBag, TnTransferenciaRepository $tnTransferenciaRepository)
    {
        $user = $this->getUser();
        $form = $this->createForm(ReporteAgtTransfType::class);
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

            $params['agentes'] = [$user->getAgente()];//Para el agente que esta logueado
            if (count($data['estado']) > 0) {
                $tempShow = [];
                foreach ($data['estado'] as $estado) {
                    if ($estado == '01') {
                        $tempShow[] = "Pendiente";
                    } elseif ($estado == '02') {
                        $tempShow[] = "Enviada";
                    } elseif ($estado == '03') {
                        $tempShow[] = "Cancelada";
                    }
                }
                $params['estados'] = $data['estado'];
                $paramsShow['estados'] = implode(', ', $tempShow);
            } else {
                $paramsShow['estados'] = 'Todos';
            }

            $transferencias = $tnTransferenciaRepository->findTransferenciasAgenteAgentesParams($params);

            foreach ($transferencias as $transferencia) {
                if ($transferencia->getAgente() != null) {
                    if (array_key_exists($transferencia->getAgente()->getNombre(), $result)) {
                        $result[$transferencia->getAgente()->getNombre()][] = $transferencia;
                    } else {
                        $result[$transferencia->getAgente()->getNombre()] = [$transferencia];
                    }
                }
            }

            $sessionBag->set('reportes/admin_reporte_agente_costos_transf', [
                'params' => $paramsShow,
                'transferencias' => $result,
            ]);
        }

        if ($request->getMethod() == 'GET') {
            $sessionBag->remove('reportes/admin_reporte_agente_costos_transf');
        }


        return $this->render('backend/reportes/transferencias/transf_costos_agente.html.twig', [
            'transferencias' => $result,
            'form' => $form->createView()
        ]);
    }


    /**
     * @Route("/print/costos/agentes", name="reportes_admin_costos_agente_transferencias_print")
     */
    public function transferenciasAgentePrintAction(Request $request, SessionInterface $sessionBag, TcPdfManager $tcPdfManager)
    {
        $user = $this->getUser();
        if ($sessionBag->has('reportes/admin_reporte_agente_costos_transf')) {
            ini_set('memory_limit', -1);
            ini_set('max_execution_time', 300);

            $reporte = $sessionBag->get('reportes/admin_reporte_agente_costos_transf');

            //Exportando transferencias a PDF
            $html = $this->renderView('backend/reportes/transferencias/transf_agente_costos_pdf.html.twig', array(
                'transferencias' => $reporte['transferencias'],
                'params' => $reporte['params']
            ));

            $date = new \DateTime('now');
            $tcPdfManager->getDocumentReport('QM_Transferencias_' . $user->getUsername() . ($date->format('d - m - Y')), $html, 'I', ['title' => 'TRANSFERENCIAS AGENTE - ' . $this->getUser()->getUsername()]);
        } else {
            return $this->redirectToRoute('reportes_agente_costos_transferencias');
        }
    }

    /**
     * @Route("/agencias/agentes", name="reportes_agencia_agentes_transferencias")
     */
    public function transfererenciasAgenciaAgentesAction(Request $request, SessionInterface $sessionBag, TnTransferenciaRepository $tnTransferenciaRepository)
    {
        $user = $this->getUser();
        $form = $this->createForm(ReporteAgentesTransfType::class);
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

            $params['agencias'] = [$user->getAgencia()];//Para la agencia que esta logueada

            if (count($data['estado']) > 0) {
                $tempShow = [];
                foreach ($data['estado'] as $estado) {
                    if ($estado == '01') {
                        $tempShow[] = "Pendiente";
                    } elseif ($estado == '02') {
                        $tempShow[] = "Enviada";
                    } elseif ($estado == '03') {
                        $tempShow[] = "Cancelada";
                    }
                }
                $params['estados'] = $data['estado'];
                $paramsShow['estados'] = implode(', ', $tempShow);
            } else {
                $paramsShow['estados'] = 'Todos';
            }

            $transferencias = $tnTransferenciaRepository->findTransferenciasAgenciaAgentesCobroParams($params);

            foreach ($transferencias as $transferencia) {
                if ($transferencia->getAgente() != null) {
                    if (array_key_exists($transferencia->getAgente()->getNombre(), $result)) {
                        $result[$transferencia->getAgente()->getNombre()][] = $transferencia;
                    } else {
                        $result[$transferencia->getAgente()->getNombre()] = [$transferencia];
                    }
                }
            }

            $sessionBag->set('reportes/admin_reporte_agencia_agentes_transf', [
                'params' => $paramsShow,
                'transferencias' => $result,
            ]);
        }

        if ($request->getMethod() == 'GET') {
            $sessionBag->remove('reportes/admin_reporte_agencia_agentes_transf');
        }


        return $this->render('backend/reportes/transferencias/transf_agencia_agentes.html.twig', [
            'transferencias' => $result,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/print/agencia/agentes", name="reportes_admin_agencia_agentes_transferencias_print")
     */
    public function transferenciasAgenciaAgentesPrintAction(Request $request, SessionInterface $sessionBag, TcPdfManager $tcPdfManager)
    {
        $user = $this->getUser();
        if ($sessionBag->has('reportes/admin_reporte_agencia_agentes_transf')) {
            ini_set('memory_limit', -1);
            ini_set('max_execution_time', 300);

            $reporte = $sessionBag->get('reportes/admin_reporte_agencia_agentes_transf');

            //Exportando transferencias a PDF
            $html = $this->renderView('backend/reportes/transferencias/transf_agencia_agentes_pdf.html.twig', array(
                'transferencias' => $reporte['transferencias'],
                'params' => $reporte['params']
            ));

            $date = new \DateTime('now');
            $tcPdfManager->getDocumentReport('QM_Transferencias_Agentes_' . $user->getUsername() . ($date->format('d - m - Y')), $html, 'I', ['title' => 'TRANSFERENCIAS AGENTES - ' . $this->getUser()->getUsername()]);
        } else {
            return $this->redirectToRoute('reportes_agencia_agentes_transferencias');
        }
    }
}
