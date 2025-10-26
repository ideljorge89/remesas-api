<?php

namespace App\Controller\Backend\Nomencladores;

use App\Entity\NmEstadoTransferencia;
use App\Entity\NmGrupoPagoTransf;
use App\Entity\NmGrupoPagoTransfAgente;
use App\Entity\TnAgente;
use App\Entity\TnConfiguration;
use App\Entity\TnDestinatario;
use App\Entity\TnOperacionAgenciaTransf;
use App\Entity\TnOperacionAgenteTransf;
use App\Entity\TnRepartidor;
use App\Entity\TnReporteTransferencia;
use App\Entity\TnTransferencia;
use App\Entity\TnUser;
use App\Form\TnRepartidorType;
use App\Form\TnTransferenciaType;
use App\Form\Type\AdvancedSerachAsignarType;
use App\Form\Type\AdvancedSerachTransfType;
use App\Form\Type\PendientesAsignarType;
use App\Form\Type\ReporteRepartidorType;
use App\Manager\ConfigurationManager;
use App\Manager\FacturaManager;
use App\Manager\TcPdfManager;
use App\Model\AsignarTransferenciaModel;
use App\Model\ReporteEnvioTransferenciaModel;
use App\Repository\NmEstadoTransferenciaRepository;
use App\Repository\NmMonedaRepository;
use App\Repository\TnAgenciaRepository;
use App\Repository\TnAgenteRepository;
use App\Repository\TnRepartidorRepository;
use App\Repository\TnReporteEnvioRepository;
use App\Repository\TnReporteTransferenciaRepository;
use App\Repository\TnTransferenciaRepository;
use Gedmo\Loggable\Entity\LogEntry;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @Route("/transferencia")
 */
class TnTransferenciaController extends AbstractController
{
    /**
     * @Route("/", name="tn_transferencia_index", methods={"GET"})
     */
    public function index(TnTransferenciaRepository $tnTransferenciaRepository, PaginatorInterface $paginator, TnAgenteRepository $tnAgenteRepository, AuthorizationCheckerInterface $authorizationChecker): Response
    {
        $tnUser = $this->getUser();
        if ($tnUser instanceof TnUser) {
            if ($authorizationChecker->isGranted("ROLE_AGENCIA")) {
                $agentesIds = $tnAgenteRepository->findAgentesAgenciaIds($tnUser->getAgencia());

                $query = $tnTransferenciaRepository->createQueryBuilder('tt')
                    ->where('tt.agencia = :ag or (tt.agente is not null and tt.agente IN (:ids))')
                    ->setParameter('ag', $tnUser->getAgencia())
                    ->setParameter('ids', $agentesIds)
                    ->orderBy('tt.created', "DESC")
                    ->getQuery();
            } elseif ($authorizationChecker->isGranted("ROLE_AGENTE")) {
                $tnAgente = $tnUser->getAgente();
                if ($tnAgente->getTipoAgente() == TnAgente::TIPO_EXTERNO) {
                    $query = $tnTransferenciaRepository->createQueryBuilder('tt')
                        ->where('tt.agente = :ag')
                        ->setParameter('ag', $tnUser->getAgente())
                        ->orderBy('tt.created', "DESC")
                        ->getQuery();
                } else {
                    $userAgencia = $tnAgente->getAgencia();
                    $query = $tnTransferenciaRepository->createQueryBuilder('tt')
                        ->where('tt.agente = :ag or tt.agencia = :agc')
                        ->setParameter('ag', $tnUser->getAgente())
                        ->setParameter('agc', $userAgencia)
                        ->orderBy('tt.created', "DESC")
                        ->getQuery();
                }

            } else {
                $query = $tnTransferenciaRepository->createQueryBuilder('f')->orderBy('f.created', "DESC")->getQuery();
            }
        }

        $form = $this->createForm(AdvancedSerachTransfType::class);

        $request = $this->get('request_stack')->getCurrentRequest();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            100
        );

        return $this->render('backend/tn_transferencia/index.html.twig', [
            'form' => $form->createView(),
            'tn_transferencias' => $pagination,
        ]);
    }

    /**
     * @Route("/advanced_search", name="tn_transferencia_advanced_search")
     * @Method({"POST"})
     */
    public function advancedSearch(Request $request, TnTransferenciaRepository $tnTransferenciaRepository)
    {
        $tnUser = $this->getUser();
        $data = $request->request->all();
        $authorizationChecker = $this->get('security.authorization_checker');
        if ($authorizationChecker->isGranted("ROLE_AGENCIA")) {
            $transferencias = $tnTransferenciaRepository->advancedSearchAgencia($data['advanced_serach_transf'], $tnUser->getAgencia());
        } elseif ($authorizationChecker->isGranted("ROLE_AGENTE")) {
            $transferencias = $tnTransferenciaRepository->advancedSearchAgente($data['advanced_serach_transf'], $tnUser->getAgente());
        } else {
            $transferencias = $tnTransferenciaRepository->advancedSearchAdmin($data['advanced_serach_transf']);
        }

        return $this->render('backend/tn_transferencia/search_results.html.twig', [
            'tn_transferencias' => $transferencias
        ]);
    }

    /**
     * @Route("/new", name="tn_transferencia_new", methods={"GET","POST"})
     */
    public function new(Request $request, ConfigurationManager $configurationManager, FacturaManager $facturaManager, AuthorizationCheckerInterface $authorizationChecker): Response
    {
        $entityManager = $this->getDoctrine()->getManager();

        $statusFactura = $configurationManager->get(TnConfiguration::STATUS_NEW_FACTURA);
        if ($statusFactura == 'HABILITADO') {

            $user = $this->getUser();
            if ($user->getPorcentaje() == null || $user->getPorcentaje() == 0) {
                $this->get('session')->getFlashBag()->add('error', 'Debe especificar el porciento a operar para registrar/editar transferencias.');

                if($authorizationChecker->isGranted("ROLE_AGENCIA")){
                    return $this->redirectToRoute('user_backend_agencia_editar_porcentajes_transferencias');
                }elseif ($authorizationChecker->isGranted("ROLE_AGENTE")){
                    return $this->redirectToRoute('user_backend_agente_editar_porcentajes_transferencias');
                }

                return $this->redirectToRoute('nm_grupo_pago_transf_index');
            }

            $tnTransferencia = new TnTransferencia();
            if (!$user->getAuth()) {
                $tnTransferencia->setAuth(false);
            }

            $form = $this->createForm(TnTransferenciaType::class, $tnTransferencia);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                if (!$user->getAuth()) {
                    $tnTransferencia->setTotalCobrar(0.0);
                }

                //Verificando los valores y poniendo los que van
                $user = $this->getUser();
                $checker = $this->get('security.authorization_checker');

                $tnTransferencia->setMonto($tnTransferencia->getImporte());

                //Guardando la moneda y la tasa
                $monedaTransferencia = $tnTransferencia->getMoneda();
                $tasa = $monedaTransferencia->getTasaCambio();

                $importeTasa = round($tnTransferencia->getImporte() / $tasa); //Importe por el que se debe calcular los porcientos y demás.

                if ($checker->isGranted("ROLE_AGENCIA")) {
                    $tnTransferencia->setAgencia($user->getAgencia());
                    if ($tnTransferencia->getAgente() != null) {
                        try {
                            $grupoPagoAgente = $entityManager->getRepository(NmGrupoPagoTransfAgente::class)->grupoPagoAgente($tnTransferencia->getAgente(), $monedaTransferencia);
                            if ($grupoPagoAgente) {
                                //Poniendo los valores con que se crea la factura
                                $porcientoAsig = $grupoPagoAgente->getPorcentaje();
                                $tnTransferencia->setPorcentajeAsignadoAgente($porcientoAsig);
                                //Buscando el porcentaje configurado
                                $porcentaje = $facturaManager->porcientoOperacionAgenteTransferencia($tnTransferencia->getAgente(), $monedaTransferencia);
                                if ($porcentaje != null) {
                                    $tnTransferencia->setPorcentajeOperaAgente($porcentaje);
                                } else {
                                    $tnTransferencia->setPorcentajeOperaAgente($tnTransferencia->getAgente()->getUsuario()->getPorcentaje());
                                }

                                $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);

                                $tnTransferencia->setTotalPagarAgente($totalPagar);
                            } else {
                                throw new \Exception("Error grupo de pago");
                            }

                        } catch (\Exception $e) {
                            $form->get('importe')->addError(new FormError("El Agente no tiene configurado las transferencias."));
                            $form->isValid();
                        }
                    }
                    try {
                        $grupoPagoAgencia = $entityManager->getRepository(NmGrupoPagoTransf::class)->grupoPagoAgencia($user->getAgencia(), $monedaTransferencia);
                        if ($grupoPagoAgencia) {
                            //Poniendo los valores con que se crea la factura
                            $porcientoAsig = $grupoPagoAgencia->getPorcentaje();
                            $tnTransferencia->setPorcentajeAsignado($porcientoAsig);
                            //Buscando el porcentaje configurado
                            $porcentaje = $facturaManager->porcientoOperacionAgenciaTransferencia($user->getAgencia(), $monedaTransferencia);
                            if ($porcentaje != null) {
                                $tnTransferencia->setPorcentajeOpera($porcentaje);
                            } else {
                                $tnTransferencia->setPorcentajeOpera($user->getPorcentaje());
                            }

                            $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);
                            $tnTransferencia->setTotalPagar($totalPagar);

                            $estado = $entityManager->getRepository(NmEstadoTransferencia::class)->findOneBy(['codigo' => NmEstadoTransferencia::ESTADO_PENDIENTE]);
                            $tnTransferencia->setEstado($estado);
                        } else {
                            throw new \Exception("Error grupo de pago");
                        }
                    } catch (\Exception $e) {
                        $form->get('importe')->addError(new FormError("La Agencia no tiene configurado las transferencias."));
                        $form->isValid();
                    }

                } elseif ($checker->isGranted("ROLE_AGENTE")) {
                    try {
                        $grupoPagoAgente = $entityManager->getRepository(NmGrupoPagoTransfAgente::class)->grupoPagoAgente($user->getAgente(), $monedaTransferencia);
                        if ($grupoPagoAgente) {
                            $tnTransferencia->setAgencia($user->getAgente()->getAgencia());
                            $tnTransferencia->setAgente($user->getAgente());
                            //Poniendo los valores con que se crea la factura
                            $porcientoAsig = $grupoPagoAgente->getPorcentaje();
                            $tnTransferencia->setPorcentajeAsignadoAgente($porcientoAsig);
                            //Buscando el porcentaje configurado
                            $porcentaje = $facturaManager->porcientoOperacionAgenteTransferencia($user->getAgente(), $monedaTransferencia);
                            if ($porcentaje != null) {
                                $tnTransferencia->setPorcentajeOperaAgente($porcentaje);
                            } else {
                                $tnTransferencia->setPorcentajeOperaAgente($user->getPorcentaje());
                            }

                            $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);
                            $tnTransferencia->setTotalPagarAgente($totalPagar);

                            $estado = $entityManager->getRepository(NmEstadoTransferencia::class)->findOneBy(['codigo' => NmEstadoTransferencia::ESTADO_PENDIENTE]);
                            $tnTransferencia->setEstado($estado);
                        } else {
                            throw new \Exception("Error grupo de pago");
                        }
                    } catch (\Exception $e) {
                        $form->get('importe')->addError(new FormError("El Agente no tiene configurado las transferencias."));
                        $form->isValid();
                    }

                    if ($user->getAgente()->getAgencia() != null) {//Verifico si el agente es de una agencia y guardo sus datos
                        try {
                            $grupoPagoAgencia = $entityManager->getRepository(NmGrupoPagoTransf::class)->grupoPagoAgencia($user->getAgente()->getAgencia(), $monedaTransferencia);
                            if ($grupoPagoAgencia) {
                                $porcientoAsig = $grupoPagoAgencia->getPorcentaje();
                                $tnTransferencia->setPorcentajeAsignado($porcientoAsig);
                                //Buscando el porcentaje configurado
                                $porcentaje = $facturaManager->porcientoOperacionAgenciaTransferencia($user->getAgente()->getAgencia(), $monedaTransferencia);
                                if ($porcentaje != null) {
                                    $tnTransferencia->setPorcentajeOpera($porcentaje);
                                } else {
                                    $tnTransferencia->setPorcentajeOpera($user->getAgente()->getAgencia()->getUsuario() ? $user->getAgente()->getAgencia()->getUsuario()->getPorcentaje() : $porcientoAsig);
                                }
                            } else {
                                throw new \Exception("Error grupo de pago");
                            }
                        } catch (\Exception $e) {
                            $form->get('importe')->addError(new FormError("La Agencia a la que pertecene el agente no tiene configurado las transferencias."));
                            $form->isValid();
                        }

                        $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);
                        $tnTransferencia->setTotalPagar($totalPagar);
                    } else {
                        $tnTransferencia->setTotalPagar(0);
                        $tnTransferencia->setPorcentajeAsignado(0);
                        $tnTransferencia->setPorcentajeOpera(0);
                    }
                } else {
                    //Verificando si es para de una agencia o un agente
                    if ($tnTransferencia->getAgente() != null) {
                        try {
                            $grupoPagoAgente = $entityManager->getRepository(NmGrupoPagoTransfAgente::class)->grupoPagoAgente($tnTransferencia->getAgente(), $monedaTransferencia);
                            if ($grupoPagoAgente) {
                                $tnTransferencia->setAgencia($tnTransferencia->getAgente()->getAgencia());
                                //Poniendo los valores con que se crea la factura
                                $porcientoAsig = $grupoPagoAgente->getPorcentaje();
                                $tnTransferencia->setPorcentajeAsignadoAgente($porcientoAsig);
                                $porcentaje = $facturaManager->porcientoOperacionAgenteTransferencia($tnTransferencia->getAgente(), $monedaTransferencia);
                                if ($porcentaje != null) {
                                    $tnTransferencia->setPorcentajeOperaAgente($porcentaje);
                                } else {
                                    $tnTransferencia->setPorcentajeOperaAgente($tnTransferencia->getAgente()->getUsuario()->getPorcentaje());
                                }
                                //Calculo el porciento o utilidad fija
                                $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);

                                $tnTransferencia->setTotalPagarAgente($totalPagar);
                            } else {
                                throw new \Exception("Error grupo de pago");
                            }
                        } catch (\Exception $e) {
                            $form->get('importe')->addError(new FormError("El Agente no tiene configurado las transferencias."));
                            $form->isValid();
                        }
                        if ($tnTransferencia->getAgente()->getAgencia() != null) {//Verifico si el agente es de una agencia y guardo sus datos
                            try {
                                $grupoPagoAgencia = $entityManager->getRepository(NmGrupoPagoTransf::class)->grupoPagoAgencia($tnTransferencia->getAgente()->getAgencia(), $monedaTransferencia);
                                if ($grupoPagoAgencia) {
                                    $porcientoAsig = $grupoPagoAgencia->getPorcentaje();
                                    $tnTransferencia->setPorcentajeAsignado($porcientoAsig);
                                    //Calculando el porciento con que opera
                                    $porcentaje = $facturaManager->porcientoOperacionAgenciaTransferencia($tnTransferencia->getAgente()->getAgencia(), $monedaTransferencia);
                                    if ($porcentaje != null) {
                                        $tnTransferencia->setPorcentajeOpera($porcentaje);
                                    } else {
                                        $tnTransferencia->setPorcentajeOpera($tnTransferencia->getAgente()->getAgencia()->getUsuario() ? $tnTransferencia->getAgente()->getAgencia()->getUsuario()->getPorcentaje() : $porcientoAsig);
                                    }
                                    $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);
                                    $tnTransferencia->setTotalPagar($totalPagar);
                                } else {
                                    throw new \Exception("Error grupo de pago");
                                }
                            } catch (\Exception $e) {
                                $form->get('importe')->addError(new FormError("La Agencia a la que pertecene el agente no tiene configurado las transferencias."));
                                $form->isValid();
                            }
                        } else {
                            $tnTransferencia->setTotalPagar(0);
                            $tnTransferencia->setPorcentajeAsignado(0);
                            $tnTransferencia->setPorcentajeOpera(0);
                        }
                    } elseif ($tnTransferencia->getAgencia() != null) {
                        try {
                            $grupoPagoAgencia = $entityManager->getRepository(NmGrupoPagoTransf::class)->grupoPagoAgencia($tnTransferencia->getAgencia(), $monedaTransferencia);
                            if ($grupoPagoAgencia) {
                                //Poniendo los valores con que se crea la factura
                                $porcientoAsig = $grupoPagoAgencia->getPorcentaje();
                                $tnTransferencia->setPorcentajeAsignado($porcientoAsig);
                                //Calculando el porciento con que opera
                                $porcentaje = $facturaManager->porcientoOperacionAgenciaTransferencia($tnTransferencia->getAgencia(), $monedaTransferencia);
                                if ($porcentaje != null) {
                                    $tnTransferencia->setPorcentajeOpera($porcentaje);
                                } else {
                                    $tnTransferencia->setPorcentajeOpera($tnTransferencia->getAgencia()->getUsuario()->getPorcentaje());
                                }

                                $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);
                                $tnTransferencia->setTotalPagar($totalPagar);
                            } else {
                                throw new \Exception("Error grupo de pago");
                            }
                        } catch (\Exception $e) {
                            $form->get('importe')->addError(new FormError("La Agencia no tiene configurado las transferencias."));
                            $form->isValid();
                        }
                    } else {

                        //Poniendo los valores con que se crea la factura
                        $porcientoAsig = $configurationManager->get(TnConfiguration::PORCENTAJE);
                        $tnTransferencia->setPorcentajeAsignado($porcientoAsig);
                        $tnTransferencia->setPorcentajeOpera($user->getPorcentaje());
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
                        $tnTransferencia->setTotalPagar($totalPagar);
                    }
                }

                if ($form->isValid()) {
                    $tnTransferencia->setCodigo($facturaManager->newCodigoTransferencia());

                    if ($tnTransferencia->getAgencia() != null && $tnTransferencia->getAgencia()->getRetenida()) {//Verificando si hay que marcar la factura como retenida
                        $tnTransferencia->setRetenida(true);
                    }

                    $tnTransferencia->setToken((sha1(uniqid())));
                    $entityManager->persist($tnTransferencia);
                    $entityManager->flush();

                    return $this->redirectToRoute('tn_transferencia_index');
                }
            }

            return $this->render('backend/tn_transferencia/new.html.twig', [
                'tn_transferencium' => $tnTransferencia,
                'form' => $form->createView(),
            ]);
        } else {
            $this->get('session')->getFlashBag()->add('warning', 'En estos momentos se ha desabilitado la opción de registrar nuevas transferencias, intente más tarde.');

            return $this->redirectToRoute('tn_transferencia_index');
        }
    }

    /**
     * @Route("/{id}", name="tn_transferencia_show", methods={"GET"})
     */
    public function show(TnTransferencia $tnTransferencium): Response
    {
        return $this->render('backend/tn_transferencia/show.html.twig', [
            'tn_transferencium' => $tnTransferencium,
        ]);
    }

    /**
     * @Route("/{token}/edit", name="tn_transferencia_edit", methods={"GET","POST"})
     * @ParamConverter("tnTransferencia", options={"mapping": {"token": "token"}})
     */
    public function edit(Request $request, TnTransferencia $tnTransferencia, ConfigurationManager $configurationManager, FacturaManager $facturaManager): Response
    {
        $entityManager = $this->getDoctrine()->getManager();

        $user = $this->getUser();
        if ($user->getPorcentaje() == null || $user->getPorcentaje() == 0) {
            $this->get('session')->getFlashBag()->add('error', 'Debe especificar el porciento a operar para registrar/editar transferencias.');

            return $this->redirectToRoute('tn_transferencia_index');
        }

        $form = $this->createForm(TnTransferenciaType::class, $tnTransferencia);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$user->getAuth()) {
                $tnTransferencia->setTotalCobrar(0.0);
            }

            //Verificando los valores y poniendo los que van
            $user = $this->getUser();
            $checker = $this->get('security.authorization_checker');

            $tnTransferencia->setMonto($tnTransferencia->getImporte());

            //Guardando la moneda y la tasa
            $monedaTransferencia = $tnTransferencia->getMoneda();
            $tasa = $monedaTransferencia->getTasaCambio();

            $importeTasa = round($tnTransferencia->getImporte() / $tasa); //Importe por el que se debe calcular los porcientos y demás.

            if ($checker->isGranted("ROLE_AGENCIA")) {
                $tnTransferencia->setAgencia($user->getAgencia());
                if ($tnTransferencia->getAgente() != null) {
                    try {
                        $grupoPagoAgente = $entityManager->getRepository(NmGrupoPagoTransfAgente::class)->grupoPagoAgente($tnTransferencia->getAgente(), $monedaTransferencia);
                        if ($grupoPagoAgente) {
                            //Poniendo los valores con que se crea la factura
                            $porcientoAsig = $grupoPagoAgente->getPorcentaje();
                            $tnTransferencia->setPorcentajeAsignadoAgente($porcientoAsig);
                            //Buscando el porcentaje configurado
                            $porcentaje = $facturaManager->porcientoOperacionAgenteTransferencia($tnTransferencia->getAgente(), $monedaTransferencia);
                            if ($porcentaje != null) {
                                $tnTransferencia->setPorcentajeOperaAgente($porcentaje);
                            } else {
                                $tnTransferencia->setPorcentajeOperaAgente($tnTransferencia->getAgente()->getUsuario()->getPorcentaje());
                            }

                            $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);

                            $tnTransferencia->setTotalPagarAgente($totalPagar);
                        } else {
                            throw new \Exception("Error grupo de pago");
                        }

                    } catch (\Exception $e) {
                        $form->get('importe')->addError(new FormError("El Agente no tiene configurado las transferencias."));
                        $form->isValid();
                    }
                }
                try {
                    $grupoPagoAgencia = $entityManager->getRepository(NmGrupoPagoTransf::class)->grupoPagoAgencia($user->getAgencia(), $monedaTransferencia);
                    if ($grupoPagoAgencia) {
                        //Poniendo los valores con que se crea la factura
                        $porcientoAsig = $grupoPagoAgencia->getPorcentaje();
                        $tnTransferencia->setPorcentajeAsignado($porcientoAsig);
                        //Buscando el porcentaje configurado
                        $porcentaje = $facturaManager->porcientoOperacionAgenciaTransferencia($user->getAgencia(), $monedaTransferencia);
                        if ($porcentaje != null) {
                            $tnTransferencia->setPorcentajeOpera($porcentaje);
                        } else {
                            $tnTransferencia->setPorcentajeOpera($user->getPorcentaje());
                        }

                        $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);
                        $tnTransferencia->setTotalPagar($totalPagar);

                        $estado = $entityManager->getRepository(NmEstadoTransferencia::class)->findOneBy(['codigo' => NmEstadoTransferencia::ESTADO_PENDIENTE]);
                        $tnTransferencia->setEstado($estado);
                    } else {
                        throw new \Exception("Error grupo de pago");
                    }
                } catch (\Exception $e) {
                    $form->get('importe')->addError(new FormError("La Agencia no tiene configurado las transferencias."));
                    $form->isValid();
                }

            } elseif ($checker->isGranted("ROLE_AGENTE")) {
                try {
                    $grupoPagoAgente = $entityManager->getRepository(NmGrupoPagoTransfAgente::class)->grupoPagoAgente($user->getAgente(), $monedaTransferencia);
                    if ($grupoPagoAgente) {
                        $tnTransferencia->setAgencia($user->getAgente()->getAgencia());
                        $tnTransferencia->setAgente($user->getAgente());
                        //Poniendo los valores con que se crea la factura
                        $porcientoAsig = $grupoPagoAgente->getPorcentaje();
                        $tnTransferencia->setPorcentajeAsignadoAgente($porcientoAsig);
                        //Buscando el porcentaje configurado
                        $porcentaje = $facturaManager->porcientoOperacionAgenteTransferencia($user->getAgente(), $monedaTransferencia);
                        if ($porcentaje != null) {
                            $tnTransferencia->setPorcentajeOperaAgente($porcentaje);
                        } else {
                            $tnTransferencia->setPorcentajeOperaAgente($user->getPorcentaje());
                        }

                        $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);
                        $tnTransferencia->setTotalPagarAgente($totalPagar);

                        $estado = $entityManager->getRepository(NmEstadoTransferencia::class)->findOneBy(['codigo' => NmEstadoTransferencia::ESTADO_PENDIENTE]);
                        $tnTransferencia->setEstado($estado);
                    } else {
                        throw new \Exception("Error grupo de pago");
                    }
                } catch (\Exception $e) {
                    $form->get('importe')->addError(new FormError("El Agente no tiene configurado las transferencias."));
                    $form->isValid();
                }

                if ($user->getAgente()->getAgencia() != null) {//Verifico si el agente es de una agencia y guardo sus datos
                    try {
                        $grupoPagoAgencia = $entityManager->getRepository(NmGrupoPagoTransf::class)->grupoPagoAgencia($user->getAgente()->getAgencia(), $monedaTransferencia);
                        if ($grupoPagoAgencia) {
                            $porcientoAsig = $grupoPagoAgencia->getPorcentaje();
                            $tnTransferencia->setPorcentajeAsignado($porcientoAsig);
                            //Buscando el porcentaje configurado
                            $porcentaje = $facturaManager->porcientoOperacionAgenciaTransferencia($user->getAgente()->getAgencia(), $monedaTransferencia);
                            if ($porcentaje != null) {
                                $tnTransferencia->setPorcentajeOpera($porcentaje);
                            } else {
                                $tnTransferencia->setPorcentajeOpera($user->getAgente()->getAgencia()->getUsuario() ? $user->getAgente()->getAgencia()->getUsuario()->getPorcentaje() : $porcientoAsig);
                            }
                        } else {
                            throw new \Exception("Error grupo de pago");
                        }
                    } catch (\Exception $e) {
                        $form->get('importe')->addError(new FormError("La Agencia a la que pertecene el agente no tiene configurado las transferencias."));
                        $form->isValid();
                    }

                    $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);
                    $tnTransferencia->setTotalPagar($totalPagar);
                } else {
                    $tnTransferencia->setTotalPagar(0);
                    $tnTransferencia->setPorcentajeAsignado(0);
                    $tnTransferencia->setPorcentajeOpera(0);
                }
            } else {
                //Verificando si es para de una agencia o un agente
                if ($tnTransferencia->getAgente() != null) {
                    try {
                        $grupoPagoAgente = $entityManager->getRepository(NmGrupoPagoTransfAgente::class)->grupoPagoAgente($tnTransferencia->getAgente(), $monedaTransferencia);
                        if ($grupoPagoAgente) {
                            $tnTransferencia->setAgencia($tnTransferencia->getAgente()->getAgencia());
                            //Poniendo los valores con que se crea la factura
                            $porcientoAsig = $grupoPagoAgente->getPorcentaje();
                            $tnTransferencia->setPorcentajeAsignadoAgente($porcientoAsig);
                            $porcentaje = $facturaManager->porcientoOperacionAgenteTransferencia($tnTransferencia->getAgente(), $monedaTransferencia);
                            if ($porcentaje != null) {
                                $tnTransferencia->setPorcentajeOperaAgente($porcentaje);
                            } else {
                                $tnTransferencia->setPorcentajeOperaAgente($tnTransferencia->getAgente()->getUsuario()->getPorcentaje());
                            }
                            //Calculo el porciento o utilidad fija
                            $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);

                            $tnTransferencia->setTotalPagarAgente($totalPagar);
                        } else {
                            throw new \Exception("Error grupo de pago");
                        }
                    } catch (\Exception $e) {
                        $form->get('importe')->addError(new FormError("El Agente no tiene configurado las transferencias."));
                        $form->isValid();
                    }
                    if ($tnTransferencia->getAgente()->getAgencia() != null) {//Verifico si el agente es de una agencia y guardo sus datos
                        try {
                            $grupoPagoAgencia = $entityManager->getRepository(NmGrupoPagoTransf::class)->grupoPagoAgencia($tnTransferencia->getAgente()->getAgencia(), $monedaTransferencia);
                            if ($grupoPagoAgencia) {
                                $porcientoAsig = $grupoPagoAgencia->getPorcentaje();
                                $tnTransferencia->setPorcentajeAsignado($porcientoAsig);
                                //Calculando el porciento con que opera
                                $porcentaje = $facturaManager->porcientoOperacionAgenciaTransferencia($tnTransferencia->getAgente()->getAgencia(), $monedaTransferencia);
                                if ($porcentaje != null) {
                                    $tnTransferencia->setPorcentajeOpera($porcentaje);
                                } else {
                                    $tnTransferencia->setPorcentajeOpera($tnTransferencia->getAgente()->getAgencia()->getUsuario() ? $tnTransferencia->getAgente()->getAgencia()->getUsuario()->getPorcentaje() : $porcientoAsig);
                                }
                                $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);
                                $tnTransferencia->setTotalPagar($totalPagar);
                            } else {
                                throw new \Exception("Error grupo de pago");
                            }
                        } catch (\Exception $e) {
                            $form->get('importe')->addError(new FormError("La Agencia a la que pertecene el agente no tiene configurado las transferencias."));
                            $form->isValid();
                        }
                    } else {
                        $tnTransferencia->setTotalPagar(0);
                        $tnTransferencia->setPorcentajeAsignado(0);
                        $tnTransferencia->setPorcentajeOpera(0);
                    }
                } elseif ($tnTransferencia->getAgencia() != null) {
                    try {
                        $grupoPagoAgencia = $entityManager->getRepository(NmGrupoPagoTransf::class)->grupoPagoAgencia($tnTransferencia->getAgencia(), $monedaTransferencia);
                        if ($grupoPagoAgencia) {
                            //Poniendo los valores con que se crea la factura
                            $porcientoAsig = $grupoPagoAgencia->getPorcentaje();
                            $tnTransferencia->setPorcentajeAsignado($porcientoAsig);
                            //Calculando el porciento con que opera
                            $porcentaje = $facturaManager->porcientoOperacionAgenciaTransferencia($tnTransferencia->getAgencia(), $monedaTransferencia);
                            if ($porcentaje != null) {
                                $tnTransferencia->setPorcentajeOpera($porcentaje);
                            } else {
                                $tnTransferencia->setPorcentajeOpera($tnTransferencia->getAgencia()->getUsuario()->getPorcentaje());
                            }

                            $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);
                            $tnTransferencia->setTotalPagar($totalPagar);
                        } else {
                            throw new \Exception("Error grupo de pago");
                        }
                    } catch (\Exception $e) {
                        $form->get('importe')->addError(new FormError("La Agencia no tiene configurado las transferencias."));
                        $form->isValid();
                    }
                } else {

                    //Poniendo los valores con que se crea la factura
                    $porcientoAsig = $configurationManager->get(TnConfiguration::PORCENTAJE);
                    $tnTransferencia->setPorcentajeAsignado($porcientoAsig);
                    $tnTransferencia->setPorcentajeOpera($user->getPorcentaje());
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
                    $tnTransferencia->setTotalPagar($totalPagar);
                }
            }

            if ($form->isValid()) {

                if ($tnTransferencia->getAgencia() != null && $tnTransferencia->getAgencia()->getRetenida() && !$tnTransferencia->getRetenida()) {//Verificando si hay que marcar la factura como retenida
                    $tnTransferencia->setRetenida(true);
                } else {
                    $tnTransferencia->setRetenida(false);
                }

                $entityManager->persist($tnTransferencia);
                $entityManager->flush();

                return $this->redirectToRoute('tn_transferencia_index');
            }
        }

        return $this->render('backend/tn_transferencia/edit.html.twig', [
            'tn_transferencia' => $tnTransferencia,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="tn_transferencia_delete", methods={"DELETE"})
     */
    public function delete(Request $request, TnTransferencia $tnTransferencium): Response
    {
        if ($this->isCsrfTokenValid('delete' . $tnTransferencium->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($tnTransferencium);
            $entityManager->flush();
        }

        return $this->redirectToRoute('tn_transferencia_index');
    }

    /**
     *
     * @Route("/cancel/state", name="admin_transferencia_cancel_ajax")
     * @Method({"GET", "POST"})
     */
    public function cancelEstadoAjax(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        if ($request->isXmlHttpRequest()) {
            $tnTransferencia = $em->getRepository(TnTransferencia::class)->find($request->get('idTransferencia'));
            $estado = $em->getRepository(NmEstadoTransferencia::class)->findOneBy(array('codigo' => $request->get('estado')));

            if ($tnTransferencia->getReporteTransferencia() != null) {//Quitando del reporte de envío.
                $tnTransferencia->setReporteTransferencia(null);
            }

            $tnTransferencia->setEstado($estado);
            $em->persist($tnTransferencia);
            $em->flush();

            $html = '<span class="label label-danger">Cancelada</span>';

            $actions = '<a class="btn btn-warning btn-xs" data-toggle="tooltip"
                                                       title="Descargar transferencia"
                                                       href="#"><i class="fa fa-print"></i></a>';

            return new JsonResponse(array('success' => true, 'html' => $html, 'actions' => $actions));
        }
    }

    /**
     * @Route("/agente/porciento/operacion", name="admin_agente_transferencia_porciento_operacion_ajax")
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

            $nmGrupoPago = $em->getRepository(NmGrupoPagoTransfAgente::class)->grupoPagoAgente($tnAgente, $nmMoneda);
            $porcentajeOperacion = $em->getRepository(TnOperacionAgenteTransf::class)->findOneBy(['agente' => $tnAgente, 'grupoPago' => $nmGrupoPago]);
            if (!is_null($porcentajeOperacion) && !is_null($porcentajeOperacion->getPorcentaje())) {
                return new JsonResponse(array('success' => true, 'valor' => $porcentajeOperacion->getPorcentaje()));
            } else {
                return new JsonResponse(array('success' => false, 'valor' => 'Undefined'));
            }
        }
    }

    /**
     * @Route("/agencia/porciento/operacion", name="admin_agencia_transferencia_porciento_operacion_ajax")
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

            $nmGrupoPago = $em->getRepository(NmGrupoPagoTransf::class)->grupoPagoAgencia($tnAgencia, $nmMoneda);
            $porcentajeOperacion = $em->getRepository(TnOperacionAgenciaTransf::class)->findOneBy(['agencia' => $tnAgencia, 'grupoPago' => $nmGrupoPago]);
            if (!is_null($porcentajeOperacion) && !is_null($porcentajeOperacion->getPorcentaje())) {
                return new JsonResponse(array('success' => true, 'valor' => $porcentajeOperacion->getPorcentaje()));
            } else {
                return new JsonResponse(array('success' => false, 'valor' => 'Undefined'));
            }
        }
    }

    /**
     * @Route("/notas/{token}/edit", name="tn_transferencia_notas_edit")
     * @ParamConverter("tnTransferencia", options={"mapping": {"token": "token"}})
     * @Method({"GET", "POST"})
     */
    public function editarNotasTransferencia(Request $request, TnTransferencia $tnTransferencia)
    {
        $em = $this->getDoctrine()->getManager();
        if ($request->isMethod("POST")) {
            $notas = $request->get('notas');
            $tnTransferencia->setNotas($notas);
            $em->persist($tnTransferencia);
            $em->flush();

            return $this->render('backend/tn_transferencia/notas_edit.html.twig', array(
                'transferencia' => $tnTransferencia,
            ));
        }

        return $this->render('backend/tn_transferencia/notas.html.twig', array(
            'tnTransferencia' => $tnTransferencia,
        ));
    }

    /**
     * @Route("/logs/{token}/transferencia", name="admin_transferencia_logs_show", methods={"GET"})
     * @ParamConverter("tnTransferencia", options={"mapping": {"token": "token"}})
     */
    public function adminLogsTransferencia(TnTransferencia $tnTransferencia): Response
    {
        $repository = $this->getDoctrine()->getManager()->getRepository(LogEntry::class);
        $logs = $repository->getLogEntries($tnTransferencia);

        return $this->render('backend/tn_transferencia/logs.html.twig', [
            'tnTransferencia' => $tnTransferencia,
            'logs' => $logs
        ]);
    }

    /**
     * @Route("change/{id}/fecha/contable", name="admin_transferencia_change_fecha_contable")
     * @Method({"GET", "POST"})
     */
    public function changeFechaContableTransferencia(Request $request, TnTransferencia $tnTransferencia)
    {
        $em = $this->getDoctrine()->getManager();

        if ($request->isMethod("POST")) {

            $fecha = new \DateTime($request->get('fechaContable'));
            $tnTransferencia->setFechaContable($fecha);
            $em->persist($tnTransferencia);
            $em->flush();

            return $this->redirectToRoute('tn_transferencia_index');
        }

        return $this->render('backend/tn_transferencia/fecha_contable.html.twig', array(
            'tnTransferencia' => $tnTransferencia,
        ));
    }

    /**
     * @Route("/fecha/contable/ajax", name="admin_transferencia_change_fecha_contable_ajax")
     * @Method({"GET", "POST"})
     */
    public function changeFechaContableFacturaAjax(Request $request, TnTransferenciaRepository $transferenciaRepository)
    {
        $em = $this->getDoctrine()->getManager();

        if ($request->isXmlHttpRequest()) {
            $tnTransferencia = $transferenciaRepository->find($request->get('transferencia'));
            $fecha = new \DateTime($request->get('fechaContable'));
            $tnTransferencia->setFechaContable($fecha);
            $em->persist($tnTransferencia);
            $em->flush();

            //Para cambiar el div de la fecha contable....
            $btn = $fecha->format("d/m/Y") . ' <a href="' . $this->generateUrl('admin_transferencia_change_fecha_contable', ['id' => $tnTransferencia->getId()]) . '"
                                                       data-target="#modal-form"
                                                       class="btn btn-xs btn-primary btn-circle btn-outline show-option"
                                                       title="Modificar fecha contable"><i
                                                                class="fa fa-calendar"></i></a>';

            return new JsonResponse(array('success' => true, 'msg' => 'La fecha contable de la transferencia fue cambiada con éxito.', 'div_content' => $btn));
        }
    }

    /**
     * @Route("/export_transferencia/{token}/{action}/pdf", name="admin_tranferencia_export_pdf", methods={"GET"}, requirements={"action"="download|print"})
     * @ParamConverter("factura", options={"mapping": {"token": "token"}})
     */
    public function exportNotaCreditoPdf(Request $request, TcPdfManager $tcPdfManager, TnTransferencia $tnTransferencia, $action): Response
    {
        $html = $this->renderView('backend/tn_transferencia/factura_transferencia_pdf.html.twig', array(
            'transferencia' => $tnTransferencia
        ));

        if ($action == "download") {
            $tcPdfManager->getDocument('QM_TRANSFERENCIA_' . $tnTransferencia->getCodigo(), $html, 'D');

        } elseif ($action == "print") {
            $tcPdfManager->getDocument('QM_TRANSFERENCIA_' . $tnTransferencia->getCodigo(), $html, 'I');
        }

        return $this->redirectToRoute('tn_transferencia_index');
    }

    /**
     * @Route("/destinatarios/usuario", name="tn_transferencia_destinatarios_usuario")
     * @Method({"GET", "POST"})
     */
    public function destinatariosUsuario(Request $request, TnTransferenciaRepository $tnTransferenciaRepository)
    {
        if ($request->isXmlHttpRequest()) {
            $user = $request->get('user');
            $type = $request->get('type');
            $titular = $request->get('titular');
            if (isset($user) && $user != "") {
                if ($type == "Agente") {
                    return $this->render('backend/tn_transferencia/destinatarios_user.html.twig', array(
                        'destinatarios' => $tnTransferenciaRepository->findDestinatarioAgente($user, $titular)
                    ));
                } elseif ($type == "Agencia") {
                    return $this->render('backend/tn_transferencia/destinatarios_user.html.twig', array(
                        'destinatarios' => $tnTransferenciaRepository->findDestinatarioAgencia($user, $titular)
                    ));
                }
            } else {
                return $this->render('backend/tn_transferencia/destinatarios_user.html.twig', array());
            }
        }
    }

    /**
     * @Route("/repartidores/listado", name="tn_transferencia_repartidores_lista", methods={"GET"})
     */
    public function listadoRepartidores(TnRepartidorRepository $repartidorRepository): Response
    {
        return $this->render('backend/tn_transferencia/repartidor/index.html.twig', [
            'tn_repartidores' => $repartidorRepository->findAll(),
        ]);
    }

    /**
     * @Route("/repartidores/new", name="tn_transferencia_repartidores_new", methods={"GET","POST"})
     */
    public function newRepartidor(Request $request): Response
    {
        $tnRepartidor = new TnRepartidor();
        $form = $this->createForm(TnRepartidorType::class, $tnRepartidor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $tnRepartidor->setToken((sha1(uniqid())));

            $entityManager->persist($tnRepartidor);
            $entityManager->flush();

            return $this->redirectToRoute('tn_transferencia_repartidores_lista');
        }

        return $this->render('backend/tn_transferencia/repartidor/new.html.twig', [
            'repartidor' => $tnRepartidor,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/repartidores/{token}/edit", name="tn_transferencia_repartidores_edit", methods={"GET","POST"})
     * @ParamConverter("tnRepartidor", options={"mapping": {"token": "token"}})
     */
    public function editRepartidor(Request $request, TnRepartidor $tnRepartidor): Response
    {
        $form = $this->createForm(TnRepartidorType::class, $tnRepartidor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('tn_transferencia_repartidores_lista');
        }

        return $this->render('backend/tn_transferencia/repartidor/edit.html.twig', [
            'repartidor' => $tnRepartidor,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/pendientes/repartir", name="tn_transferencia_repartidores_pendientes_repartir")
     */
    public function pendientesRepartir(Request $request, SessionInterface $sessionBag, TnTransferenciaRepository $tnTransferenciaRepository, NmEstadoTransferenciaRepository $estadoTransferenciaRepository): Response
    {
        ini_set('memory_limit', -1);

        $entityManager = $this->getDoctrine()->getManager();

//        $formSearch = $this->createForm(AdvancedSerachAsignarType::class);
//
//        $formSearch->handleRequest($request);
//
//        if ($formSearch->isSubmitted() && $formSearch->isValid()) {
//
//            $sessionBag->remove('transferencias/asignar_search');
//
//            $data = $formSearch->getData();
//
//            $transferencias = [];
//
//            if ($data['codigo'] != null || $data['tarejta'] != null && $data['titular'] != null || $data['fechaInicio'] != null || $data['fechaFin'] != null || $data['nota'] != null || count($data['agencia']) > 0 || count($data['agente']) > 0) {
//                $query = $tnTransferenciaRepository->createQueryBuilder('transf')
//                    ->join('transf.estado', 'estado')
//                    ->where('estado.codigo IN (:estados)')
//                    ->andWhere('transf.reporteTransferencia IS NULL')
//                    ->andWhere('transf.retenida IS NULL or transf.retenida = false')
//                    ->setParameter('estados', [NmEstadoTransferencia::ESTADO_PENDIENTE])
//                    ->orderBy('transf.created', "ASC");
//
//
//                if ($formSearch->get('codigo')->getData() != null) {
//                    $query->andWhere('transf.codigo like :cod or transf.referencia like :cod')
//                        ->setParameter('cod', '%' . $formSearch->get('codigo')->getData() . '%');
//                }
//
//                if ($formSearch->get('tarejta')->getData() != null) {
//                    $query->andWhere('transf.numeroTarjeta like :ntarj')
//                        ->setParameter('ntarj', '%' . $formSearch->get('tarejta')->getData() . '%');
//                }
//
//                if ($formSearch->get('titular')->getData() != null) {
//                    $query->andWhere('transf.titularTarjeta like :ttular')
//                        ->setParameter('ttular', '%' . $formSearch->get('titular')->getData() . '%');
//                }
//
//                if ($formSearch->get('fechaInicio')->getData() != null && $formSearch->get('fechaFin')->getData() == null) {
//                    $fInicio = new \DateTime(date($formSearch->get('fechaInicio')->getData()->format('Y-m-d') . " 00:00:00"));
//                    $query->andWhere('transf.created >= :inicio')
//                        ->setParameter('inicio', $fInicio);
//                }
//
//                if ($formSearch->get('fechaFin')->getData() != null && $formSearch->get('fechaInicio')->getData() == NULL) {
//                    $fFin = new \DateTime(date($formSearch->get('fechaFin')->getData()->format('Y-m-d') . " 23:59:59"));
//                    $query->andWhere('transf.created <= :fin')
//                        ->setParameter('fin', $fFin);
//                }
//
//                if ($formSearch->get('fechaInicio')->getData() != null && $formSearch->get('fechaFin')->getData() != null) {
//
//                    $fInicio = new \DateTime(date($formSearch->get('fechaInicio')->getData()->format('Y-m-d') . " 00:00:00"));
//                    $fFin = new \DateTime(date($formSearch->get('fechaFin')->getData()->format('Y-m-d') . " 23:59:59"));
//                    $query->andWhere('transf.created >= :inicio and transf.created <= :fin')
//                        ->setParameter('fin', $fFin)
//                        ->setParameter('inicio', $fInicio);
//                }
//
//                if (count($formSearch->get('agencia')->getData()) > 0) {
//                    $query->andWhere('transf.agencia IN (:ags)')
//                        ->setParameter('ags', $formSearch->get('agencia')->getData());
//                }
//
//                if (count($formSearch->get('agente')->getData()) > 0) {
//                    $query->andWhere('transf.agente IN (:ags)')
//                        ->setParameter('ags', $formSearch->get('agente')->getData());
//                }
//
//                if ($formSearch->get('nota')->getData() != null) {
//                    $query->andWhere('transf.notas like :nota')
//                        ->setParameter('nota', '%' . $formSearch->get('nota')->getData() . '%');
//                }
//
//                $transferencias = $query->getQuery()->getResult();
//
//                $dataSeach = [];
//                foreach ($transferencias as $transf) {
//                    $dataSeach[] = $transf->getId();
//                }
//
//                $sessionBag->set('transferencias/asignar_search', [
//                    'transferencias' => $dataSeach,
//                ]);
//            }
//        } else {
        $total = $tnTransferenciaRepository->createQueryBuilder('tr')
            ->select('COUNT(tr.id)')
            ->join('tr.estado', 'estado')
            ->where('estado.codigo IN (:estados)')
            ->andWhere('tr.reporteTransferencia IS NULL')
            ->andWhere('tr.retenida IS NULL or tr.retenida = false')
            ->setParameter('estados', [NmEstadoTransferencia::ESTADO_PENDIENTE])
            ->orderBy('tr.created', "ASC")->getQuery()->getSingleScalarResult();

        $transferencias = $tnTransferenciaRepository->createQueryBuilder('tr')
            ->join('tr.estado', 'estado')
            ->where('estado.codigo IN (:estados)')
            ->andWhere('tr.reporteTransferencia IS NULL')
            ->andWhere('tr.retenida IS NULL or tr.retenida = false')
            ->setParameter('estados', [NmEstadoTransferencia::ESTADO_PENDIENTE])
            ->orderBy('tr.created', "ASC")->getQuery()->getResult();
//        }

        $asginarTransferenciaModel = new AsignarTransferenciaModel();
        $asginarTransferenciaModel->setTransferencias($transferencias);

        $form = $this->createForm(PendientesAsignarType::class, $asginarTransferenciaModel, [
            'action' => $this->generateUrl('tn_transferencia_repartidores_pendientes_repartir')
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $estadoAsig = $estadoTransferenciaRepository->findOneBy(array('codigo' => NmEstadoTransferencia::ESTADO_ASINGADA));

            foreach ($form->getData()->getTransferencias() as $transferencia) {
                if ($transferencia instanceof TnTransferencia && $transferencia->getRepartidor() != null) {
                    $transferencia->setEstado($estadoAsig);
                    $entityManager->persist($transferencia);
                }
            }

            $entityManager->flush();

            $sessionBag->remove('transferencias/asignar_search');

            $this->get('session')->getFlashBag()->add('success', 'Repartidores asignados correctamente');
            return $this->redirectToRoute('tn_transferencia_repartidores_pendientes_repartir');
        }

        return $this->render('backend/tn_transferencia/repartidor/asignar_transferencia.html.twig', [
            'form' => $form->createView(),
//            'formSearch' => $formSearch->createView(),
            'pagina' => count($transferencias),
            'total' => isset($total) ? $total : count($transferencias)
        ]);
    }

    /**
     * @Route("/reporte/repartidor", name="tn_transferencia_repartidores_reporte")
     */
    public function reporteRepartidorAction(Request $request, SessionInterface $sessionBag, TnTransferenciaRepository $tnTransferenciaRepository)
    {
        $form = $this->createForm(ReporteRepartidorType::class);
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

            if (count($data['repartidor']) > 0) {//Para filtrar por las monedas.
                $temp = [];
                $tempShow = [];
                foreach ($data['repartidor'] as $dist) {
                    $temp [] = $dist->getId();
                    $tempShow[] = $dist;
                }
                $params['repartidores'] = $temp;
                $paramsShow['repartidores'] = implode(', ', $tempShow);

            } else {
                $paramsShow['repartidores'] = 'Todos';
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
                $paramsShow['monedas'] = 'Todos';
            }
            if (count($data['estado']) > 0) {
                $tempShow = [];
                foreach ($data['estado'] as $estado) {
                    if ($estado == '02') {
                        $tempShow[] = "Asignada";
                    } elseif ($estado == '03') {
                        $tempShow[] = "Enviada";
                    } elseif ($estado == '04') {
                        $tempShow[] = "Entregada";
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
            if (isset($params['estados']) && count($params['estados']) == 1 && in_array(NmEstadoTransferencia::ESTADO_CANCELADA, $params['estados'])) {
                $transferencias = $tnTransferenciaRepository->findTransfParamsRepartidorCanceladas($params);
            } else {//Si no, normal la consulta.
                $transferencias = $tnTransferenciaRepository->findTransfAdminParamsRepartidor($params);
            }

            foreach ($transferencias as $transferencia) {
                if ($transferencia instanceof TnTransferencia) {
                    if ($transferencia->getRepartidor() != null) {
                        if (array_key_exists($transferencia->getRepartidor()->getNombre(), $result)) {
                            $result[$transferencia->getRepartidor()->getNombre()][] = $transferencia;
                        } else {
                            $result[$transferencia->getRepartidor()->getNombre()] = [$transferencia];
                        }
                    } else {//Para las remesas que aún no tengan repartidor
                        if (array_key_exists('Sin definir', $result)) {
                            $result['Sin definir'][] = $transferencia;
                        } else {
                            $result['Sin definir'] = [$transferencia];
                        }
                    }
                }
            }

            $sessionBag->set('reportes/admin_reporte_repartidores', [
                'params' => $paramsShow,
                'search' => $params
            ]);
        }

        if ($request->getMethod() == 'GET') {
            $sessionBag->remove('reportes/admin_reporte_repartidores');
        }

        return $this->render('backend/tn_transferencia/reportes/tranfs_repartidores.html.twig', [
            'form' => $form->createView(),
            'transferencias' => $result
        ]);
    }

    /**
     * @Route("/print/reporte/repartidor", name="tn_transferencia_repartidores_reporte_print")
     */
    public function printReporteRepartidorAction(Request $request, SessionInterface $sessionBag, TcPdfManager $tcPdfManager, TnTransferenciaRepository $tnTransferenciaRepository)
    {
        $user = $this->getUser();
        if ($sessionBag->has('reportes/admin_reporte_repartidores')) {
            $reporte = $sessionBag->get('reportes/admin_reporte_repartidores');
            $result = [];
            //Si envía el estado de Cancelado solo en el reporte, tengo que buscar las canceladas solamente con los demás datos.
            if (isset($params['estados']) && count($reporte['search']['estados']) == 1 && in_array(NmEstadoTransferencia::ESTADO_CANCELADA, $reporte['search']['estados'])) {
                $transferencias = $tnTransferenciaRepository->findTransfParamsRepartidorCanceladas($reporte['search']);
            } else {//Si no, normal la consulta.
                $transferencias = $tnTransferenciaRepository->findTransfAdminParamsRepartidor($reporte['search']);
            }

            foreach ($transferencias as $transferencia) {
                if ($transferencia instanceof TnTransferencia) {
                    if (array_key_exists($transferencia->getRepartidor()->getNombre(), $result)) {
                        $result[$transferencia->getRepartidor()->getNombre()][] = $transferencia;

                    } else {
                        $result[$transferencia->getRepartidor()->getNombre()] = [$transferencia];
                    }
                }
            }
            //Exportando remesas a PDF
            $html = $this->renderView('backend/tn_transferencia/reportes/tranfs_repartidores_pdf.html.twig', array(
                'transferencias' => $result,
                'params' => $reporte['params']
            ));

            $date = new \DateTime('now');
            $tcPdfManager->getDocumentReport('QM_Transferencias_' . $user->getUsername() . ($date->format('d-m-Y')), $html, 'I', ['title' => 'REPORTE DE REPARTIDOR - ' . $this->getUser()->getUsername()]);
        } else {
            return $this->redirectToRoute('tn_transferencia_repartidores_reporte');
        }

    }

    /**
     * @Route("/remove/retenida", name="admin_transferencia_remove_retenida")
     * @Method({"GET", "POST"})
     */
    public function removeRetenida(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        if ($request->isXmlHttpRequest()) {
            $data = $request->request->all();
            $data = json_decode($data['datos'], true);

            $tnTransferencia = $em->getRepository(TnTransferencia::class)->find($data['factura']);
            if (!is_null($tnTransferencia)) {
                $tnTransferencia->setRetenida(false);
                $em->persist($tnTransferencia);
                $em->flush();

                return new JsonResponse(array('success' => true));
            } else {
                return new JsonResponse(array('success' => false));
            }
        }
    }
}
