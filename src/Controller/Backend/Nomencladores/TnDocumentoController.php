<?php

namespace App\Controller\Backend\Nomencladores;

use App\Entity\NmEstado;
use App\Entity\NmGrupoPago;
use App\Entity\NmMoneda;
use App\Entity\TnAgencia;
use App\Entity\TnConfiguration;
use App\Entity\TnDestinatario;
use App\Entity\TnDocumento;
use App\Entity\TnEmisor;
use App\Entity\TnFactura;
use App\Entity\TnRemesa;
use App\Entity\TnSaldoAgencia;
use App\Form\TnDocumentoType;
use App\Manager\ConfigurationManager;
use App\Manager\FacturaManager;
use App\Manager\PhpExcelManager;
use App\Repository\NmGrupoPagoRepository;
use App\Repository\NmMunicipioRepository;
use App\Repository\TnAgenciaRepository;
use App\Repository\TnDestinatarioRepository;
use App\Repository\TnDocumentoRepository;
use App\Repository\TnEmisorRepository;
use App\Repository\TnSaldoAgenciaRepository;
use App\Util\DirectoryNamerUtil;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @Route("/super/nom/tn/documento")
 */
class TnDocumentoController extends AbstractController
{
    /**
     * @Route("/", name="tn_documento_index", methods={"GET","POST"})
     */
    public function index(Request $request, PaginatorInterface $paginator, ConfigurationManager $configurationManager, TnDocumentoRepository $tnDocumentoRepository, DirectoryNamerUtil $directoryNamerUtil, AuthorizationCheckerInterface $authorizationChecker): Response
    {
        ini_set('memory_limit', -1);

        $entityManager = $this->getDoctrine()->getManager();
        $tnDocumento = new TnDocumento();
        $form = $this->createForm(TnDocumentoType::class, $tnDocumento);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $file */
            $fileDocument = $form->get('url')->getData();
            if ($fileDocument) {
                // Move the file to the directory where brochures are stored
                $newFilename = $directoryNamerUtil->getNamer("file_" . $tnDocumento->getAgencia()->getNombre() . "_" . time() . "_", $fileDocument);
                try {
                    $tnDocumento->setUrl($newFilename);
                    $fileDocument->move(
                        $directoryNamerUtil->getDocumentDirPath($tnDocumento),
                        $newFilename
                    );

                    $tnDocumento->setEstado(TnDocumento::ESTADO_PENDIENTE);
                    $entityManager->persist($tnDocumento);
                    $entityManager->flush();

                    return $this->redirectToRoute('tn_documento_index');

                } catch (FileException $e) {
                    $form->get('url')->addError(new FormError("Error en el fichero: " . $e->getMessage()));
                    $form->isValid();
                }
            } else {
                $form->get('url')->addError(new FormError("Error en el fichero: Requerido"));
                $form->isValid();
            }
        }

        //Ficheros
        if ($authorizationChecker->isGranted("ROLE_AGENCIA")) {
            $fin = new \DateTime();
            $init = clone $fin;
            $init->modify("-1week");

            $query = $tnDocumentoRepository->createQueryBuilder('f')
                ->where('f.agencia = :agc')
                ->andWhere('f.created >= :ini and f.created <= :fin')
                ->setParameter('ini', $init)
                ->setParameter('fin', $fin)
                ->setParameter('agc', $this->getUser()->getAgencia())
                ->orderBy('f.created', "DESC")
                ->getQuery();
        } else {
            $fin = new \DateTime();
            $init = clone $fin;
            $init->modify("-1week");

            $query = $tnDocumentoRepository->createQueryBuilder('f')
                ->where('f.created >= :ini and f.created <= :fin')
                ->setParameter('ini', $init)
                ->setParameter('fin', $fin)
                ->orderBy('f.created', "DESC")
                ->getQuery();
        }

        $request = $this->get('request_stack')->getCurrentRequest();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            50
        );

        return $this->render('backend/tn_documento/index.html.twig', [
            'tn_documento' => $tnDocumento,
            'form' => $form->createView(),
            'tn_documentos' => $pagination,
            'state' => $configurationManager->get(TnConfiguration::STATUS_NEW_FACTURA)
        ]);
    }

    /**
     * @Route("/procesar/{id}/destinatarios", name="tn_documento_procesar_fichero_destinatarios", methods={"GET"})
     */
    public function procesarFichero(Request $request, TnDocumento $tnDocumento, TnDestinatarioRepository $destinatarioRepository, DirectoryNamerUtil $directoryNamerUtil, PhpExcelManager $excelManager, NmMunicipioRepository $municipioRepository): Response
    {
        try {
            ini_set('memory_limit', '-1');

            $rows = $directoryNamerUtil->parseCsv($tnDocumento);
            $cols = ['FirstName' => 0, 'LastName' => 1, 'MaidenName' => 2, 'Adrs1' => 5, 'Adrs2' => 6, 'Adrs3' => 7, 'Adrs4' => 8, 'Municipio' => 9, 'Phone' => 10];
            //Vamos a procesar el resultado de parsear el documento csv de los destinatarios
            $municipiosArray = $municipioRepository->findAll();

            $resultProcess = [];
            $arrayRowsFails = [];
            $i = 2;
            foreach ($rows as $row) {
                $erroes = $excelManager->validateRowDestinatario($cols, $row);
                if (count($erroes) == 0) {
                    $direccion = $row[$cols['Adrs1']] . " " . $row[$cols['Adrs2']];
                    if ($row[$cols['Adrs3']] != "") {
                        $direccion = $direccion . " " . $row[$cols['Adrs3']];
                    }
                    if ($row[$cols['Adrs4']] != "") {
                        $direccion = $direccion . " " . $row[$cols['Adrs4']];
                    }

                    $municipio = null;
                    //Buscando los muncipios en la direcccion
                    foreach ($municipiosArray as $item) {
                        $dirTemp = $directoryNamerUtil->eliminar_tildes(strtolower($direccion));
                        $mumTemp = $directoryNamerUtil->eliminar_tildes(strtolower($item->getName()));
                        if (strpos($dirTemp, $mumTemp) !== false) {
                            $municipio = $item;
                            break;
                        }
                    }

                    if ($municipio != null) {
                        $temp = [
                            'nombre' => $row[$cols['FirstName']],
                            'apellidos' => ($row[$cols['LastName']] != "" ? (" " . $row[$cols['LastName']]) : "") . "" . ($row[$cols['MaidenName']] != "" ? (" " . $row[$cols['MaidenName']]) : ""),
                            'phone' => $row[$cols['Phone']],
                            'direccion' => $direccion,
                            'municipio' => $municipio
                        ];
                        $resultProcess[] = $temp;
                    } else {
                        $arrayRowsFails[] = [$i, 'municipio'];
                    }
                } else {
                    $arrayRowsFails[] = [$i, $erroes];
                }
                $i++;
            }
        } catch (\Exception $ex) {
            $this->get('session')->getFlashBag()->add('error', 'El fichero selecciona contiene errores, no pudo procesarse, revise caracteres extraños o columnas faltantes.');
            return $this->redirectToRoute('tn_documento_index');
        }

        $em = $this->getDoctrine()->getManager();
        $em->getConnection()->beginTransaction();
        try {
            $registerCount = 0;
            foreach ($resultProcess as $data) {
                $tnDestinatario = $destinatarioRepository->findOneBy(['phone' => $data['phone'], 'usuario' => $tnDocumento->getAgencia()->getUsuario()]);
                if (is_null($tnDestinatario)) {
                    $tnDestinatario = new TnDestinatario();
                    $tnDestinatario->setNombre($data['nombre']);
                    $tnDestinatario->setApellidos($data['apellidos']);
                    $tnDestinatario->setPhone($data['phone']);
                    $tnDestinatario->setDireccion($data['direccion']);
                    $tnDestinatario->setEnabled(true);
                    $tnDestinatario->setCountry("CU");
                    $tnDestinatario->setMunicipio($data['municipio']);
                    $tnDestinatario->setProvincia($data['municipio']->getProvincia());
                    $tnDestinatario->setEmisor($tnDocumento->getEmisor());
                    $tnDestinatario->setUsuario($tnDocumento->getAgencia() ? $tnDocumento->getAgencia()->getUsuario() : null);
                    $tnDestinatario->setToken((sha1(uniqid())));

                    $em->persist($tnDestinatario);
                    $registerCount++;
                }
            }
            //Actualizando el documento
            $tnDocumento->setEstado(TnDocumento::ESTADO_REGISTRADO);
            $tnDocumento->setValidation($arrayRowsFails);
            $tnDocumento->setTotalProcesado(count($rows));
            $tnDocumento->setTotalValido($registerCount);
            $tnDocumento->setTotalNoValido(count($arrayRowsFails));
            $em->persist($tnDocumento);

            $em->flush();
            $em->getConnection()->commit();
            $this->get('session')->getFlashBag()->add('info', 'El fichero ha sido procesado correctamente, un total de ' . $registerCount . " destinatarios registrados.");
        } catch (\Exception $e) {
            // Rollback the failed transaction attempt
            $em->getConnection()->rollback();
            $this->get('session')->getFlashBag()->add('error', 'El fichero selecciona contiene errores, no pudo procesarse, revise caracteres extraños o columnas faltantes.');
        }

        return $this->redirectToRoute('tn_documento_index');
    }

    /**
     * @Route("/procesar/{id}/facturas", name="tn_documento_procesar_fichero_facturas", methods={"GET"})
     */
    public function procesarFicheroFacturas(Request $request, ConfigurationManager $configurationManager, FacturaManager $facturaManager, TnDocumento $tnDocumento, TnDestinatarioRepository $destinatarioRepository, DirectoryNamerUtil $directoryNamerUtil, PhpExcelManager $excelManager, NmMunicipioRepository $municipioRepository, TnAgenciaRepository $agenciaRepository, TnEmisorRepository $emisorRepository, TnSaldoAgenciaRepository $tnSaldoAgenciaRepository, NmGrupoPagoRepository $grupoPagoRepository): Response
    {
        try {
            ini_set('memory_limit', '-1');
            //Parseo el csv que me devuelve todos los datos importados
            if ($tnDocumento->getTipo() == TnDocumento::TIPO_FACTURA_TE) {
                $rows = $directoryNamerUtil->parseCsvTransExport($tnDocumento);
                $cols = ['FirstName' => 0, 'LastName' => 1, 'MaidenName' => 2, 'Address' => 3, 'CodigoMunicipio' => 4, 'Phone' => 5, 'Monto' => 6, 'Nota' => 7, 'Moneda' => 8];
            } elseif ($tnDocumento->getTipo() == TnDocumento::TIPO_FACTURA_CA) {
                $rows = $directoryNamerUtil->parseCsvCanada($tnDocumento);
                $cols = ['Nota' => 0, 'FirstName' => 4, 'LastName' => 5, 'MaidenName' => 6, 'Address_1' => 7, 'Address_2' => 8, 'CodigoMunicipio' => 22, 'Phone' => 12, 'Monto' => 16, 'Moneda' => 18];
            } elseif ($tnDocumento->getTipo() == TnDocumento::TIPO_FACTURA_TM) {
                $rows = $directoryNamerUtil->parseCsvTramiPro($tnDocumento);
                $cols = ['Agency' => 0, 'FirstName' => 6, 'LastName' => 7, 'MaidenName' => 8, 'Address_1' => 9, 'Address_2' => 10, 'CodigoMunicipio' => 14, 'Phone' => 16, 'Phone_Alt' => 17, 'Moneda' => 18, 'Monto' => 19];
            } elseif ($tnDocumento->getTipo() == TnDocumento::TIPO_FACTURA_CMX) {//CubaMax nueva.
                $rows = $directoryNamerUtil->parseCsvCubaMax($tnDocumento);
                $cols = ['Nota' => 0, 'FirstName' => 1, 'LastName' => 2, 'MaidenName' => 3, 'Address' => 4, 'Municipio' => 6, 'Phone' => 7, 'Monto' => 8, 'Moneda' => 9];
            } elseif ($tnDocumento->getTipo() == TnDocumento::TIPO_FACTURA_VCB) {//VaCuba nueva.
                $rows = $directoryNamerUtil->parseCsvCubaMax($tnDocumento);
                $cols = ['Referencia' => 0, 'FirstName' => 1, 'LastName' => 2, 'MaidenName' => 3, 'Address' => 4, 'Municipio' => 6, 'Phone' => 7, 'Monto' => 8, 'Moneda' => 9, 'Nota' => 10];
            } else {
                $rows = $directoryNamerUtil->parseCsvTramiPro($tnDocumento);
                $cols = ['FirstName' => 6, 'LastName' => 7, 'MaidenName' => 8, 'Address_1' => 9, 'Address_2' => 10, 'CodigoMunicipio' => 14, 'Phone' => 16, 'Phone_Alt' => 17, 'Moneda' => 18, 'Monto' => 19];
            }

            //Vamos a procesar el resultado de parsear el documento csv de las facturas...
            $resultProcess = [];
            $resultProcessPos = [];
            $arrayRowsFails = [];
            $i = 1;
            foreach ($rows as $row) {
                //Verificando errores
                if ($tnDocumento->getTipo() == TnDocumento::TIPO_FACTURA_TM) {
                    $erroes = $excelManager->validateRowMultplie($tnDocumento->getTipo(), $cols, $row);
                } else {
                    $erroes = $excelManager->validateRowFactura($tnDocumento->getTipo(), $cols, $row);
                }
                if (count($erroes) == 0) {
                    if ($tnDocumento->getTipo() == TnDocumento::TIPO_FACTURA_CMX) {
                        //Busco el municio por la referencia que ellos me dan.
                        $municipio = $municipioRepository->findOneBy(['referencia' => $row[$cols['Municipio']]]);
                    } else {
                        if ($tnDocumento->getTipo() == TnDocumento::TIPO_FACTURA_VCB) {
                            $codigoEnviado = trim($row[$cols['Municipio']]);
                        } else {
                            $codigoEnviado = trim($row[$cols['CodigoMunicipio']]);
                        }
                        if (strlen($codigoEnviado) == 7) {
                            $codigo = substr($codigoEnviado, 1, strlen($codigoEnviado) - 1);
                        } else {
                            $codigo = strlen($codigoEnviado) == 5 ? "0" . $codigoEnviado : $codigoEnviado;
                        }
                        $municipio = $municipioRepository->findOneBy(['codigo' => $codigo]);
                    }

                    if ($municipio != null) {
                        if ($tnDocumento->getTipo() == TnDocumento::TIPO_FACTURA_TE) {
                            $temp = [
                                'nombre' => $directoryNamerUtil->eliminar_extrannos($row[$cols['FirstName']]),
                                'apellidos' => $directoryNamerUtil->eliminar_extrannos(($row[$cols['LastName']] != "" ? (" " . $row[$cols['LastName']]) : "") . "" . ($row[$cols['MaidenName']] != "" ? (" " . $row[$cols['MaidenName']]) : "")),
                                'phone' => $row[$cols['Phone']],
                                'phone_alt' => null,
                                'direccion' => $directoryNamerUtil->eliminar_extrannos($row[$cols['Address']]),
                                'municipio' => $municipio,
                                'monto' => $row[$cols['Monto']],
                                'Nota' => $row[$cols['Nota']],
                                'Moneda' => $row[$cols['Moneda']]
                            ];
                        } elseif ($tnDocumento->getTipo() == TnDocumento::TIPO_FACTURA_CMX) { // Cubamax
                            $temp = [
                                'nombre' => $directoryNamerUtil->eliminar_extrannos($row[$cols['FirstName']]),
                                'apellidos' => $directoryNamerUtil->eliminar_extrannos(($row[$cols['LastName']] != "" ? (" " . $row[$cols['LastName']]) : "") . "" . ($row[$cols['MaidenName']] != "" ? (" " . $row[$cols['MaidenName']]) : "")),
                                'phone' => $row[$cols['Phone']],
                                'phone_alt' => null,
                                'direccion' => $directoryNamerUtil->eliminar_extrannos($row[$cols['Address']]),
                                'municipio' => $municipio,
                                'monto' => $row[$cols['Monto']],
                                'Nota' => $row[$cols['Nota']],
                                'Moneda' => $row[$cols['Moneda']]
                            ];
                        } elseif ($tnDocumento->getTipo() == TnDocumento::TIPO_FACTURA_VCB) { //VaCuba
                            $temp = [
                                'Referencia' => $row[$cols['Referencia']],
                                'nombre' => $directoryNamerUtil->eliminar_extrannos($row[$cols['FirstName']]),
                                'apellidos' => $directoryNamerUtil->eliminar_extrannos(($row[$cols['LastName']] != "" ? (" " . $row[$cols['LastName']]) : "") . "" . ($row[$cols['MaidenName']] != "" ? (" " . $row[$cols['MaidenName']]) : "")),
                                'phone' => $row[$cols['Phone']],
                                'phone_alt' => null,
                                'direccion' => $directoryNamerUtil->eliminar_extrannos($row[$cols['Address']]),
                                'municipio' => $municipio,
                                'monto' => $row[$cols['Monto']],
                                'Moneda' => $row[$cols['Moneda']],
                                'Nota' => $row[$cols['Nota']]
                            ];
                        } elseif ($tnDocumento->getTipo() == TnDocumento::TIPO_FACTURA_CA) {
                            if(strpos($row[$cols['Phone']], 't:+') === false){
                                $phone = $row[$cols['Phone']];
                            }else{
                                $phone = substr($row[$cols['Phone']], 3, strlen($row[$cols['Phone']]) - 1);
                            }
                            $temp = [
                                'nombre' => $directoryNamerUtil->eliminar_extrannos($row[$cols['FirstName']]),
                                'apellidos' => $directoryNamerUtil->eliminar_extrannos(($row[$cols['LastName']] != "" ? (" " . $row[$cols['LastName']]) : "") . "" . ($row[$cols['MaidenName']] != "" ? (" " . $row[$cols['MaidenName']]) : "")),
                                'phone' => $phone,
                                'phone_alt' => null,
                                'direccion' => $directoryNamerUtil->eliminar_extrannos($row[$cols['Address_1']] . " " . $row[$cols['Address_2']]),
                                'municipio' => $municipio,
                                'monto' => $row[$cols['Monto']],
                                'Moneda' => $row[$cols['Moneda']],
                                'Nota' => $row[$cols['Nota']]
                            ];
                        } elseif ($tnDocumento->getTipo() == TnDocumento::TIPO_FACTURA_TM) {
                            $tnAgencia = $agenciaRepository->findOneBy(['nombre' => $row[$cols['Agency']]]);
                            if ($tnAgencia instanceof TnAgencia && $tnAgencia->getUsuario() != null) {
                                $temp = [
                                    'agency' => $tnAgencia,
                                    'nombre' => $directoryNamerUtil->eliminar_extrannos($row[$cols['FirstName']]),
                                    'apellidos' => $directoryNamerUtil->eliminar_extrannos(($row[$cols['LastName']] != "" ? (" " . $row[$cols['LastName']]) : "") . "" . ($row[$cols['MaidenName']] != "" ? (" " . $row[$cols['MaidenName']]) : "")),
                                    'phone' => $row[$cols['Phone']],
                                    'phone_alt' => $row[$cols['Phone_Alt']] != "" ? $row[$cols['Phone_Alt']] : null,
                                    'direccion' => $directoryNamerUtil->eliminar_extrannos($row[$cols['Address_1']] . " " . $row[$cols['Address_2']]),
                                    'municipio' => $municipio,
                                    'monto' => $row[$cols['Monto']],
                                    'Moneda' => $row[$cols['Moneda']]
                                ];
                            } else {
                                $temp = null;
                                $arrayRowsFails[] = [$i, 'Agencia o Usuario'];
                            }
                        } else {
                            $temp = [
                                'nombre' => $directoryNamerUtil->eliminar_extrannos($row[$cols['FirstName']]),
                                'apellidos' => $directoryNamerUtil->eliminar_extrannos(($row[$cols['LastName']] != "" ? (" " . $row[$cols['LastName']]) : "") . "" . ($row[$cols['MaidenName']] != "" ? (" " . $row[$cols['MaidenName']]) : "")),
                                'phone' => $row[$cols['Phone']],
                                'phone_alt' => $row[$cols['Phone_Alt']] != "" ? $row[$cols['Phone_Alt']] : null,
                                'direccion' => $directoryNamerUtil->eliminar_extrannos($row[$cols['Address_1']] . " " . $row[$cols['Address_2']]),
                                'municipio' => $municipio,
                                'monto' => $row[$cols['Monto']],
                                'Moneda' => $row[$cols['Moneda']]
                            ];
                        }
                        if ($temp != null) {
                            $resultProcess[] = $temp;
                            $resultProcessPos[] = $i;
                        }
                    } else {
                        $arrayRowsFails[] = [$i, 'Municipio'];
                    }
                } else {
                    $arrayRowsFails[] = [$i, $erroes];
                }
                $i++;
            }
        } catch (\Exception $ex) {
            $this->get('session')->getFlashBag()->add('error', 'El fichero selecciona contiene errores, no pudo procesarse, revise caracteres extraños o columnas faltantes.');
            return $this->redirectToRoute('tn_documento_index');
        }

        $em = $this->getDoctrine()->getManager();
        $em->getConnection()->beginTransaction();
        try {
            $registerCount = 0;
            $countPos = 0;
            foreach ($resultProcess as $data) {
                $tnAgencia = $tnDocumento->getAgencia();
                $tnEmisor = $tnDocumento->getEmisor();
                if ($tnDocumento->getTipo() == TnDocumento::TIPO_FACTURA_TM) {
                    $tnAgencia = $data['agency'];

                    $tnEmisorAgencia = $emisorRepository->findOneBy(['nombre' => $tnAgencia->getNombre(), 'usuario' => $tnAgencia->getUsuario()]);
                    if ($tnEmisorAgencia == null) {
                        $tnEmisor = new TnEmisor();
                        $tnEmisor->setNombre($tnAgencia->getNombre());
                        $tnEmisor->setApellidos("Agencia");
                        $tnEmisor->setPhone($tnAgencia->getPhone());
                        $tnEmisor->setUsuario($tnAgencia->getUsuario());
                        $tnEmisor->setCountry("CU");
                        $tnEmisor->setEnabled(true);
                        $tnEmisor->setToken((sha1(uniqid())));
                        $em->persist($tnEmisor);
                        $em->flush();
                    } else {
                        $tnEmisor = $tnEmisorAgencia;
                    }
                }

                $monedas = [];
                foreach ($tnAgencia->getGruposPago() as $grupo) {
                    if ($grupo->getMoneda() != null) {
                        if (!in_array($grupo->getMoneda()->getSimbolo(), $monedas)) {
                            $monedas[] = $grupo->getMoneda()->getSimbolo();
                        }
                    }
                }

                if ($tnDocumento->getTipo() == TnDocumento::TIPO_FACTURA_TE || $tnDocumento->getTipo() == TnDocumento::TIPO_FACTURA_CMX || $tnDocumento->getTipo() == TnDocumento::TIPO_FACTURA_TP || $tnDocumento->getTipo() == TnDocumento::TIPO_FACTURA_TM || $tnDocumento->getTipo() == TnDocumento::TIPO_FACTURA_VCB || $tnDocumento->getTipo() == TnDocumento::TIPO_FACTURA_CA) {//Si trae la moneda la busco por la que traiga.
                    $moneda = $em->getRepository(NmMoneda::class)->findOneBy(['simbolo' => trim($data['Moneda'])]);
                } else {
                    $moneda = $em->getRepository(NmMoneda::class)->findOneBy(['simbolo' => NmMoneda::CURRENCY_CUP]);
                }

                if (is_null($moneda) || !in_array($moneda->getSimbolo(), $monedas)) {
                    throw new \Exception("La Agencia " . $tnAgencia->getNombre() . " no tiene configurada la moneda " . $data['Moneda'] . " para operar.");
                }

                if (!$tnAgencia->getUnlimited()) {
                    //Verificando que tenga saldo para registrar la remesa
                    $nmGrupoPago = $grupoPagoRepository->grupoPagoAgencia($tnAgencia, $moneda);
                    if (!is_null($nmGrupoPago)) {
                        $saldoMoneda = $tnSaldoAgenciaRepository->findOneBy(['agencia' => $tnAgencia, 'grupoPago' => $nmGrupoPago]);
                        if (!is_null($saldoMoneda)) {
                            if ($data['monto'] > $saldoMoneda->getSaldo()) {
                                $arrayRowsFails[] = [$resultProcessPos[$countPos], 'Saldo'];
                                $countPos++;
                                continue;
                            }
                        } else {
                            $arrayRowsFails[] = [$resultProcessPos[$countPos], 'Saldo'];
                            $countPos++;
                            continue;
                        }
                    }
                }

//                if ($data['monto'] < $moneda->getMinimo() || $data['monto'] > $moneda->getMaximo()) {//Moneda mínimos y máximos
//                    throw new \Exception("El fichero contiene errores en los montos a entregar, revise lo mínimos y máximos límites");
//                }

                if ($moneda->getSimbolo() == NmMoneda::CURRENCY_USD && $data['monto'] % 100 != 0 && ($tnDocumento->getTipo() != TnDocumento::TIPO_FACTURA_CA)) {//Moneda USD mínimo y por  múltiplos de 50.
                    throw new \Exception("El fichero contiene errores en los montos de remesas en USD, revise que todos los valores sean múltiplo de 100.");
                }
//
                if ($moneda->getSimbolo() == NmMoneda::CURRENCY_EUR && $data['monto'] % 50 != 0) {//Moneda USD mínimo y por  múltiplos de 50.
                    throw new \Exception("El fichero contiene errores en los montos de remesas en EUR, revise que todos los valores sean múltiplo de 50.");
                }

                $grupoPagoAgencia = $em->getRepository(NmGrupoPago::class)->grupoPagoAgencia($tnAgencia, $moneda);
                $tasa = $moneda->getTasaCambio();

                $minimo = $grupoPagoAgencia->getMinimo();
                $utilidadFija = $grupoPagoAgencia->getUtilidadFija();

                $params = [
                    'nombre' => $data['nombre'],
                    'apellidos' => $data['apellidos'],
                    'phone' => $data['phone'],
                    'emisor' => $tnEmisor
                ];
                //Buscamos que exista ese destinatario para la búsqueda
                $arrayDestinatarios = $destinatarioRepository->searchDestinatarioByParams($params, $tnAgencia->getUsuario());

                if (count($arrayDestinatarios) == 0) {
                    $tnDestinatario = new TnDestinatario();
                    $tnDestinatario->setNombre($data['nombre']);
                    $tnDestinatario->setApellidos($data['apellidos']);
                    $tnDestinatario->setPhone($data['phone']);
                    if ($data['phone_alt'] != null) {
                        $tnDestinatario->setPhoneAlternativo($data['phone_alt']);
                    }
                    $tnDestinatario->setDireccion($data['direccion']);
                    $tnDestinatario->setEnabled(true);
                    $tnDestinatario->setCountry("CU");
                    $tnDestinatario->setMunicipio($data['municipio']);
                    $tnDestinatario->setProvincia($data['municipio']->getProvincia());
                    $tnDestinatario->setEmisor($tnEmisor);
                    $tnDestinatario->setUsuario($tnAgencia->getUsuario());
                    $tnDestinatario->setToken((sha1(uniqid())));

                    $em->persist($tnDestinatario);
                } else {//Me quedo con el destinatario que encontró
                    $tnDestinatario = $arrayDestinatarios[0];
                }

//                if ($moneda->getSimbolo() == NmMoneda::CURRENCY_USD && $facturaManager->validarDestinatario($tnAgencia->getUsuario(), $tnDestinatario)) {
//                    throw new \Exception("Hay destinatarios repetidos en el día en el fichero ." . $tnDestinatario->getNombre() . " " . $tnDestinatario->getApellidos());
//                }
                if ($moneda->getSimbolo() == NmMoneda::CURRENCY_EUR && !$facturaManager->validarDestinatarioMonedaProvincia($tnDestinatario, $moneda)) {
                    throw new \Exception("No se está entregando remesas en la moneda EUR en el municipio del destinatario " . $tnDestinatario->getNombre() . " " . $tnDestinatario->getApellidos());
                }


                $tnFactura = new TnFactura();
                $fechaEntrega = new \DateTime();
                $days = $configurationManager->get(TnConfiguration::DIAS_ENTREGA);
                $fechaEntrega->modify('+' . $days . ' day');
                $tnFactura->setFechaEntrega($fechaEntrega);
                $tnFactura->setTotal(0.0);
                $tnFactura->setBtnEmisor(true);
                $tnFactura->setAuth(true);
                $tnFactura->setEmisor($tnEmisor);
                $tnFactura->setAgencia($tnAgencia);
                if (isset($data['Nota']) && $data['Nota'] != "") {
                    $tnFactura->setNotas($data['Nota']);
                }
                if (isset($data['Referencia']) && $data['Referencia'] != "") {
                    $tnFactura->setReferencia($data['Referencia']);
                } else {
                    if (isset($data['Nota']) && $data['Nota'] != "") {
                        $tnFactura->setReferencia($data['Nota']);
                    }
                }
                //Si ya tengo el destinatario entonce sigo con los datos de la remesa
                $tnRemesa = new TnRemesa();
                $tnRemesa->setTotalPagar(0.0);
                $tnRemesa->setImporteEntregar($data['monto']);
                $tnRemesa->setDestinatario($tnDestinatario);
                $tnRemesa->setMoneda($moneda);
                $tnRemesa->setEntregada(false);
                $distZona = $facturaManager->findDistribuidorZona($data['municipio']);
                if (count($distZona) == 1) {//Si tiene un solo distribuidor, se lo asigno a la remesa
                    $tnRemesa->setDistribuidor($distZona[0]);
                }
                $em->persist($tnRemesa);

                $tnFactura->addRemesa($tnRemesa);//Adiciono la remesa a la factura
                $tnFactura->setImporte($data['monto']);

                $tnFactura->setMoneda($moneda->getSimbolo());
                $tnFactura->setTasa($tasa);

                $importeTasa = round(($tnFactura->getImporte() / $tasa), 2); //Importe por el que se debe calcular los porcientos y demás.
                //Completando los datos de la factura.
                //Buscando el porcentaje configurado
                $porcentaje = $facturaManager->porcientoOperacionAgencia($tnAgencia, $moneda);
                if ($porcentaje != null) {
                    $tnFactura->setPorcentajeOpera($porcentaje);
                } else {
                    $tnFactura->setPorcentajeOpera($tnAgencia->getUsuario()->getPorcentaje());
                }
                $tnFactura->setTipoPorcentaje(TnFactura::TIPO_PORCIENTO);
                //Calculo el porciento
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
                    $porcientoAsig = $grupoPagoAgencia->getPorcentaje();
                    $tnFactura->setPorcentajeAsignado($porcientoAsig);
                    $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);
                    $tnFactura->setUtilidadFija(false);
                } else {
                    $porcientoAsig = $grupoPagoAgencia->getPorcentaje();
                    $tnFactura->setPorcentajeAsignado($porcientoAsig);
                    $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);
                    $tnFactura->setUtilidadFija(false);
                }
                $tnFactura->setTotalPagar($totalPagar);

                $tnFactura->setNoFactura($facturaManager->newCodigoFactura());
                $estado = $em->getRepository(NmEstado::class)->findOneBy(['codigo' => NmEstado::ESTADO_PENDIENTE]);
                $tnFactura->setEstado($estado);
                $tnFactura->setSospechosa(false);
                $tnFactura->setToken((sha1(uniqid())));
                $em->persist($tnFactura);

                //Actualizando saldo agencia
                $facturaManager->updateSaldoAgencia($tnAgencia, $moneda, $data['monto']);

                $registerCount++;
                $countPos++;
            }
            //Actualizando el documento
            $tnDocumento->setEstado(TnDocumento::ESTADO_REGISTRADO);
            $tnDocumento->setValidation($arrayRowsFails);
            $tnDocumento->setTotalProcesado(count($rows));
            $tnDocumento->setTotalValido($registerCount);
            $tnDocumento->setTotalNoValido(count($arrayRowsFails));
            $em->persist($tnDocumento);

            $em->flush();
            $em->getConnection()->commit();
            $this->get('session')->getFlashBag()->add('info', 'El fichero ha sido procesado correctamente, un total de ' . $registerCount . " facturas registradas.");
        } catch (\Exception $e) {
            // Rollback the failed transaction attempt
            $em->getConnection()->rollback();
            $this->get('session')->getFlashBag()->add('error', 'El fichero selecciona contiene errores, no pudo procesarse, revise caracteres extraños o columnas faltantes. ' . $e->getMessage());
        }

        return $this->redirectToRoute('tn_documento_index');
    }

    /**
     * @Route("/{id}", name="tn_documento_delete", methods={"DELETE"})
     */
    public function delete(Request $request, TnDocumento $tnDocumento): Response
    {
        if ($this->isCsrfTokenValid('delete' . $tnDocumento->getId(), $request->request->get('_token'))) {
            //Eliminando el archivo del documento
            @unlink($this->getParameter('files_path') . '/' . $tnDocumento->getUrl());

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($tnDocumento);
            $entityManager->flush();

            $this->get('session')->getFlashBag()->add('info', "Documento y ficheros eliminados correctamente");
        }

        return $this->redirectToRoute('tn_documento_index');
    }

    /**
     * @Route("/emisores/agencia", name="admin_find_emisores_agencia")
     * @Method({"GET", "POST"})
     */
    public function findEmisoresAgenciaAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        if ($request->isXmlHttpRequest()) {
            $idAgencia = $request->get('idAgencia');
            $emisores = $em->getRepository(TnEmisor::class)->findBy(array('usuario' => $idAgencia));

            $result = array();
            foreach ($emisores as $emisor) {
                $temp['value'] = $emisor->getId();
                $temp['text'] = $emisor->getNombre() . " " . $emisor->getApellidos();
                $result[] = $temp;
            }
            $response = new Response();
            $response->setContent(json_encode(array('emisores' => $result)));

            return $response;
        }
    }

    /**
     * @Route("/validations/{id}/invalid", name="tn_documento_validation_invalid", methods={"GET","POST"})
     */
    public function validationInvalid(Request $request, TnDocumento $tnDocumento, DirectoryNamerUtil $directoryNamerUtil): Response
    {
        $invalids = $tnDocumento->getValidation();

        $result = [];
        if ($tnDocumento->getTipo() == TnDocumento::TIPO_DESTINATARIO) {
            $rows = $directoryNamerUtil->parseCsv($tnDocumento);
            foreach ($invalids as $invalid) {
                $pos = $invalid[0];
                $temp = $rows[$pos - 2];
                $temp[] = is_array($invalid[1]) ? implode(', ', $invalid[1]) : $invalid[1];
                unset($temp[3]);
                unset($temp[4]);
                unset($temp[9]);
                unset($temp[11]);
                unset($temp[12]);
                unset($temp[13]);
                $result[] = $temp;
            }

            return $this->render('backend/tn_documento/validation.html.twig', [
                'invalids' => $result,
                'tn_documento' => $tnDocumento
            ]);
        } else {
            //Parseo el csv que me devuelve todos los datos importados
            if ($tnDocumento->getTipo() == TnDocumento::TIPO_FACTURA_TE) {
                $rows = $directoryNamerUtil->parseCsvTransExport($tnDocumento);

                foreach ($invalids as $invalid) {
                    $pos = $invalid[0];
                    $temp = $rows[$pos - 1];
                    $temp[] = is_array($invalid[1]) ? implode(', ', $invalid[1]) : $invalid[1];
                    $result[] = $temp;
                }
            } elseif ($tnDocumento->getTipo() == TnDocumento::TIPO_FACTURA_CA) {
                $rows = $directoryNamerUtil->parseCsvCanada($tnDocumento);

                foreach ($invalids as $invalid) {
                    $pos = $invalid[0];
                    $temp = $rows[$pos - 1];
                    $temp[] = is_array($invalid[1]) ? implode(', ', $invalid[1]) : $invalid[1];

                    $rowData = [];
                    $rowData[] = $temp[4];
                    $rowData[] = $temp[5];
                    $rowData[] = $temp[6];
                    $rowData[] = $temp[7] . " " . $temp[8];
                    $rowData[] = $temp[22];
                    $rowData[] = $temp[12];
                    $rowData[] = $temp[16];
                    $rowData[] = $temp[23];

                    $result[] = $rowData;
                }
            } elseif ($tnDocumento->getTipo() == TnDocumento::TIPO_FACTURA_TM) {
                $rows = $directoryNamerUtil->parseCsvTramiPro($tnDocumento);
                foreach ($invalids as $invalid) {
                    $pos = $invalid[0];
                    $temp = $rows[$pos - 1];
                    $temp[] = is_array($invalid[1]) ? implode(', ', $invalid[1]) : $invalid[1];

                    $rowData = [];
                    $rowData[] = $temp[0];
                    $rowData[] = $temp[6];
                    $rowData[] = $temp[7];
                    $rowData[] = $temp[8];
                    $rowData[] = $temp[9] . " " . $temp[10];
                    $rowData[] = $temp[14];
                    $rowData[] = $temp[16];
                    $rowData[] = $temp[18];
                    $rowData[] = $temp[19];
                    $rowData[] = $temp[23];

                    $result[] = $rowData;
                }
            } else {
                $rows = $directoryNamerUtil->parseCsvTramiPro($tnDocumento);
                foreach ($invalids as $invalid) {
                    $pos = $invalid[0];
                    $temp = $rows[$pos - 1];
                    $temp[] = is_array($invalid[1]) ? implode(', ', $invalid[1]) : $invalid[1];
                    $temp[9] = $temp[9] . " " . $temp[10];
                    unset($temp[0]);
                    unset($temp[1]);
                    unset($temp[2]);
                    unset($temp[3]);
                    unset($temp[4]);
                    unset($temp[5]);
                    unset($temp[5]);
                    unset($temp[10]);
                    unset($temp[11]);
                    unset($temp[12]);
                    unset($temp[13]);
                    unset($temp[15]);
                    unset($temp[17]);
                    unset($temp[20]);
                    unset($temp[21]);
                    unset($temp[22]);

                    $result[] = $temp;
                }
            }

            return $this->render('backend/tn_documento/validation_factura.html.twig', [
                'invalids' => $result,
                'tn_documento' => $tnDocumento
            ]);
        }
    }

    /**
     * @Route("/print/{id}/invalid", name="tn_documento_validation_invalid_print", methods={"GET","POST"})
     */
    public function printDocumentoAction(Request $request, TnDocumento $tnDocumento, DirectoryNamerUtil $directoryNamerUtil, PhpExcelManager $excelManager)
    {
        $invalids = $tnDocumento->getValidation();

        if ($tnDocumento->getTipo() == TnDocumento::TIPO_DESTINATARIO) {
            $rows = $directoryNamerUtil->parseCsv($tnDocumento);
            $result = [];
            foreach ($invalids as $invalid) {
                $pos = $invalid[0];
                $temp = $rows[$pos - 2];
                $temp[] = is_array($invalid[1]) ? implode(', ', $invalid[1]) : $invalid[1];
                $result[] = $temp;
            }

            $name = $directoryNamerUtil->eliminar_tildes($tnDocumento->getAgencia()) . "_" . uniqid('Invalidos');
            $report = $excelManager->exportDocumentoNoValidos($result);
            return
                $excelManager->outputFile(
                    $excelManager->getContent(
                        $report
                    ),
                    $name
                );
        } else {
            //Parseo el csv que me devuelve todos los datos importados
            if ($tnDocumento->getTipo() == TnDocumento::TIPO_FACTURA_TE) {
                $rows = $directoryNamerUtil->parseCsvTransExport($tnDocumento);
                $result = [];
                foreach ($invalids as $invalid) {
                    $pos = $invalid[0];
                    $temp = $rows[$pos - 1];
                    $temp[] = is_array($invalid[1]) ? implode(', ', $invalid[1]) : $invalid[1];
                    $result[] = $temp;
                }

                $name = $directoryNamerUtil->eliminar_tildes($tnDocumento->getAgencia()) . "_" . uniqid('Invalidos');
                $report = $excelManager->exportDocumentoFacturasNoValidas($result);
                return
                    $excelManager->outputFile(
                        $excelManager->getContent(
                            $report
                        ),
                        $name
                    );
            } elseif ($tnDocumento->getTipo() == TnDocumento::TIPO_FACTURA_CA) {
                $rows = $directoryNamerUtil->parseCsvCanada($tnDocumento);
                $result = [];
                foreach ($invalids as $invalid) {
                    $pos = $invalid[0];
                    $temp = $rows[$pos - 1];
                    $temp[] = is_array($invalid[1]) ? implode(', ', $invalid[1]) : $invalid[1];
                    $result[] = $temp;
                }

                $name = $directoryNamerUtil->eliminar_tildes($tnDocumento->getAgencia()) . "_" . uniqid('Invalidos');
                $report = $excelManager->exportDocumentoFacturasCanada($result);
                return
                    $excelManager->outputFile(
                        $excelManager->getContent(
                            $report
                        ),
                        $name
                    );
            } else {
                $rows = $directoryNamerUtil->parseCsvTramiPro($tnDocumento);
                $result = [];
                foreach ($invalids as $invalid) {
                    $pos = $invalid[0];
                    $temp = $rows[$pos - 1];
                    $temp[] = is_array($invalid[1]) ? implode(', ', $invalid[1]) : $invalid[1];
                    $result[] = $temp;
                }

                $name = $directoryNamerUtil->eliminar_tildes($tnDocumento->getAgencia()) . "_" . uniqid('Invalidos');
                $report = $excelManager->exportDocumentoFacturasTramipro($result);
                return
                    $excelManager->outputFile(
                        $excelManager->getContent(
                            $report
                        ),
                        $name
                    );
            }
        }
    }
}
