<?php

namespace App\Controller\Backend\Nomencladores;

use App\Entity\NmEstado;
use App\Entity\NmGrupoPago;
use App\Entity\NmGrupoPagoAgente;
use App\Entity\NmMoneda;
use App\Entity\TnAgencia;
use App\Entity\TnAgente;
use App\Entity\TnApoderado;
use App\Entity\TnApoderadoFactura;
use App\Entity\TnConfiguration;
use App\Entity\TnDestinatario;
use App\Entity\TnDistribuidor;
use App\Entity\TnEmisor;
use App\Entity\TnFactura;
use App\Entity\TnOperacionAgencia;
use App\Entity\TnOperacionAgente;
use App\Entity\TnRemesa;
use App\Entity\TnUser;
use App\Form\TnFacturaType;
use App\Form\Type\AdvancedSerachType;
use App\Form\Type\FacturaAprobarType;
use App\Form\Type\FacturaDistribuirType;
use App\Manager\ConfigurationManager;
use App\Manager\FacturaManager;
use App\Manager\TcPdfManager;
use App\Repository\NmEstadoRepository;
use App\Repository\NmMonedaRepository;
use App\Repository\TnAgenciaRepository;
use App\Repository\TnAgenteRepository;
use App\Repository\TnEmisorRepository;
use App\Repository\TnFacturaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Loggable\Entity\LogEntry;
use Knp\Component\Pager\PaginatorInterface;
use Laminas\Validator\Date;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Spraed\PDFGeneratorBundle\PDFGenerator\PDFGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @Route("/remesas/facturas")
 */
class TnFacturaController extends AbstractController
{
    /**
     * @Route("/", name="tn_factura_index", methods={"GET"})
     */
    public function index(TnFacturaRepository $tnFacturaRepository, PaginatorInterface $paginator, TnAgenteRepository $tnAgenteRepository, AuthorizationCheckerInterface $authorizationChecker): Response
    {
        $tnUser = $this->getUser();
        if ($tnUser instanceof TnUser) {
            if ($authorizationChecker->isGranted("ROLE_AGENCIA")) {
                $agentesIds = $tnAgenteRepository->findAgentesAgenciaIds($tnUser->getAgencia());

                $query = $tnFacturaRepository->createQueryBuilder('f')
                    ->where('f.agencia = :ag or (f.agente is not null and f.agente IN (:ids))')
                    ->setParameter('ag', $tnUser->getAgencia())
                    ->setParameter('ids', $agentesIds)
                    ->orderBy('f.created', "DESC")
                    ->getQuery();
            } elseif ($authorizationChecker->isGranted("ROLE_AGENTE")) {
                $tnAgente = $tnUser->getAgente();
                if ($tnAgente->getTipoAgente() == TnAgente::TIPO_EXTERNO) {
                    $query = $tnFacturaRepository->createQueryBuilder('f')
                        ->where('f.agente = :ag')
                        ->setParameter('ag', $tnUser->getAgente())
                        ->orderBy('f.created', "DESC")
                        ->getQuery();
                } else {
                    $userAgencia = $tnAgente->getAgencia();
                    $query = $tnFacturaRepository->createQueryBuilder('f')
                        ->where('f.agente = :ag or f.agencia = :agc')
                        ->setParameter('ag', $tnUser->getAgente())
                        ->setParameter('agc', $userAgencia)
                        ->orderBy('f.created', "DESC")
                        ->getQuery();
                }

            } else {
                $query = $tnFacturaRepository->createQueryBuilder('f')->orderBy('f.created', "DESC")->getQuery();
            }
        }

        $form = $this->createForm(AdvancedSerachType::class);

        $request = $this->get('request_stack')->getCurrentRequest();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            100
        );

        return $this->render('backend/tn_factura/index.html.twig', [
            'form' => $form->createView(),
            'tn_facturas' => $pagination
        ]);
    }

    /**
     * @Route("/advanced_search", name="tn_factura_advanced_search")
     * @Method({"POST"})
     */
    public function advancedSearch(Request $request, TnFacturaRepository $tnFacturaRepository)
    {
        $tnUser = $this->getUser();
        $data = $request->request->all();
        $authorizationChecker = $this->get('security.authorization_checker');
        if ($authorizationChecker->isGranted("ROLE_AGENCIA")) {
            $facturas = $tnFacturaRepository->advancedSearchAgencia($data['advanced_serach'], $tnUser->getAgencia());
        } elseif ($authorizationChecker->isGranted("ROLE_AGENTE")) {
            $facturas = $tnFacturaRepository->advancedSearchAgente($data['advanced_serach'], $tnUser->getAgente());
        } else {
            $facturas = $tnFacturaRepository->advancedSearchAdmin($data['advanced_serach']);
        }


        return $this->render('backend/tn_factura/search_results.html.twig', [
            'tn_facturas' => $facturas
        ]);
    }

    /**
     * @Route("/notas/{token}/edit", name="tn_factura_notas_edit")
     * @ParamConverter("tnFactura", options={"mapping": {"token": "token"}})
     * @Method({"GET", "POST"})
     */
    public function editarNotasFactira(Request $request, TnFactura $tnFactura)
    {
        $em = $this->getDoctrine()->getManager();
        if ($request->isMethod("POST")) {
            $notas = $request->get('notas');
            $tnFactura->setNotas($notas);
            $em->persist($tnFactura);
            $em->flush();

            return $this->render('backend/tn_factura/notas_edit.html.twig', array(
                'factura' => $tnFactura,
            ));
        }

        return $this->render('backend/tn_factura/notas.html.twig', array(
            'tnFactura' => $tnFactura,
        ));
    }

    /**
     * @Route("/referencia_old/{token}/edit", name="tn_factura_referencia_old_edit")
     * @ParamConverter("tnFactura", options={"mapping": {"token": "token"}})
     * @Method({"GET", "POST"})
     */
    public function referenciaOldFacturaEdit(Request $request, TnFactura $tnFactura)
    {
        $em = $this->getDoctrine()->getManager();
        if ($request->isMethod("POST")) {
            $referencia_old = $request->get('referencia_old');
            $tnFactura->setReferenciaOld($referencia_old);
            $em->persist($tnFactura);
            $em->flush();

            return $this->render('backend/tn_factura/referencia_old_edit.html.twig', array(
                'factura' => $tnFactura,
            ));
        }

        return $this->render('backend/tn_factura/referencia_old.html.twig', array(
            'tnFactura' => $tnFactura,
        ));
    }

    /**
     * @Route("/new", name="tn_factura_new", methods={"GET","POST"})
     * @Route("/new/{token}/emisor", name="tn_factura_new_emisor", methods={"GET","POST"})
     * @ParamConverter("tnEmisor", options={"mapping": {"token": "token"}})
     */
    public function new(Request $request, ConfigurationManager $configurationManager, FacturaManager $facturaManager, TnEmisor $tnEmisor = null, AuthorizationCheckerInterface $authorizationChecker): Response
    {
        ini_set('memory_limit', -1);

        $statusFactura = $configurationManager->get(TnConfiguration::STATUS_NEW_FACTURA);
        if ($statusFactura == 'HABILITADO') {

            $user = $this->getUser();
            if ($user->getPorcentaje() == null || $user->getPorcentaje() == 0) {
                $this->get('session')->getFlashBag()->add('error', 'Debe especificar el porciento a operar para registrar/editar facturas.');

                return $this->redirectToRoute('tn_factura_index');
            }
            $entityManager = $this->getDoctrine()->getManager();
            $tnFactura = new TnFactura();
            if (!$user->getAuth()) {
                $tnFactura->setAuth(false);
            }
            if ($tnEmisor != null) {
                $tnFactura->setEmisor($tnEmisor);
            }
            $form = $this->createForm(TnFacturaType::class, $tnFactura);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $fechaEntrega = new \DateTime();
                $days = $configurationManager->get(TnConfiguration::DIAS_ENTREGA);
                $fechaEntrega->modify('+' . $days . ' day');
                $tnFactura->setFechaEntrega($fechaEntrega);
                //Obteniendo la remesa de la factura y sus datos.
                $tnRemesaFactura = $tnFactura->getRemesas()[0];

                if (!$user->getAuth()) {
                    $tnRemesaFactura->setTotalPagar(0.0);
                }
                $tnRemesaFactura->setEntregada(false);
                $entityManager->persist($tnRemesaFactura);

                $total = $tnRemesaFactura->getTotalPagar();
                $importe = $tnRemesaFactura->getImporteEntregar();
                $cantRemesas = 1;

                if ($user->getAuth()) {
                    $tnFactura->setTotal($total);
                } else {
                    $tnFactura->setTotal(0.0);
                }
                //Guardando la moneda y la tasa
                $monedaRemesa = $tnRemesaFactura->getMoneda();
                $tasa = $monedaRemesa->getTasaCambio();

                $tnFactura->setImporte($importe);
                $tnFactura->setMoneda($monedaRemesa->getSimbolo());
                $tnFactura->setTasa($tasa);


                //Verificando los valores y poniendo los que van
                $user = $this->getUser();
                $checker = $this->get('security.authorization_checker');

                $importeTasa = round(($importe / $tasa), 2); //Importe por el que se debe calcular los porcientos y demás.
                if ($checker->isGranted("ROLE_AGENCIA")) {
                    $tnFactura->setAgencia($user->getAgencia());
                    if ($tnFactura->getAgente() != null) {
                        $grupoPagoAgente = $entityManager->getRepository(NmGrupoPagoAgente::class)->grupoPagoAgente($tnFactura->getAgente(), $monedaRemesa);

                        if (!is_null($grupoPagoAgente->getUtilidad())) {//Viendo el que grupo de pago está el agente
                            //Poniendo los valores con que se crea la factura
                            $porcientoAsig = $grupoPagoAgente->getUtilidad();
                            $tnFactura->setPorcentajeAsignadoAgente($porcientoAsig);
                            //Buscando el porcentaje configurado
                            $porcentaje = $facturaManager->porcientoOperacionAgente($tnFactura->getAgente(), $monedaRemesa);
                            if ($porcentaje != null) {
                                $tnFactura->setPorcentajeOperaAgente($porcentaje);
                            } else {
                                $tnFactura->setPorcentajeOperaAgente($tnFactura->getAgente()->getUsuario()->getPorcentaje());
                            }
                            $tnFactura->setTipoPorcentajeAgente(TnFactura::TIPO_UTILIDAD);
                            //Calculo el porciento
                            $totalPagar = $importeTasa + $porcientoAsig * $cantRemesas;
                            $tnFactura->setTotalPagarAgente($totalPagar);
                            $tnFactura->setUtilidadFijaAgente(false);
                        } else {
                            //Poniendo los valores con que se crea la factura
                            $porcientoAsig = $grupoPagoAgente->getPorcentaje();
                            $tnFactura->setPorcentajeAsignadoAgente($porcientoAsig);
                            //Buscando el porcentaje configurado
                            $porcentaje = $facturaManager->porcientoOperacionAgente($tnFactura->getAgente(), $monedaRemesa);
                            if ($porcentaje != null) {
                                $tnFactura->setPorcentajeOperaAgente($porcentaje);
                            } else {
                                $tnFactura->setPorcentajeOperaAgente($tnFactura->getAgente()->getUsuario()->getPorcentaje());
                            }
                            $tnFactura->setTipoPorcentajeAgente(TnFactura::TIPO_PORCIENTO);
                            //Calculo el porciento
                            $minimo = $grupoPagoAgente->getMinimo();
                            $utilidadFija = $grupoPagoAgente->getUtilidadFija();
                            if ($minimo != null && !is_null($utilidadFija) && $importeTasa < $minimo) {
                                $tipoUtilidad = $grupoPagoAgente->getTipoUtilidad();
                                if ($tipoUtilidad != "" && $tipoUtilidad == NmGrupoPagoAgente::TIPO_PORCIENTO) {
                                    $totalPagar = $importeTasa + round((($importeTasa * $utilidadFija) / 100), 2);
                                    $tnFactura->setUtilidadFijaAgente(false);
                                    $tnFactura->setPorcentajeAsignadoAgente($utilidadFija);
                                } else {
                                    $totalPagar = $importeTasa + $utilidadFija;
                                    $tnFactura->setUtilidadFijaAgente(true);
                                    $tnFactura->setPorcentajeAsignadoAgente($utilidadFija);
                                }
                            } elseif ($importeTasa < 100) {
                                $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);
                                $tnFactura->setUtilidadFijaAgente(false);
                            } else {
                                $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);
                                $tnFactura->setUtilidadFijaAgente(false);
                            }

                            $tnFactura->setTotalPagarAgente($totalPagar);
                        }
                        //Actualizando saldo agente
                        $facturaManager->updateSaldoAgente($tnFactura->getAgente(), $monedaRemesa, $importe);
                    }
                    $grupoPagoAgencia = $entityManager->getRepository(NmGrupoPago::class)->grupoPagoAgencia($user->getAgencia(), $monedaRemesa);

                    //Poniendo los valores con que se crea la factura
                    $porcientoAsig = $grupoPagoAgencia->getPorcentaje();
                    $tnFactura->setPorcentajeAsignado($porcientoAsig);
                    //Buscando el porcentaje configurado
                    $porcentaje = $facturaManager->porcientoOperacionAgencia($user->getAgencia(), $monedaRemesa);
                    if ($porcentaje != null) {
                        $tnFactura->setPorcentajeOpera($porcentaje);
                    } else {
                        $tnFactura->setPorcentajeOpera($user->getPorcentaje());
                    }
                    $tnFactura->setTipoPorcentaje(TnFactura::TIPO_PORCIENTO);

                    //Calculo el porciento
                    $minimo = $grupoPagoAgencia->getMinimo();
                    $utilidadFija = $grupoPagoAgencia->getUtilidadFija();

                    if ($minimo != null && !is_null($utilidadFija) && $importeTasa < $minimo) {
                        $tipoUtilidad = $grupoPagoAgencia->getTipoUtilidad();
                        if ($tipoUtilidad != "" && $tipoUtilidad == NmGrupoPago::TIPO_PORCIENTO) {
                            $totalPagar = $importeTasa + round((($importeTasa * $utilidadFija) / 100), 2);
                            $tnFactura->setUtilidadFija(false);
                            $tnFactura->setPorcentajeAsignado($utilidadFija);
                        } else {
                            $totalPagar = $importeTasa + $utilidadFija;
                            $tnFactura->setUtilidadFija(true);
                            $tnFactura->setPorcentajeAsignado($utilidadFija);
                        }
                    } elseif ($importeTasa < 100) {
                        $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);
                        $tnFactura->setUtilidadFija(false);
                    } else {
                        $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);
                        $tnFactura->setUtilidadFija(false);
                    }
                    $tnFactura->setTotalPagar($totalPagar);

                    if ($tnFactura->getAgente() == null) {
                        //Actualizando saldo agencia
                        $facturaManager->updateSaldoAgencia($user->getAgencia(), $monedaRemesa, $importe);
                    }

                    //Verificar los datos del apoderado
                    $apoderado = $entityManager->getRepository(TnApoderado::class)->findOneBySubordinada($user->getAgencia());
                    if (!is_null($apoderado)) {
                        $facturaManager->crearApoderadoFactura($apoderado, $tnFactura);
                    }
                    $estado = $entityManager->getRepository(NmEstado::class)->findOneBy(['codigo' => NmEstado::ESTADO_PENDIENTE]);
                    $tnFactura->setEstado($estado);
                } elseif ($checker->isGranted("ROLE_AGENTE")) {
                    $grupoPagoAgente = $entityManager->getRepository(NmGrupoPagoAgente::class)->grupoPagoAgente($user->getAgente(), $monedaRemesa);

                    $tnFactura->setAgencia($user->getAgente()->getAgencia());
                    $tnFactura->setAgente($user->getAgente());
                    if ($grupoPagoAgente->getUtilidad()) {//Viendo el que grupo de pago está el agente
                        //Poniendo los valores con que se crea la factura
                        $porcientoAsig = $grupoPagoAgente->getUtilidad();
                        $tnFactura->setPorcentajeAsignadoAgente($porcientoAsig);
                        //Buscando el porcentaje configurado
                        $porcentaje = $facturaManager->porcientoOperacionAgente($user->getAgente(), $monedaRemesa);
                        if ($porcentaje != null) {
                            $tnFactura->setPorcentajeOperaAgente($porcentaje);
                        } else {
                            $tnFactura->setPorcentajeOperaAgente($user->getPorcentaje());
                        }
                        $tnFactura->setTipoPorcentajeAgente(TnFactura::TIPO_UTILIDAD);
                        //Calculo el porciento
                        $totalPagar = $importeTasa + $porcientoAsig * $cantRemesas;
                        $tnFactura->setTotalPagarAgente($totalPagar);
                        $tnFactura->setUtilidadFijaAgente(false);
                    } else {
                        //Poniendo los valores con que se crea la factura
                        $porcientoAsig = $grupoPagoAgente->getPorcentaje();
                        $tnFactura->setPorcentajeAsignadoAgente($porcientoAsig);
                        //Buscando el porcentaje configurado
                        $porcentaje = $facturaManager->porcientoOperacionAgente($user->getAgente(), $monedaRemesa);
                        if ($porcentaje != null) {
                            $tnFactura->setPorcentajeOperaAgente($porcentaje);
                        } else {
                            $tnFactura->setPorcentajeOperaAgente($user->getPorcentaje());
                        }
                        $tnFactura->setTipoPorcentajeAgente(TnFactura::TIPO_PORCIENTO);
                        //Calculo el porciento
                        $minimo = $grupoPagoAgente->getMinimo();
                        $utilidadFija = $grupoPagoAgente->getUtilidadFija();
                        if ($minimo != null && !is_null($utilidadFija) && $importeTasa < $minimo) {
                            $tipoUtilidad = $grupoPagoAgente->getTipoUtilidad();
                            if ($tipoUtilidad != "" && $tipoUtilidad == NmGrupoPago::TIPO_PORCIENTO) {
                                $totalPagar = $importeTasa + round((($importeTasa * $utilidadFija) / 100), 2);
                                $tnFactura->setUtilidadFijaAgente(false);
                                $tnFactura->setPorcentajeAsignadoAgente($utilidadFija);
                            } else {
                                $totalPagar = $importeTasa + $utilidadFija;
                                $tnFactura->setUtilidadFijaAgente(true);
                                $tnFactura->setPorcentajeAsignadoAgente($utilidadFija);
                            }
                        } elseif ($importeTasa < 100) {
                            $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);
                            $tnFactura->setUtilidadFijaAgente(false);
                        } else {
                            $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);
                            $tnFactura->setUtilidadFijaAgente(false);
                        }
                        $tnFactura->setTotalPagarAgente($totalPagar);
                    }

                    $estado = $entityManager->getRepository(NmEstado::class)->findOneBy(['codigo' => NmEstado::ESTADO_PENDIENTE]);
                    $tnFactura->setEstado($estado);
                    if ($user->getAgente()->getAgencia() != null) {//Verifico si el agente es de una agencia y guardo sus datos
                        $grupoPagoAgencia = $entityManager->getRepository(NmGrupoPago::class)->grupoPagoAgencia($user->getAgente()->getAgencia(), $monedaRemesa);

                        $porcientoAsig = $grupoPagoAgencia->getPorcentaje();
                        $tnFactura->setPorcentajeAsignado($porcientoAsig);
                        //Buscando el porcentaje configurado
                        $porcentaje = $facturaManager->porcientoOperacionAgencia($user->getAgente()->getAgencia(), $monedaRemesa);
                        if ($porcentaje != null) {
                            $tnFactura->setPorcentajeOpera($porcentaje);
                        } else {
                            $tnFactura->setPorcentajeOpera($user->getAgente()->getAgencia()->getUsuario() ? $user->getAgente()->getAgencia()->getUsuario()->getPorcentaje() : $porcientoAsig);
                        }
                        $tnFactura->setTipoPorcentaje(TnFactura::TIPO_PORCIENTO);
                        //Calculo el porciento
                        $minimo = $grupoPagoAgencia->getMinimo();
                        $utilidadFija = $grupoPagoAgencia->getUtilidadFija();
                        if ($minimo != null && !is_null($utilidadFija) && $importeTasa < $minimo) {
                            $tipoUtilidad = $grupoPagoAgencia->getTipoUtilidad();
                            if ($tipoUtilidad != "" && $tipoUtilidad == NmGrupoPago::TIPO_PORCIENTO) {
                                $totalPagar = $importeTasa + round((($importeTasa * $utilidadFija) / 100), 2);
                                $tnFactura->setUtilidadFija(false);
                                $tnFactura->setPorcentajeAsignado($utilidadFija);
                            } else {
                                $totalPagar = $importeTasa + $utilidadFija;
                                $tnFactura->setUtilidadFija(true);
                                $tnFactura->setPorcentajeAsignado($utilidadFija);
                            }
                        } elseif ($importeTasa < 100) {
                            $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);
                            $tnFactura->setUtilidadFija(false);
                        } else {
                            $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);
                            $tnFactura->setUtilidadFija(false);
                        }
                        $tnFactura->setTotalPagar($totalPagar);

                        //Verificar los datos del apoderado
                        $apoderado = $entityManager->getRepository(TnApoderado::class)->findOneBySubordinada($user->getAgente()->getAgencia());
                        if (!is_null($apoderado)) {
                            $facturaManager->crearApoderadoFactura($apoderado, $tnFactura);
                        }
                    } else {
                        $tnFactura->setTotalPagar(0);
                        $tnFactura->setPorcentajeAsignado(0);
                        $tnFactura->setPorcentajeOpera(0);
                        $tnFactura->setTipoPorcentaje(TnFactura::TIPO_PORCIENTO);
                    }

                    //Actualizando el saldo al agente
                    $facturaManager->updateSaldoAgente($user->getAgente(), $monedaRemesa, $importe);
                } else {
                    //Verificando si es para de una agencia o un agente
                    if ($tnFactura->getAgente() != null) {
                        $grupoPagoAgente = $entityManager->getRepository(NmGrupoPagoAgente::class)->grupoPagoAgente($tnFactura->getAgente(), $monedaRemesa);

                        $tnFactura->setAgencia($tnFactura->getAgente()->getAgencia());
                        if ($grupoPagoAgente->getUtilidad()) {//Viendo el que grupo de pago está el agente
                            //Poniendo los valores con que se crea la factura
                            $porcientoAsig = $grupoPagoAgente->getUtilidad();
                            $tnFactura->setPorcentajeAsignadoAgente($porcientoAsig);
                            //Buscando el porcentaje configurado
                            $porcentaje = $facturaManager->porcientoOperacionAgente($tnFactura->getAgente(), $monedaRemesa);
                            if ($porcentaje != null) {
                                $tnFactura->setPorcentajeOperaAgente($porcentaje);
                            } else {
                                $tnFactura->setPorcentajeOperaAgente($tnFactura->getAgente()->getUsuario()->getPorcentaje());
                            }
                            $tnFactura->setTipoPorcentajeAgente(TnFactura::TIPO_UTILIDAD);
                            //Calculo el porciento
                            $totalPagar = $importeTasa + $porcientoAsig * $cantRemesas;
                            $tnFactura->setTotalPagarAgente($totalPagar);
                            $tnFactura->setUtilidadFijaAgente(false);
                        } else {
                            //Poniendo los valores con que se crea la factura
                            $porcientoAsig = $grupoPagoAgente->getPorcentaje();
                            $tnFactura->setPorcentajeAsignadoAgente($porcientoAsig);
                            $porcentaje = $facturaManager->porcientoOperacionAgente($tnFactura->getAgente(), $monedaRemesa);
                            if ($porcentaje != null) {
                                $tnFactura->setPorcentajeOperaAgente($porcentaje);
                            } else {
                                $tnFactura->setPorcentajeOperaAgente($tnFactura->getAgente()->getUsuario()->getPorcentaje());
                            }
                            $tnFactura->setTipoPorcentajeAgente(TnFactura::TIPO_PORCIENTO);
                            //Calculo el porciento o utilidad fija
                            $minimo = $grupoPagoAgente->getMinimo();
                            $utilidadFija = $grupoPagoAgente->getUtilidadFija();
                            if ($minimo != null && !is_null($utilidadFija) && $importeTasa < $minimo) {
                                $tipoUtilidad = $grupoPagoAgente->getTipoUtilidad();
                                if ($tipoUtilidad != "" && $tipoUtilidad == NmGrupoPago::TIPO_PORCIENTO) {
                                    $totalPagar = $importeTasa + round((($importeTasa * $utilidadFija) / 100), 2);
                                    $tnFactura->setPorcentajeAsignadoAgente($utilidadFija);
                                    $tnFactura->setUtilidadFijaAgente(false);
                                } else {
                                    $totalPagar = $importeTasa + $utilidadFija;
                                    $tnFactura->setPorcentajeAsignadoAgente($utilidadFija);
                                    $tnFactura->setUtilidadFijaAgente(true);
                                }
                            } elseif ($importeTasa < 100) {
                                $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);
                                $tnFactura->setUtilidadFijaAgente(false);
                            } else {
                                $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);
                                $tnFactura->setUtilidadFijaAgente(false);
                            }

                            $tnFactura->setTotalPagarAgente($totalPagar);
                        }
                        if ($tnFactura->getAgente()->getAgencia() != null) {//Verifico si el agente es de una agencia y guardo sus datos
                            $grupoPagoAgencia = $entityManager->getRepository(NmGrupoPago::class)->grupoPagoAgencia($tnFactura->getAgente()->getAgencia(), $monedaRemesa);

                            $porcientoAsig = $grupoPagoAgencia->getPorcentaje();
                            $tnFactura->setPorcentajeAsignado($porcientoAsig);
                            //Calculando el porciento con que opera
                            $porcentaje = $facturaManager->porcientoOperacionAgencia($tnFactura->getAgente()->getAgencia(), $monedaRemesa);
                            if ($porcentaje != null) {
                                $tnFactura->setPorcentajeOpera($porcentaje);
                            } else {
                                $tnFactura->setPorcentajeOpera($tnFactura->getAgente()->getAgencia()->getUsuario() ? $tnFactura->getAgente()->getAgencia()->getUsuario()->getPorcentaje() : $porcientoAsig);
                            }
                            $tnFactura->setTipoPorcentaje(TnFactura::TIPO_PORCIENTO);
                            //Calculo el porciento o utilidad fija
                            $minimo = $grupoPagoAgencia->getMinimo();
                            $utilidadFija = $grupoPagoAgencia->getUtilidadFija();
                            if ($minimo != null && !is_null($utilidadFija) && $importeTasa < $minimo) {
                                $tipoUtilidad = $grupoPagoAgencia->getTipoUtilidad();
                                if ($tipoUtilidad != "" && $tipoUtilidad == NmGrupoPago::TIPO_PORCIENTO) {
                                    $totalPagar = $importeTasa + round((($importeTasa * $utilidadFija) / 100), 2);
                                    $tnFactura->setUtilidadFija(false);
                                    $tnFactura->setPorcentajeAsignado($utilidadFija);
                                } else {
                                    $totalPagar = $importeTasa + $utilidadFija;
                                    $tnFactura->setUtilidadFija(true);
                                    $tnFactura->setPorcentajeAsignado($utilidadFija);
                                }
                            } elseif ($importeTasa < 100) {
                                $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);
                                $tnFactura->setUtilidadFija(false);
                            } else {
                                $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);
                                $tnFactura->setUtilidadFija(false);
                            }
                            $tnFactura->setTotalPagar($totalPagar);

                            //Verificar los datos del apoderado
                            $apoderado = $entityManager->getRepository(TnApoderado::class)->findOneBySubordinada($tnFactura->getAgente()->getAgencia());
                            if (!is_null($apoderado)) {
                                $facturaManager->crearApoderadoFactura($apoderado, $tnFactura);
                            }
                        } else {
                            $tnFactura->setTotalPagar(0);
                            $tnFactura->setPorcentajeAsignado(0);
                            $tnFactura->setPorcentajeOpera(0);
                            $tnFactura->setTipoPorcentaje(TnFactura::TIPO_PORCIENTO);
                        }

                        //Actualizando saldo agente
                        $facturaManager->updateSaldoAgente($tnFactura->getAgente(), $monedaRemesa, $importe);
                    } elseif ($tnFactura->getAgencia() != null) {
                        $grupoPagoAgencia = $entityManager->getRepository(NmGrupoPago::class)->grupoPagoAgencia($tnFactura->getAgencia(), $monedaRemesa);

                        //Poniendo los valores con que se crea la factura
                        $porcientoAsig = $grupoPagoAgencia->getPorcentaje();
                        $tnFactura->setPorcentajeAsignado($porcientoAsig);
                        //Calculando el porciento con que opera
                        $porcentaje = $facturaManager->porcientoOperacionAgencia($tnFactura->getAgencia(), $monedaRemesa);
                        if ($porcentaje != null) {
                            $tnFactura->setPorcentajeOpera($porcentaje);
                        } else {
                            $tnFactura->setPorcentajeOpera($tnFactura->getAgencia()->getUsuario()->getPorcentaje());
                        }
                        $tnFactura->setTipoPorcentaje(TnFactura::TIPO_PORCIENTO);

                        //Calculo el porciento o utilidad fija
                        $minimo = $grupoPagoAgencia->getMinimo();
                        $utilidadFija = $grupoPagoAgencia->getUtilidadFija();

                        if ($minimo != null && !is_null($utilidadFija) && $importeTasa < $minimo) {
                            $tipoUtilidad = $grupoPagoAgencia->getTipoUtilidad();
                            if ($tipoUtilidad != "" && $tipoUtilidad == NmGrupoPago::TIPO_PORCIENTO) {
                                $totalPagar = $importeTasa + round((($importeTasa * $utilidadFija) / 100), 2);
                                $tnFactura->setPorcentajeAsignado($utilidadFija);
                                $tnFactura->setUtilidadFija(false);
                            } else {
                                $totalPagar = $importeTasa + $utilidadFija;
                                $tnFactura->setPorcentajeAsignado($utilidadFija);
                                $tnFactura->setUtilidadFija(true);
                            }
                        } elseif ($importeTasa < 100) {
                            $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);
                            $tnFactura->setUtilidadFija(false);
                        } else {
                            $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);
                            $tnFactura->setUtilidadFija(false);
                        }
                        $tnFactura->setTotalPagar($totalPagar);

                        //Actualizando saldo agencia
                        $facturaManager->updateSaldoAgencia($tnFactura->getAgencia(), $monedaRemesa, $importe);

                        //Verificar los datos del apoderado
                        $apoderado = $entityManager->getRepository(TnApoderado::class)->findOneBySubordinada($tnFactura->getAgencia());
                        if (!is_null($apoderado)) {
                            $facturaManager->crearApoderadoFactura($apoderado, $tnFactura);
                        }
                    } else {

                        //Poniendo los valores con que se crea la factura
                        $porcientoAsig = $configurationManager->get(TnConfiguration::PORCENTAJE);
                        $tnFactura->setPorcentajeAsignado($porcientoAsig);
                        $tnFactura->setPorcentajeOpera($user->getPorcentaje());
                        $tnFactura->setTipoPorcentaje(TnFactura::TIPO_PORCIENTO);
                        //Calculo el porciento
                        if ($porcientoAsig > 0) {
                            if ($importeTasa < 100) {
                                $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);
                            } else {
                                $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);
                            }
                        } else {
                            $totalPagar = 0;
                        }
                        $tnFactura->setUtilidadFija(false);
                        $tnFactura->setUtilidadFijaAgente(false);
                        $tnFactura->setTotalPagar($totalPagar);
                    }
                }


                $tnFactura->setNoFactura($facturaManager->newCodigoFactura());

                //Asignando distribuuidor en caso de que sea un solo en la zona
                foreach ($tnFactura->getRemesas() as $remesa) {
                    $municipio = $remesa->getDestinatario()->getMunicipio()->getId();
                    $distZona = $facturaManager->findDistribuidorZona($municipio);

                    if (count($distZona) == 1) {//Si tiene un solo distribuidor, se lo asigno a la remesa
                        $remesa->setDistribuidor($distZona[0]);
                    }
                }

                if ($tnFactura->getAgencia() != null && $tnFactura->getAgencia()->getRetenida()) {//Verificando si hay que marcar la factura como retenida
                    $tnFactura->setRetenida(true);
                }

                $tnFactura->setToken((sha1(uniqid())));
                $tnFactura->setSospechosa(false);
                $entityManager->persist($tnFactura);
                $entityManager->flush();

                return $this->redirectToRoute('tn_factura_index');
            }

            return $this->render('backend/tn_factura/new.html.twig', [
                'tn_factura' => $tnFactura,
                'porciento' => $user->getPorcentaje(),
                'form' => $form->createView(),
            ]);
        } else {
            $this->get('session')->getFlashBag()->add('warning', 'En estos momentos se ha desabilitado la opción de registrar nuevas facturas, intente más tarde.');

            return $this->redirectToRoute('tn_factura_index');
        }
    }

    /**
     * @Route("/show/{token}", name="tn_factura_show", methods={"GET"})
     * @ParamConverter("tnFactura", options={"mapping": {"token": "token"}})
     */
    public function show(TnFactura $tnFactura): Response
    {
        return $this->render('backend/tn_factura/show.html.twig', [
            'tn_factura' => $tnFactura,
        ]);
    }

    /**
     * @Route("/edit/{token}/", name="tn_factura_edit", methods={"GET","POST"})
     * @ParamConverter("tnFactura", options={"mapping": {"token": "token"}})
     */
    public function edit(Request $request, TnFactura $tnFactura, ConfigurationManager $configurationManager, FacturaManager $facturaManager): Response
    {
        ini_set('memory_limit', -1);

        $user = $this->getUser();
        if ($user->getPorcentaje() == null || $user->getPorcentaje() == 0) {
            $this->get('session')->getFlashBag()->add('error', 'Debe especificar el porciento a operar para registrar/editar facturas.');

            return $this->redirectToRoute('tn_factura_index');
        }
        $entityManager = $this->getDoctrine()->getManager();
        $initial = new ArrayCollection();
        $importeAnterior = 0;
        foreach ($tnFactura->getRemesas() as $remesa) {
            $initial->add($remesa);
            $importeAnterior = $remesa->getImporteEntregar();
        }
        $form = $this->createForm(TnFacturaType::class, $tnFactura);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //Eliminando las remesas eliminadas
            $this->deleteCollections($entityManager, $initial, $tnFactura->getRemesas(), $facturaManager);

            $total = 0;
            $importe = 0;
            $cantRemesas = 0;
            foreach ($tnFactura->getRemesas() as $remesa) {
                if ($remesa->getId() == null) {
                    if (!$user->getAuth()) {
                        $remesa->setTotalPagar(0.0);
                    }
                    $remesa->setEntregada(false);
                    $entityManager->persist($remesa);
                }
                $total += $remesa->getTotalPagar();
                $importe += $remesa->getImporteEntregar();
                $cantRemesas++;
                if ($remesa->getDistribuidor() == null) {//Verificando los distrubuidores y si se han asignado nuevos para la zona y ponerla.
                    $municipio = $remesa->getDestinatario()->getMunicipio()->getId();
                    $distZona = $facturaManager->findDistribuidorZona($municipio);
                    if (count($distZona) == 1) {//Si tiene un solo distribuidor, se lo asigno a la remesa
                        $remesa->setDistribuidor($distZona[0]);
                        $entityManager->persist($remesa);
                    }
                } elseif ($tnFactura->getEstado()->getCodigo() != NmEstado::ESTADO_DISTRIBUCION) {//Verficando si se mantiene el distribuidor por si cambiaron el destinatario.
                    $municipio = $remesa->getDestinatario()->getMunicipio()->getId();
                    $distZona = $facturaManager->findDistribuidorZona($municipio);
                    if (count($distZona) == 1) {//Si tiene un solo distribuidor, verifico si es el mismo, si no es actualizo
                        if ($remesa->getDistribuidor()->getId() != $distZona[0]->getId()) {
                            $remesa->setDistribuidor($distZona[0]);
                            $entityManager->persist($remesa);

                            //Cancelando historial si tiene esa remesa
                            $facturaManager->cancelarHistorialFactura($tnFactura);
                            $remesa->setHistorialDistribuidor(null);
                        }
                    } else {
                        if (!in_array($remesa->getDistribuidor(), $distZona)) {//Si el distribuidor no está en los nuevos encontrados, se borra de la remesa
                            $remesa->setDistribuidor(null);
                            $entityManager->persist($remesa);

                            //Cancelando historial si tiene esa remesa
                            $facturaManager->cancelarHistorialFactura($tnFactura);
                            $remesa->setHistorialDistribuidor(null);
                        }
                    }
                }
            }

            $tnRemesaFactura = $tnFactura->getRemesas()[0];
            //Chequeando si la factura está aprobada
            if ($tnFactura->getEstado()->getCodigo() == NmEstado::ESTADO_APROBADA || $tnFactura->getEstado()->getCodigo() == NmEstado::ESTADO_DISTRIBUCION) {
                $this->addCollections($initial, $tnFactura->getRemesas(), $facturaManager);//Añadiendo las nuevas facturas de haber
                if ($tnRemesaFactura->getHistorialDistribuidor() != null) {
                    $facturaManager->chequearHistorialFactura($tnFactura);//Chequeando no se hayan modificado las que están
                } else {
                    $facturaManager->crearHistorialDistribucion($tnFactura);//Creando el historia en caso de que sea la misma pero se modificó
                }
            }

            if ($user->getAuth()) {
                $tnFactura->setTotal($total);
            } else {
                $tnFactura->setTotal(0.0);
            }
            //Guardando la moneda y la tasa
            $monedaRemesa = $tnRemesaFactura->getMoneda();
            $tasa = $monedaRemesa->getTasaCambio();
            $tnFactura->setImporte($importe);
            $tnFactura->setMoneda($monedaRemesa->getSimbolo());
            $tnFactura->setTasa($tasa);

            $user = $this->getUser();
            $checker = $this->get('security.authorization_checker');

            $importeTasa = round(($importe / $tasa), 2); //Importe por el que se debe calcular los porcientos y demás.
            if ($checker->isGranted("ROLE_AGENCIA")) {
                $tnFactura->setAgencia($user->getAgencia());
                //Poniendo los valores con que se crea la factura
                if ($tnFactura->getAgente() != null) {
                    $grupoPagoAgente = $entityManager->getRepository(NmGrupoPagoAgente::class)->grupoPagoAgente($tnFactura->getAgente(), $monedaRemesa);

                    if (!is_null($grupoPagoAgente->getUtilidad())) {//Viendo el que grupo de pago está el agente
                        //Poniendo los valores con que se crea la factura
                        $porcientoAsig = $grupoPagoAgente->getUtilidad();
                        $tnFactura->setPorcentajeAsignadoAgente($porcientoAsig);
                        //Calculando el porciento con que opera
                        $porcentaje = $facturaManager->porcientoOperacionAgente($tnFactura->getAgente(), $monedaRemesa);
                        if ($porcentaje != null) {
                            $tnFactura->setPorcentajeOperaAgente($porcentaje);
                        } else {
                            $tnFactura->setPorcentajeOperaAgente($tnFactura->getAgente()->getUsuario()->getPorcentaje());
                        }
                        $tnFactura->setTipoPorcentajeAgente(TnFactura::TIPO_UTILIDAD);
                        //Calculo el porciento
                        $totalPagar = $importeTasa + $porcientoAsig * $cantRemesas;
                        $tnFactura->setTotalPagarAgente($totalPagar);
                        $tnFactura->setUtilidadFijaAgente(false);
                    } else {
                        //Poniendo los valores con que se crea la factura
                        $porcientoAsig = $grupoPagoAgente->getPorcentaje();
                        $tnFactura->setPorcentajeAsignadoAgente($porcientoAsig);
                        //Calculando el porciento con que opera
                        $porcentaje = $facturaManager->porcientoOperacionAgente($tnFactura->getAgente(), $monedaRemesa);
                        if ($porcentaje != null) {
                            $tnFactura->setPorcentajeOperaAgente($porcentaje);
                        } else {
                            $tnFactura->setPorcentajeOperaAgente($tnFactura->getAgente()->getUsuario()->getPorcentaje());
                        }
                        $tnFactura->setTipoPorcentajeAgente(TnFactura::TIPO_PORCIENTO);
                        //Calculo el porciento
                        $minimo = $grupoPagoAgente->getMinimo();
                        $utilidadFija = $grupoPagoAgente->getUtilidadFija();
                        if ($minimo != null && !is_null($utilidadFija) && $importeTasa < $minimo) {
                            $tipoUtilidad = $grupoPagoAgente->getTipoUtilidad();
                            if ($tipoUtilidad != "" && $tipoUtilidad == NmGrupoPago::TIPO_PORCIENTO) {
                                $totalPagar = $importeTasa + round((($importeTasa * $utilidadFija) / 100), 2);
                                $tnFactura->setUtilidadFijaAgente(false);
                                $tnFactura->setPorcentajeAsignadoAgente($utilidadFija);
                            } else {
                                $totalPagar = $importeTasa + $utilidadFija;
                                $tnFactura->setUtilidadFijaAgente(true);
                                $tnFactura->setPorcentajeAsignadoAgente($utilidadFija);
                            }
                        } elseif ($importeTasa < 100) {
                            $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);
                            $tnFactura->setUtilidadFijaAgente(false);
                        } else {
                            $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);
                            $tnFactura->setUtilidadFijaAgente(false);
                        }
                        $tnFactura->setTotalPagarAgente($totalPagar);
                    }
                    //Actualizando saldo agente
                    if ($importeAnterior != $importe) {
                        $facturaManager->updateSaldoAgente($tnFactura->getAgente(), $monedaRemesa, $importe, $importeAnterior);
                    }
                }
                $grupoPagoAgencia = $entityManager->getRepository(NmGrupoPago::class)->grupoPagoAgencia($user->getAgencia(), $monedaRemesa);

                $porcientoAsig = $grupoPagoAgencia->getPorcentaje();
                $tnFactura->setPorcentajeAsignado($porcientoAsig);
                //Calculando el porciento con que opera
                $porcentaje = $facturaManager->porcientoOperacionAgencia($user->getAgencia(), $monedaRemesa);
                if ($porcentaje != null) {
                    $tnFactura->setPorcentajeOpera($porcentaje);
                } else {
                    $tnFactura->setPorcentajeOpera($user->getPorcentaje());
                }
                $tnFactura->setTipoPorcentaje(TnFactura::TIPO_PORCIENTO);
                //Calculo el porciento
                $minimo = $grupoPagoAgencia->getMinimo();
                $utilidadFija = $grupoPagoAgencia->getUtilidadFija();

                if ($minimo != null && !is_null($utilidadFija) && $importeTasa < $minimo) {
                    $tipoUtilidad = $grupoPagoAgencia->getTipoUtilidad();
                    if ($tipoUtilidad != "" && $tipoUtilidad == NmGrupoPago::TIPO_PORCIENTO) {
                        $totalPagar = $importeTasa + round((($importeTasa * $utilidadFija) / 100), 2);
                        $tnFactura->setUtilidadFija(false);
                        $tnFactura->setPorcentajeAsignado($utilidadFija);
                    } else {
                        $totalPagar = $importeTasa + $utilidadFija;
                        $tnFactura->setUtilidadFija(true);
                        $tnFactura->setPorcentajeAsignado($utilidadFija);
                    }
                } elseif ($importeTasa < 100) {
                    $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);
                    $tnFactura->setUtilidadFija(false);
                } else {
                    $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);
                    $tnFactura->setUtilidadFija(false);
                }

                //Verificar los datos del apoderado
                $apoderado = $entityManager->getRepository(TnApoderado::class)->findOneBySubordinada($user->getAgencia());
                if (!is_null($apoderado)) {
                    $apoderadoFactura = $entityManager->getRepository(TnApoderadoFactura::class)->findOneBy(['factura' => $tnFactura, 'apoderado' => $apoderado]);
                    if (is_null($apoderadoFactura)) { // Si no tiene historial lo creo, si no actualizo
                        $facturaManager->crearApoderadoFactura($apoderado, $tnFactura);
                    } else {
                        $facturaManager->updateApoderadoFactura($apoderado, $tnFactura, $apoderadoFactura);
                    }
                }

                if ($tnFactura->getAgente() == null) {
                    //Actualizando saldo agencia
                    if ($importeAnterior != $importe) {
                        $facturaManager->updateSaldoAgencia($user->getAgencia(), $monedaRemesa, $importe, $importeAnterior);
                    }
                }

                $tnFactura->setTotalPagar($totalPagar);
            } elseif ($checker->isGranted("ROLE_AGENTE")) {
                $tnFactura->setAgencia($user->getAgente()->getAgencia());
                $tnFactura->setAgente($user->getAgente());

                $grupoPagoAgente = $entityManager->getRepository(NmGrupoPagoAgente::class)->grupoPagoAgente($user->getAgente(), $monedaRemesa);
                if ($grupoPagoAgente->getUtilidad()) {//Viendo el que grupo de pago está el agente
                    //Poniendo los valores con que se crea la factura
                    $porcientoAsig = $grupoPagoAgente->getUtilidad();
                    $tnFactura->setPorcentajeAsignadoAgente($porcientoAsig);
                    //Calculando el porciento con que opera
                    $porcentaje = $facturaManager->porcientoOperacionAgente($user->getAgente(), $monedaRemesa);
                    if ($porcentaje != null) {
                        $tnFactura->setPorcentajeOperaAgente($porcentaje);
                    } else {
                        $tnFactura->setPorcentajeOperaAgente($user->getPorcentaje());
                    }
                    $tnFactura->setTipoPorcentajeAgente(TnFactura::TIPO_UTILIDAD);
                    //Calculo el porciento
                    $totalPagar = $importeTasa + $porcientoAsig * $cantRemesas;
                    $tnFactura->setTotalPagarAgente($totalPagar);
                    $tnFactura->setUtilidadFijaAgente(false);
                } else {
                    //Poniendo los valores con que se crea la factura
                    $porcientoAsig = $grupoPagoAgente->getPorcentaje();
                    $tnFactura->setPorcentajeAsignadoAgente($porcientoAsig);
                    //Calculando el porciento con que opera
                    $porcentaje = $facturaManager->porcientoOperacionAgente($user->getAgente(), $monedaRemesa);
                    if ($porcentaje != null) {
                        $tnFactura->setPorcentajeOperaAgente($porcentaje);
                    } else {
                        $tnFactura->setPorcentajeOperaAgente($user->getPorcentaje());
                    }
                    $tnFactura->setTipoPorcentajeAgente(TnFactura::TIPO_PORCIENTO);
                    //Calculo el porciento
                    $minimo = $grupoPagoAgente->getMinimo();
                    $utilidadFija = $grupoPagoAgente->getUtilidadFija();
                    if ($minimo != null && !is_null($utilidadFija) && $importeTasa < $minimo) {
                        $tipoUtilidad = $grupoPagoAgente->getTipoUtilidad();
                        if ($tipoUtilidad != "" && $tipoUtilidad == NmGrupoPago::TIPO_PORCIENTO) {
                            $totalPagar = $importeTasa + round((($importeTasa * $utilidadFija) / 100), 2);
                            $tnFactura->setUtilidadFijaAgente(false);
                            $tnFactura->setPorcentajeAsignadoAgente($utilidadFija);
                        } else {
                            $totalPagar = $importeTasa + $utilidadFija;
                            $tnFactura->setUtilidadFijaAgente(true);
                            $tnFactura->setPorcentajeAsignadoAgente($utilidadFija);
                        }
                    } elseif ($importeTasa < 100) {
                        $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);
                        $tnFactura->setUtilidadFijaAgente(false);
                    } else {
                        $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);
                        $tnFactura->setUtilidadFijaAgente(false);
                    }
                    $tnFactura->setTotalPagarAgente($totalPagar);
                }
                if ($user->getAgente()->getAgencia() != null) {//Verifico si el agente es de una agencia y guardo sus datos
                    $grupoPagoAgencia = $entityManager->getRepository(NmGrupoPago::class)->grupoPagoAgencia($user->getAgente()->getAgencia(), $monedaRemesa);

                    $porcientoAsig = $grupoPagoAgencia->getPorcentaje();
                    $tnFactura->setPorcentajeAsignado($porcientoAsig);
                    //Calculando el porciento con que opera
                    $porcentaje = $facturaManager->porcientoOperacionAgencia($user->getAgente()->getAgencia(), $monedaRemesa);
                    if ($porcentaje != null) {
                        $tnFactura->setPorcentajeOpera($porcentaje);
                    } else {
                        $tnFactura->setPorcentajeOpera($user->getAgente()->getAgencia()->getUsuario() ? $user->getAgente()->getAgencia()->getUsuario()->getPorcentaje() : $porcientoAsig);
                    }
                    $tnFactura->setTipoPorcentaje(TnFactura::TIPO_PORCIENTO);
                    //Calculo el porciento
                    $minimo = $grupoPagoAgencia->getMinimo();
                    $utilidadFija = $grupoPagoAgencia->getUtilidadFija();
                    if ($minimo != null && !is_null($utilidadFija) && $importeTasa < $minimo) {
                        $tipoUtilidad = $grupoPagoAgencia->getTipoUtilidad();
                        if ($tipoUtilidad != "" && $tipoUtilidad == NmGrupoPago::TIPO_PORCIENTO) {
                            $totalPagar = $importeTasa + round((($importeTasa * $utilidadFija) / 100), 2);
                            $tnFactura->setUtilidadFija(false);
                            $tnFactura->setPorcentajeAsignado($utilidadFija);
                        } else {
                            $totalPagar = $importeTasa + $utilidadFija;
                            $tnFactura->setUtilidadFija(true);
                            $tnFactura->setPorcentajeAsignado($utilidadFija);
                        }
                    } elseif ($importeTasa < 100) {
                        $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);
                        $tnFactura->setUtilidadFija(false);
                    } else {
                        $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);
                        $tnFactura->setUtilidadFija(false);
                    }
                    $tnFactura->setTotalPagar($totalPagar);

                    //Verificar los datos del apoderado
                    $apoderado = $entityManager->getRepository(TnApoderado::class)->findOneBySubordinada($user->getAgente()->getAgencia());
                    if (!is_null($apoderado)) {
                        $apoderadoFactura = $entityManager->getRepository(TnApoderadoFactura::class)->findOneBy(['factura' => $tnFactura, 'apoderado' => $apoderado]);
                        if (is_null($apoderadoFactura)) { // Si no tiene historial lo creo, si no actualizo
                            $facturaManager->crearApoderadoFactura($apoderado, $tnFactura);
                        } else {
                            $facturaManager->updateApoderadoFactura($apoderado, $tnFactura, $apoderadoFactura);
                        }
                    }
                } else {
                    $tnFactura->setTotalPagar(0);
                    $tnFactura->setPorcentajeAsignado(0);
                    $tnFactura->setPorcentajeOpera(0);
                    $tnFactura->setTipoPorcentaje(TnFactura::TIPO_PORCIENTO);
                }
                //Actualizando el saldo al agente
                if ($importeAnterior != $importe) {
                    $facturaManager->updateSaldoAgente($user->getAgente(), $monedaRemesa, $importe, $importeAnterior);
                }
            } else {
                //Verificando si es para de una agencia o un agente
                if ($tnFactura->getAgente() != null) {
                    $tnFactura->setAgencia($tnFactura->getAgente()->getAgencia());
                    $grupoPagoAgente = $entityManager->getRepository(NmGrupoPagoAgente::class)->grupoPagoAgente($tnFactura->getAgente(), $monedaRemesa);

                    if ($grupoPagoAgente->getUtilidad()) {//Viendo el que grupo de pago está el agente
                        //Poniendo los valores con que se crea la factura
                        $porcientoAsig = $grupoPagoAgente->getUtilidad();
                        $tnFactura->setPorcentajeAsignadoAgente($porcientoAsig);
                        //Calculando el porciento con que opera
                        $porcentaje = $facturaManager->porcientoOperacionAgente($tnFactura->getAgente(), $monedaRemesa);
                        if ($porcentaje != null) {
                            $tnFactura->setPorcentajeOperaAgente($porcentaje);
                        } else {
                            $tnFactura->setPorcentajeOperaAgente($tnFactura->getAgente()->getUsuario()->getPorcentaje());
                        }
                        $tnFactura->setTipoPorcentajeAgente(TnFactura::TIPO_UTILIDAD);
                        //Calculo el porciento
                        $totalPagar = $importeTasa + $porcientoAsig * $cantRemesas;
                        $tnFactura->setTotalPagarAgente($totalPagar);
                        $tnFactura->setUtilidadFijaAgente(false);
                    } else {
                        //Poniendo los valores con que se crea la factura
                        $porcientoAsig = $grupoPagoAgente->getPorcentaje();
                        $tnFactura->setPorcentajeAsignadoAgente($porcientoAsig);
                        //Calculando el porciento con que opera
                        $porcentaje = $facturaManager->porcientoOperacionAgente($tnFactura->getAgente(), $monedaRemesa);
                        if ($porcentaje != null) {
                            $tnFactura->setPorcentajeOperaAgente($porcentaje);
                        } else {
                            $tnFactura->setPorcentajeOperaAgente($tnFactura->getAgente()->getUsuario()->getPorcentaje());
                        }
                        $tnFactura->setTipoPorcentaje(TnFactura::TIPO_PORCIENTO);
                        //Calculo el porciento o utilidad fija
                        $minimo = $grupoPagoAgente->getMinimo();
                        $utilidadFija = $grupoPagoAgente->getUtilidadFija();
                        if ($minimo != null && !is_null($utilidadFija) && $importeTasa < $minimo) {
                            $tipoUtilidad = $grupoPagoAgente->getTipoUtilidad();
                            if ($tipoUtilidad != "" && $tipoUtilidad == NmGrupoPago::TIPO_PORCIENTO) {
                                $totalPagar = $importeTasa + round((($importeTasa * $utilidadFija) / 100), 2);
                                $tnFactura->setPorcentajeAsignadoAgente($utilidadFija);
                                $tnFactura->setUtilidadFijaAgente(false);
                            } else {
                                $totalPagar = $importeTasa + $utilidadFija;
                                $tnFactura->setPorcentajeAsignadoAgente($utilidadFija);
                                $tnFactura->setUtilidadFijaAgente(true);
                            }
                        } elseif ($importeTasa < 100) {
                            $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);
                            $tnFactura->setUtilidadFijaAgente(false);
                        } else {
                            $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);
                            $tnFactura->setUtilidadFijaAgente(false);
                        }

                        $tnFactura->setTotalPagarAgente($totalPagar);
                    }
                    if ($tnFactura->getAgente()->getAgencia() != null) {//Verifico si el agente es de una agencia y guardo sus datos
                        $grupoPagoAgencia = $entityManager->getRepository(NmGrupoPago::class)->grupoPagoAgencia($tnFactura->getAgente()->getAgencia(), $monedaRemesa);

                        $porcientoAsig = $grupoPagoAgencia->getPorcentaje();
                        $tnFactura->setPorcentajeAsignado($porcientoAsig);
                        //Calculando el porciento con que opera
                        $porcentaje = $facturaManager->porcientoOperacionAgencia($tnFactura->getAgente()->getAgencia(), $monedaRemesa);
                        if ($porcentaje != null) {
                            $tnFactura->setPorcentajeOpera($porcentaje);
                        } else {
                            $tnFactura->setPorcentajeOpera($tnFactura->getAgente()->getAgencia()->getUsuario() ? $tnFactura->getAgente()->getAgencia()->getUsuario()->getPorcentaje() : $porcientoAsig);
                        }
                        $tnFactura->setTipoPorcentaje(TnFactura::TIPO_PORCIENTO);
                        //Calculo el porciento o utilidad fija
                        $minimo = $grupoPagoAgencia->getMinimo();
                        $utilidadFija = $grupoPagoAgencia->getUtilidadFija();
                        if ($minimo != null && !is_null($utilidadFija) && $importeTasa < $minimo) {
                            $tipoUtilidad = $grupoPagoAgencia->getTipoUtilidad();
                            if ($tipoUtilidad != "" && $tipoUtilidad == NmGrupoPago::TIPO_PORCIENTO) {
                                $totalPagar = $importeTasa + round((($importeTasa * $utilidadFija) / 100), 2);
                                $tnFactura->setUtilidadFija(false);
                                $tnFactura->setPorcentajeAsignado($utilidadFija);
                            } else {
                                $totalPagar = $importeTasa + $utilidadFija;
                                $tnFactura->setUtilidadFija(true);
                                $tnFactura->setPorcentajeAsignado($utilidadFija);
                            }
                        } elseif ($importeTasa < 100) {
                            $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);
                            $tnFactura->setUtilidadFija(false);
                        } else {
                            $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);
                            $tnFactura->setUtilidadFija(false);
                        }
                        $tnFactura->setTotalPagar($totalPagar);

                        //Verificar los datos del apoderado
                        $apoderado = $entityManager->getRepository(TnApoderado::class)->findOneBySubordinada($tnFactura->getAgente()->getAgencia());
                        if (!is_null($apoderado)) {
                            $apoderadoFactura = $entityManager->getRepository(TnApoderadoFactura::class)->findOneBy(['factura' => $tnFactura, 'apoderado' => $apoderado]);
                            if (is_null($apoderadoFactura)) { // Si no tiene historial lo creo, si no actualizo
                                $facturaManager->crearApoderadoFactura($apoderado, $tnFactura);
                            } else {
                                $facturaManager->updateApoderadoFactura($apoderado, $tnFactura, $apoderadoFactura);
                            }
                        }
                    } else {
                        $tnFactura->setTotalPagar(0);
                        $tnFactura->setPorcentajeAsignado(0);
                        $tnFactura->setPorcentajeOpera(0);
                        $tnFactura->setTipoPorcentaje(TnFactura::TIPO_PORCIENTO);
                    }

                    //Actualizando saldo agente
                    if ($importeAnterior != $importe) {
                        $facturaManager->updateSaldoAgente($tnFactura->getAgente(), $monedaRemesa, $importe, $importeAnterior);
                    }
                } elseif ($tnFactura->getAgencia() != null) {
                    $grupoPagoAgencia = $entityManager->getRepository(NmGrupoPago::class)->grupoPagoAgencia($tnFactura->getAgencia(), $monedaRemesa);
                    //Poniendo los valores con que se crea la factura
                    $porcientoAsig = $grupoPagoAgencia->getPorcentaje();
                    $tnFactura->setPorcentajeAsignado($porcientoAsig);
                    //Calculando el porciento con que opera
                    $porcentaje = $facturaManager->porcientoOperacionAgencia($tnFactura->getAgencia(), $monedaRemesa);
                    if ($porcentaje != null) {
                        $tnFactura->setPorcentajeOpera($porcentaje);
                    } else {
                        $tnFactura->setPorcentajeOpera($tnFactura->getAgencia()->getUsuario()->getPorcentaje());
                    }
                    $tnFactura->setTipoPorcentaje(TnFactura::TIPO_PORCIENTO);

                    //Calculo el porciento o utilidad fija
                    $minimo = $grupoPagoAgencia->getMinimo();
                    $utilidadFija = $grupoPagoAgencia->getUtilidadFija();
                    if ($minimo != null && !is_null($utilidadFija) && $importeTasa < $minimo) {
                        $tipoUtilidad = $grupoPagoAgencia->getTipoUtilidad();
                        if ($tipoUtilidad != "" && $tipoUtilidad == NmGrupoPago::TIPO_PORCIENTO) {
                            $totalPagar = $importeTasa + round((($importeTasa * $utilidadFija) / 100), 2);
                            $tnFactura->setPorcentajeAsignado($utilidadFija);
                            $tnFactura->setUtilidadFija(false);
                        } else {
                            $totalPagar = $importeTasa + $utilidadFija;
                            $tnFactura->setPorcentajeAsignado($utilidadFija);
                            $tnFactura->setUtilidadFija(true);
                        }
                    } elseif ($importeTasa < 100) {
                        $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);
                        $tnFactura->setUtilidadFija(false);
                    } else {
                        $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);
                        $tnFactura->setUtilidadFija(false);
                    }
                    $tnFactura->setTotalPagar($totalPagar);

                    //Actualizando saldo agencia
                    if ($importeAnterior != $importe) {
                        $facturaManager->updateSaldoAgencia($tnFactura->getAgencia(), $monedaRemesa, $importe, $importeAnterior);
                    }

                    //Verificar los datos del apoderado
                    $apoderado = $entityManager->getRepository(TnApoderado::class)->findOneBySubordinada($tnFactura->getAgencia());
                    if (!is_null($apoderado)) {
                        $apoderadoFactura = $entityManager->getRepository(TnApoderadoFactura::class)->findOneBy(['factura' => $tnFactura, 'apoderado' => $apoderado]);
                        if (is_null($apoderadoFactura)) { // Si no tiene historial lo creo, si no actualizo
                            $facturaManager->crearApoderadoFactura($apoderado, $tnFactura);
                        } else {
                            $facturaManager->updateApoderadoFactura($apoderado, $tnFactura, $apoderadoFactura);
                        }
                    }
                } else {
                    //Poniendo los valores con que se crea la factura
                    $porcientoAsig = $configurationManager->get(TnConfiguration::PORCENTAJE);
                    $tnFactura->setPorcentajeAsignado($porcientoAsig);
                    $tnFactura->setPorcentajeOpera($user->getPorcentaje());
                    $tnFactura->setTipoPorcentaje(TnFactura::TIPO_PORCIENTO);
                    //Calculo el porciento
                    if ($porcientoAsig > 0) {
                        if ($importeTasa < 100) {
                            $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);
                        } else {
                            $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);
                        }
                    } else {
                        $totalPagar = 0;
                    }
                    $tnFactura->setUtilidadFija(false);
                    $tnFactura->setTotalPagar($totalPagar);
                }
            }

            if ($tnFactura->getAgencia() != null && $tnFactura->getAgencia()->getRetenida() && !$tnFactura->getRetenida()) {//Verificando si hay que marcar la factura como retenida
                $tnFactura->setRetenida(true);
            } else {
                $tnFactura->setRetenida(false);
            }

            $entityManager->persist($tnFactura);
            $entityManager->flush();

            return $this->redirectToRoute('tn_factura_index');
        }

        return $this->render('backend/tn_factura/edit.html.twig', [
            'tn_factura' => $tnFactura,
            'porciento' => $user->getPorcentaje(),
            'form' => $form->createView(),
        ]);
    }

    private function deleteCollections($em, $init, $final, $facturaManager)
    {
        foreach ($init as $item) {
            $find = false;
            foreach ($final as $item2) {
                if ($item->getId() == $item2->getId()) {
                    $find = true;
                    break;
                }
            }
            if ($find == false) {
                //Verificando primero antes de eliminar
                $facturaManager->eliminarHistorialRemesa($item);
                $em->remove($item);
            }
        }
    }

    private function addCollections($init, $final, $facturaManager)
    {
        foreach ($final as $item) {
            $find = false;
            foreach ($init as $item2) {
                if ($item->getId() == $item2->getId()) {
                    $find = true;
                    break;
                }
            }
            if ($find == false) {
                //Creando el hostirial de distribución de la nueva remesa
                $facturaManager->crearHistorialDistribucion($item);
            }
        }
    }

    /**
     * @Route("/destinatarios/emisor", name="tn_factura_destinatarios_emisor")
     * @Method({"GET", "POST"})
     */
    public function destinatariosEmisor(Request $request, TnEmisorRepository $emisorRepository)
    {
        $em = $this->getDoctrine()->getManager();
        if ($request->isXmlHttpRequest()) {
            $idEmisor = $request->get('emisor');
            if (isset($idEmisor) && $request->get('emisor') != "") {
                return $this->render('backend/tn_factura/destinatarios_emisor.html.twig', array(
                    'emisor' => $emisorRepository->find($request->get('emisor')),
                    'destinatarios' => $em->getRepository(TnDestinatario::class)->findDestinatarioEmisor($request->get('emisor'))
                ));
            } else {
                return $this->render('backend/tn_factura/destinatarios_emisor.html.twig', array());
            }
        }
    }

    /**
     * @Route("/ajax/data/emisor", name="admin_reload_destinatarios_emisor")
     * @Method({"GET", "POST"})
     */
    public function reloadDestinatariosEmisorAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        if ($request->isXmlHttpRequest()) {
            $idEmisor = $request->get('idEmisor');
            $destinatarios = $em->getRepository(TnDestinatario::class)->findBy(array('emisor' => $idEmisor, 'enabled' => true), ['created' => 'DESC']);

            $result = array();
            foreach ($destinatarios as $destinatario) {
                $temp['value'] = $destinatario->getId();
                $temp['text'] = $destinatario->getNombre() . " " . $destinatario->getApellidos();
                $result[] = $temp;
            }
            $response = new Response();
            $response->setContent(json_encode(array('destinatarios' => $result)));

            return $response;
        }
    }

    /**
     * @Route("/ajax/verificar/destino", name="admin_reload_verificar_moneda_destino")
     * @Method({"GET", "POST"})
     */
    public function verificarMonedaDestinoAction(Request $request, FacturaManager $facturaManager)
    {
        $em = $this->getDoctrine()->getManager();
        if ($request->isXmlHttpRequest()) {
            $idDestinatario = $request->get('idDestinatario');
            $idMoneda = $request->get('idMoneda');

            $destinatario = $em->getRepository(TnDestinatario::class)->find($idDestinatario);
            $moneda = $em->getRepository(NmMoneda::class)->find($idMoneda);

            if ($destinatario != null && $moneda != null && $moneda->getSimbolo() == NmMoneda::CURRENCY_EUR) {
                if (!$facturaManager->validarDestinatarioMonedaProvincia($destinatario, $moneda)) {
                    return new JsonResponse(array('success' => false));
                }
            }

            return new JsonResponse(array('success' => true));
        }
    }

    /**
     * @Route("/reload/ajax/data", name="admin_reload_emisores_agencia_agente")
     * @Method({"GET", "POST"})
     */
    public function reloadEmisoresAgenciaAgente(Request $request)
    {
        $tnUser = $this->getUser();
        $securityChecker = $this->get('security.authorization_checker');
        $em = $this->getDoctrine()->getManager();
        if ($request->isXmlHttpRequest()) {
            $type = $request->get('type');
            $value = $request->get('value');
            $groupBy = '';
            if ($type == 'agente') {
                $tnAgente = $em->getRepository(TnAgente::class)->find($value);
                if ($tnAgente && $tnAgente->getUsuario()) {
                    $emisores = $em->getRepository(TnEmisor::class)->findBy(array('usuario' => $tnAgente->getUsuario(), 'enabled' => true), ['created' => 'DESC']);
                }
            } elseif ($type == 'agencia') {
                $tnAgencia = $em->getRepository(TnAgencia::class)->find($value);
                if ($tnAgencia && $tnAgencia->getUsuario()) {
                    $emisores = $em->getRepository(TnEmisor::class)->findEmisoresAgencia($tnAgencia->getUsuario());
                }
            } else {
                if ($securityChecker->isGranted("ROLE_SUPER_ADMIN")) {
                    $emisores = $em->getRepository(TnEmisor::class)->findBy(array('enabled' => true), ['created' => 'DESC']);
                } else {
                    $emisores = $em->getRepository(TnEmisor::class)->findEmisoresAgencia($tnUser);
                }
            }
            $result = array();
            foreach ($emisores as $emisor) {
                $usuario = $emisor->getUsuario();
                if ($usuario != null) {
                    if ($usuario->getAgencia() != null) {
                        $key = $usuario->getAgencia()->getNombre();
                    } elseif ($usuario->getAgente()) {
                        $key = $usuario->getAgente()->getNombre();
                    } else {
                        $key = $usuario->getUsername();
                    }
                    if (array_key_exists($key, $result)) {
                        $temp['value'] = $emisor->getId();
                        $temp['text'] = $emisor->getNombre() . " " . $emisor->getApellidos() . " - " . $emisor->getPhone();
                        $result[$key][] = $temp;
                    } else {
                        $temp['value'] = $emisor->getId();
                        $temp['text'] = $emisor->getNombre() . " " . $emisor->getApellidos() . " - " . $emisor->getPhone();
                        $result[$key] = [$temp];
                    }
                }

            }
            $response = new Response();
            $response->setContent(json_encode(array('emisores' => $result)));

            return $response;
        }
    }

    /**
     *
     * @Route("/remesas/{token}", name="admin_factura_detalles_remesas")
     * @ParamConverter("tnFactura", options={"mapping": {"token": "token"}})
     * @Method("GET")
     */
    public function detallesRemesas(TnFactura $tnFactura)
    {
        return $this->render('backend/tn_factura/show.html.twig', array(
            'tnFactura' => $tnFactura,
        ));
    }

    /**
     *
     * @Route("/cancel/state", name="admin_factura_cancel_ajax")
     * @Method({"GET", "POST"})
     */
    public function cancelEstadoAjax(Request $request, FacturaManager $facturaManager)
    {
        $em = $this->getDoctrine()->getManager();
        if ($request->isXmlHttpRequest()) {
            $tnFactura = $em->getRepository(TnFactura::class)->find($request->get('idFactura'));
            $estado = $em->getRepository(NmEstado::class)->findOneBy(array('codigo' => $request->get('estado')));

            foreach ($tnFactura->getRemesas() as $remesa) {//Eliminando la remsas del reporte de envío al cancelarla.
                if ($remesa->getReporteEnvio() != null) {
                    $remesa->setReporteEnvio(null);
                    $em->persist($remesa);
                }
            }
            //Cancelando los valores de los créditos de los distribuidores
            $facturaManager->cancelarHistorialFactura($tnFactura);
            $monedaRemesa = $em->getRepository(NmMoneda::class)->findOneBy(['simbolo' => $tnFactura->getMoneda()]);
            if ($tnFactura->getAgente() != null) {
                $facturaManager->restablecerSaldoAgente($tnFactura->getAgente(), $monedaRemesa, $tnFactura->getImporte());
            } elseif ($tnFactura->getAgencia() != null) {
                $facturaManager->restablecerSaldoAgencia($tnFactura->getAgencia(), $monedaRemesa, $tnFactura->getImporte());
            }

            $tnFactura->setEstado($estado);
            $em->persist($tnFactura);
            $em->flush();

            $html = '<span class="label label-danger">Cancelada</span>';

            $actions = '<a class="btn btn-warning btn-xs" data-toggle="tooltip"
                                                       title="Descargar factura"
                                                       href="#"><i class="fa fa-print"></i></a>';

            return new JsonResponse(array('success' => true, 'html' => $html, 'actions' => $actions));
        }
    }

    /**
     * @Route("/aprobar/facturas", name="admin_factura_aprobar")
     */
    public function aprobarFacturas(Request $request): Response
    {
        $form = $this->createForm(FacturaAprobarType::class, null, [
            'action' => $this->generateUrl('admin_factura_aprobacion')
        ]);

        return $this->render('backend/tn_factura/aprobar.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/aprobacion", name="admin_factura_aprobacion", methods={"GET","POST"})
     */
    public function aprobacionFacturas(Request $request, NmEstadoRepository $estadoRepository, FacturaManager $facturaManager): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $form = $this->createForm(FacturaAprobarType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $facturas = $form->get('facturas')->getData();
            $estadoAprobada = $estadoRepository->findOneBy(array('codigo' => NmEstado::ESTADO_APROBADA));
            $estadoDistribucion = $estadoRepository->findOneBy(array('codigo' => NmEstado::ESTADO_DISTRIBUCION));
            foreach ($facturas as $factura) {
                $count = 0;
                foreach ($factura->getRemesas() as $remesa) {
                    if ($remesa->getDistribuidor() != null) {
                        $count++;
                        //Creando el historial de distribución de la remesa
                        $facturaManager->crearHistorialDistribucion($remesa);
                    }
                }
                if (count($factura->getRemesas()) == $count) {
                    $factura->setEstado($estadoDistribucion);
                } else {
                    $factura->setEstado($estadoAprobada);
                }
                $entityManager->persist($factura);
            }
            $entityManager->flush();

            $this->get('session')->getFlashBag()->add('success', 'Facturas aprobadas correctamente.');

            return $this->redirectToRoute('admin_factura_distribuir');
        }

        return $this->render('backend/tn_factura/aprobar.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/distribuir/facturas", name="admin_factura_distribuir")
     */
    public function distribuirFacturas(TnFacturaRepository $tnFacturaRepository, AuthorizationCheckerInterface $authorizationChecker): Response
    {
        if ($authorizationChecker->isGranted("ROLE_SUPER_ADMIN") || $authorizationChecker->isGranted("ROLE_ADMIN")) {
            $query = $tnFacturaRepository->createQueryBuilder('f')
                ->join('f.estado', 'estado')
                ->where('estado.codigo = :cod')
                ->setParameter('cod', NmEstado::ESTADO_APROBADA)
                ->orderBy('f.no_factura', "ASC");

            return $this->render('backend/tn_factura/distribuir.html.twig', [
                'tn_facturas' => $query->getQuery()->getResult(),
            ]);
        } else {
            $this->redirectToRoute('tn_factura_index');
        }
    }


    /**
     * @Route("/distribucion/{token}/factura", name="admin_distribucion_factura")
     * @ParamConverter("tnFactura", options={"mapping": {"token": "token"}})
     */
    public function distribucionFactura(Request $request, TnFactura $tnFactura, NmEstadoRepository $estadoRepository, FacturaManager $facturaManager): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $form = $this->createForm(FacturaDistribuirType::class, $tnFactura, [
            'action' => $this->generateUrl('admin_distribucion_factura', ['token' => $tnFactura->getToken()])
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $estadoDistribucion = $estadoRepository->findOneBy(array('codigo' => NmEstado::ESTADO_DISTRIBUCION));

            if ($facturaManager->findAsignacionesZona($tnFactura) == 'primary') {
                foreach ($tnFactura->getRemesas() as $remesa) {//Creando el historial de distribución de las remesas
                    if ($remesa->getHistorialDistribuidor() == null) {
                        //Creando el historial de distribución de la remesa
                        $facturaManager->crearHistorialDistribucion($remesa);
                    }
                }

                $tnFactura->setEstado($estadoDistribucion);
                $entityManager->persist($tnFactura);
                $entityManager->flush();

                $this->get('session')->getFlashBag()->add('success', 'Factura ' . $tnFactura->getNoFactura() . ' cambiada a distribución correctamente.');
                return $this->redirectToRoute('admin_factura_distribuir');
            } else {
                $this->get('session')->getFlashBag()->add('error', 'La factura no pudo ser cambiada a distribución, algunas remesas no tienen distribuidor.');
                return $this->redirectToRoute('admin_distribucion_factura', ['token' => $tnFactura->getToken()]);
            }
        }

        return $this->render('backend/tn_factura/distribucion_factura.html.twig', [
            'form' => $form->createView(),
            'tnFactura' => $tnFactura,
        ]);
    }

    /**
     * @Route("/export_factura/{token}/{action}/pdf", name="admin_factura_export_pdf", methods={"GET"}, requirements={"action"="download|print"})
     * @ParamConverter("factura", options={"mapping": {"token": "token"}})
     */
    public function exportNotaCreditoPdf(Request $request, TcPdfManager $tcPdfManager, TnFactura $factura, $action): Response
    {
        $html = $this->renderView('backend/tn_factura/factura_pdf.html.twig', array(
            'factura' => $factura,
            'emisor' => $factura->getEmisor(),
            'remesas' => $factura->getRemesas(),
        ));

        if ($action == "download") {
            $tcPdfManager->getDocument('QM_Factura_' . $factura->getNoFactura(), $html, 'D');

        } elseif ($action == "print") {
            $tcPdfManager->getDocument('QM_Factura_' . $factura->getNoFactura(), $html, 'I');
        }

        return new $this->createNotFoundException();
    }


    /**
     * @Route("/logs/{token}/factura", name="admin_factura_logs_show", methods={"GET"})
     * @ParamConverter("tnFactura", options={"mapping": {"token": "token"}})
     */
    public function adminLogsFactura(TnFactura $tnFactura): Response
    {
        $repository = $this->getDoctrine()->getManager()->getRepository(LogEntry::class);
        $logs = $repository->getLogEntries($tnFactura);

        return $this->render('backend/tn_factura/logs.html.twig', [
            'tnFactura' => $tnFactura,
            'logs' => $logs
        ]);
    }

    /**
     * @Route("change/{id}/fecha/contable", name="admin_factura_change_fecha_contable")
     * @Method({"GET", "POST"})
     */
    public function changeFechaContableFactura(Request $request, TnFactura $tnFactura)
    {
        $em = $this->getDoctrine()->getManager();

        if ($request->isMethod("POST")) {

            $fecha = new \DateTime($request->get('fechaContable'));
            $tnFactura->setFechaContable($fecha);
            $em->persist($tnFactura);
            $em->flush();

            return $this->redirectToRoute('tn_factura_index');
        }

        return $this->render('backend/tn_factura/fecha_contable.html.twig', array(
            'tnFactura' => $tnFactura,
        ));
    }


    /**
     * @Route("/fecha/contable/ajax", name="admin_factura_change_fecha_contable_ajax")
     * @Method({"GET", "POST"})
     */
    public function changeFechaContableFacturaAjax(Request $request, TnFacturaRepository $facturaRepository)
    {
        $em = $this->getDoctrine()->getManager();

        if ($request->isXmlHttpRequest()) {
            $tnFactura = $facturaRepository->find($request->get('factura'));
            $fecha = new \DateTime($request->get('fechaContable'));
            $tnFactura->setFechaContable($fecha);
            $em->persist($tnFactura);
            $em->flush();

            //Para cambiar el div de la fecha contable....
            $btn = $fecha->format("d/m/Y") . ' <a href="' . $this->generateUrl('admin_factura_change_fecha_contable', ['id' => $tnFactura->getId()]) . '"
                                                       data-target="#modal-form"
                                                       class="btn btn-xs btn-primary btn-circle btn-outline show-option"
                                                       title="Modificar fecha contable"><i
                                                                class="fa fa-calendar"></i></a>';

            return new JsonResponse(array('success' => true, 'msg' => 'La fecha contable de la factura fue cambiada con éxito.', 'div_content' => $btn));
        }
    }

    /**
     * @Route("/agencia/porciento/operacion", name="admin_agencia_porciento_operacion_ajax")
     * @Method({"GET", "POST"})
     */
    public function agenciaPorcientoOperacion(Request $request, TnAgenciaRepository $tnAgenciaRepository, NmMonedaRepository $nmMonedaRepository)
    {
        $em = $this->getDoctrine()->getManager();
        if ($request->isXmlHttpRequest()) {
            $idAgencia = $request->get('idAgencia');
            $idMoneda = $request->get('idMoneda');

            $tnAgencia = $tnAgenciaRepository->find($idAgencia);
            $nmMoneda = $nmMonedaRepository->find($idMoneda);

            $nmGrupoPago = $em->getRepository(NmGrupoPago::class)->grupoPagoAgencia($tnAgencia, $nmMoneda);
            $porcentajeOperacion = $em->getRepository(TnOperacionAgencia::class)->findOneBy(['agencia' => $tnAgencia, 'grupoPago' => $nmGrupoPago]);
            if (!is_null($porcentajeOperacion) && !is_null($porcentajeOperacion->getPorcentaje())) {
                return new JsonResponse(array('success' => true, 'valor' => $porcentajeOperacion->getPorcentaje()));
            } else {
                return new JsonResponse(array('success' => false, 'valor' => 'Undefined'));
            }
        }
    }

    /**
     * @Route("/agente/porciento/operacion", name="admin_agente_porciento_operacion_ajax")
     * @Method({"GET", "POST"})
     */
    public function agentePorcientoOperacion(Request $request, TnAgenteRepository $tnAgenteRepository, NmMonedaRepository $nmMonedaRepository)
    {
        $em = $this->getDoctrine()->getManager();
        if ($request->isXmlHttpRequest()) {
            $idAgente = $request->get('idAgente');
            $idMoneda = $request->get('idMoneda');

            $tnAgente = $tnAgenteRepository->find($idAgente);
            $nmMoneda = $nmMonedaRepository->find($idMoneda);

            $nmGrupoPago = $em->getRepository(NmGrupoPagoAgente::class)->grupoPagoAgente($tnAgente, $nmMoneda);
            $porcentajeOperacion = $em->getRepository(TnOperacionAgente::class)->findOneBy(['agente' => $tnAgente, 'grupoPagoAgente' => $nmGrupoPago]);
            if (!is_null($porcentajeOperacion) && !is_null($porcentajeOperacion->getPorcentaje())) {
                return new JsonResponse(array('success' => true, 'valor' => $porcentajeOperacion->getPorcentaje()));
            } else {
                return new JsonResponse(array('success' => false, 'valor' => 'Undefined'));
            }
        }
    }

    /**
     * @Route("/remove/sospecha", name="admin_factura_remove_sospecha")
     * @Method({"GET", "POST"})
     */
    public function removeSospecha(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        if ($request->isXmlHttpRequest()) {
            $data = $request->request->all();
            $data = json_decode($data['datos'], true);

            $tnFactura = $em->getRepository(TnFactura::class)->find($data['factura']);
            if (!is_null($tnFactura)) {
                $tnFactura->setSospechosa(false);
                $em->persist($tnFactura);
                $em->flush();

                return new JsonResponse(array('success' => true));
            } else {
                return new JsonResponse(array('success' => false));
            }
        }
    }

    /**
     * @Route("/remove/retenida", name="admin_factura_remove_retenida")
     * @Method({"GET", "POST"})
     */
    public function removeRetenida(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        if ($request->isXmlHttpRequest()) {
            $data = $request->request->all();
            $data = json_decode($data['datos'], true);

            $tnFactura = $em->getRepository(TnFactura::class)->find($data['factura']);
            if (!is_null($tnFactura)) {
                $tnFactura->setRetenida(false);
                $em->persist($tnFactura);
                $em->flush();

                return new JsonResponse(array('success' => true));
            } else {
                return new JsonResponse(array('success' => false));
            }
        }
    }
}
