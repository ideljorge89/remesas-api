<?php

namespace App\Controller\Backend\Nomencladores;

use App\Entity\NmEstado;
use App\Entity\TnRemesa;
use App\Entity\TnReporteEnvio;
use App\Entity\TnUser;
use App\Form\Type\RemesasSerachType;
use App\Manager\TcPdfManager;
use App\Repository\NmEstadoRepository;
use App\Repository\TnAgenteRepository;
use App\Repository\TnFacturaRepository;
use App\Repository\TnRemesaRepository;
use App\Repository\TnReporteEnvioRepository;
use Knp\Component\Pager\PaginatorInterface;
use Liip\ImagineBundle\Exception\Config\Filter\NotFoundException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @Route("/entrega/distribuidores")
 */
class TnRemesaController extends AbstractController
{
    /**
     * @Route("/", name="entrega_distribuidor_index", methods={"GET"})
     */
    public function index(TnRemesaRepository $tnRemesaRepository, AuthorizationCheckerInterface $authorizationChecker, PaginatorInterface $paginator): Response
    {
        $tnUser = $this->getUser();
        if ($tnUser instanceof TnUser) {
            if ($authorizationChecker->isGranted("ROLE_DISTRIBUIDOR")) {
                $query = $tnRemesaRepository->createQueryBuilder('tr')
                    ->join('tr.factura', 'factura')
                    ->join('factura.estado', 'estado')
                    ->where('estado.codigo IN (:estados) and tr.distribuidor = :dist')
//                    ->andWhere('remesa.distribuidor = :dist')
                    ->setParameter('dist', $tnUser->getDistribuidor())
                    ->setParameter('estados', [NmEstado::ESTADO_APROBADA, NmEstado::ESTADO_DISTRIBUCION, NmEstado::ESTADO_ENTREGADA, NmEstado::ESTADO_CANCELADA])
                    ->orderBy('tr.created', "DESC")
                    ->getQuery();

            }
        }

        $form = $this->createForm(RemesasSerachType::class);

        $request = $this->get('request_stack')->getCurrentRequest();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            100
        );

        return $this->render('backend/tn_remesa/index.html.twig', [
            'remesas' => $pagination,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/advanced_search", name="tn_remesas_advanced_search")
     * @Method({"POST"})
     */
    public function advancedSearch(Request $request, TnRemesaRepository $remesaRepository)
    {
        $tnUser = $this->getUser();
        $data = $request->request->all();
        $authorizationChecker = $this->get('security.authorization_checker');
        $remesas = [];
        if ($authorizationChecker->isGranted("ROLE_DISTRIBUIDOR")) {
            if ($data['remesas_serach']['orden'] != '' || $data['remesas_serach']['destinatario'] != '' || $data['remesas_serach']['direccion'] != '' || $data['remesas_serach']['fechaInicio'] != '' || $data['remesas_serach']['fechaFin'] != '' || isset($data['remesas_serach']['estado']) || isset($data['remesas_serach']['municipio']) || isset($data['remesas_serach']['provincia'])) {
                $remesas = $remesaRepository->advancedSearchDistribuidor($data['remesas_serach'], $tnUser->getDistribuidor());
            }
        }

        return $this->render('backend/tn_remesa/search_results.html.twig', [
            'remesas' => $remesas
        ]);
    }

    /**
     * @Route("/change-state/{id}", name="entrega_change_state_remesa")
     * @Method("POST")
     */
    public function changeStateRemesa(TnRemesa $tnRemesa, NmEstadoRepository $nmEstadoRepository)
    {
        $em = $this->getDoctrine()->getManager();
        $tnRemesa->setEntregada(!$tnRemesa->getEntregada());
        $remesas = 0;
        $tnFactura = $tnRemesa->getFactura();
        foreach ($tnFactura->getRemesas() as $remesa) {
            if ($remesa->getEntregada()) {
                $remesas++;
            }
        }
        if ($remesas == $tnRemesa->getFactura()->getRemesas()->count()) {
            $tnFactura->setEstado($nmEstadoRepository->findOneBy(array('codigo' => NmEstado::ESTADO_ENTREGADA)));
            $em->persist($tnFactura);
        }
        $em->persist($tnRemesa);
        $em->flush();

        return new JsonResponse(array('success' => true));
    }

    /**
     * @Route("/export_remesas/{action}/pdf", name="entrega_remesas_pendientes_export_pdf", methods={"GET"}, requirements={"action"="download|print"})
     */
    public function exportRemesasPendientesPdf(Request $request, TnReporteEnvioRepository $tnReporteEnvioRepository, TnRemesaRepository $tnRemesaRepository, TcPdfManager $tcPdfManager, $action)
    {
        $em = $this->getDoctrine()->getManager();
        $tnUser = $this->getUser();
        $authorizationChecker = $this->get('security.authorization_checker');
        $remesas = [];
        if ($tnUser instanceof TnUser) {
            if ($authorizationChecker->isGranted("ROLE_DISTRIBUIDOR")) {
                $reporte = $tnReporteEnvioRepository->findLastReporteEnvio($tnUser->getDistribuidor());

                if ($reporte instanceof TnReporteEnvio) {
                    $lastDate = $reporte->getLastDate();
                    $remesas = $tnRemesaRepository->createQueryBuilder('tr')
                        ->join('tr.factura', 'factura')
                        ->join('factura.estado', 'estado')
                        ->where('estado.codigo IN (:estados)')
                        ->andWhere('tr.distribuidor = :dist')
                        ->andWhere('tr.entregada = false')
                        ->andWhere('tr.reporteEnvio IS NULL')
                        ->andWhere('factura.aprobadaAt > :aprov or factura.distribuidaAt > :aprov')
                        ->setParameter('dist', $tnUser->getDistribuidor())
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
                        ->setParameter('dist', $tnUser->getDistribuidor())
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
                    $tnReporteEnvio->setDistribuidor($tnUser->getDistribuidor());
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
            $tcPdfManager->getDocumentReportEnvio('QM_Remesas_' . $tnUser->getDistribuidor() . "_" . ($date->format('d-m-Y')), $result, 'D', ['title' => 'REPORTE DE ENVIO', 'user' => $tnUser->getDistribuidor()]);

        } else {
            $this->get('session')->getFlashBag()->add('warning', "Usted no posee nuevas remesas pendientes en este momento, compruebe el listado de remesas.");

            return $this->redirectToRoute('entrega_distribuidor_index');
        }

        throw new NotFoundException("Ha ocurrido un error al exportar las remesas pendientes.");
    }

    /**
     * @Route("/export/{id}/remesa/{action}/pdf", name="entrega_remesa_export_pdf", methods={"GET"}, requirements={"action"="download|print"})
     */
    public function exportRemesaPdf(Request $request, TnRemesa $tnRemesa, TcPdfManager $tcPdfManager, $action): Response
    {
        $tnUser = $this->getUser();
        if ($tnRemesa->getDistribuidor() && $tnRemesa->getDistribuidor()->getId() == $tnUser->getDistribuidor()->getId()) {
            $remesas = [$tnRemesa];

            $html = $this->renderView('backend/tn_remesa/pendientes_pdf.html.twig', array(
                'remesas' => $remesas,
            ));

            $date = new \DateTime('now');
            if ($action == "download") {
                $tcPdfManager->getDocumentReport('QM_Remesas_' . $tnUser->getDistribuidor() . ($date->format('d-m-Y')), $html, 'D', ['title' => 'REPORTE DE ENVIO']);

            } elseif ($action == "print") {
                $tcPdfManager->getDocumentReport('QM_Remesas_' . $tnUser->getDistribuidor() . ($date->format('d-m-Y')), $html, 'I', ['title' => 'REPORTE DE ENVIO']);
            }
        }

        throw new NotFoundException("La remesa que está tratando de localizar no existe o no está asiganada al distribuidor.");
    }

    /**
     * @Route("/reportes/envio", name="entrega_reportes_envio_index", methods={"GET"})
     */
    public function reportesEnvio(TnReporteEnvioRepository $tnReporteEnvioRepository, PaginatorInterface $paginator): Response
    {
        $authorizationChecker = $this->get('security.authorization_checker');
        $tnUser = $this->getUser();
        $pagination = [];
        if ($tnUser instanceof TnUser) {
            if ($authorizationChecker->isGranted("ROLE_DISTRIBUIDOR")) {
                $query = $tnReporteEnvioRepository->createQueryBuilder('tr')
                    ->where('tr.distribuidor = :dist')
                    ->setParameter('dist', $tnUser->getDistribuidor())
                    ->orderBy('tr.created', "DESC")
                    ->getQuery();

                $request = $this->get('request_stack')->getCurrentRequest();

                $pagination = $paginator->paginate(
                    $query,
                    $request->query->getInt('page', 1),
                    50
                );
            }
        }

        return $this->render('backend/tn_remesa/reportes_envio.html.twig', [
            'reportesEnvio' => $pagination,
        ]);
    }

    /**
     *
     * @Route("/show/{token}/reporte", name="entrega_reporte_envio_show")
     * @ParamConverter("tnReporteEnvio", options={"mapping": {"token": "token"}})
     * @Method("GET")
     */
    public function detallesReporteEnvio(TnReporteEnvio $tnReporteEnvio)
    {
        return $this->render('backend/tn_remesa/reporte_show.html.twig', array(
            'tnReporteEnvio' => $tnReporteEnvio,
        ));
    }


    /**
     * @Route("/remesas_reporte/{token}/pdf", name="entrega_remesas_reporte_export_pdf", methods={"GET"})
     * @ParamConverter("reporte", options={"mapping": {"token": "token"}})
     */
    public function exportRemesasReportePdf(Request $request, TnReporteEnvio $reporte, TcPdfManager $tcPdfManager): Response
    {
        $remesas = $reporte->getRemesas();
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
        $tcPdfManager->getDocumentReportEnvio('QM_Remesas_' . $reporte->getDistribuidor() . "_" . ($date->format('d-m-Y')), $result, 'I', ['title' => 'REPORTE DE ENVIO', 'date' => ['fecha' => $reporte->getCreated()->format(date('d/m/Y')), 'hora' => $reporte->getCreated()->format(date('H:i:s'))], 'user' => $reporte->getDistribuidor()]);
    }
}
