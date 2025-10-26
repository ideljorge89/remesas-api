<?php

namespace App\Manager;


use App\Entity\NmEstado;
use App\Entity\NmEstadoTransferencia;
use App\Entity\NmGrupoPago;
use App\Entity\NmGrupoPagoAgente;
use App\Entity\NmGrupoPagoTransf;
use App\Entity\NmGrupoPagoTransfAgente;
use App\Entity\NmMoneda;
use App\Entity\NmMunicipio;
use App\Entity\NmProvincia;
use App\Entity\TnAgencia;
use App\Entity\TnAgente;
use App\Entity\TnApoderado;
use App\Entity\TnApoderadoFactura;
use App\Entity\TnConfiguration;
use App\Entity\TnCredito;
use App\Entity\TnDestinatario;
use App\Entity\TnDistribuidor;
use App\Entity\TnEmisor;
use App\Entity\TnFactura;
use App\Entity\TnHistorialDistribuidor;
use App\Entity\TnOperacionAgencia;
use App\Entity\TnOperacionAgenciaTransf;
use App\Entity\TnOperacionAgente;
use App\Entity\TnOperacionAgenteTransf;
use App\Entity\TnRemesa;
use App\Entity\TnSaldoAgencia;
use App\Entity\TnSaldoAgente;
use App\Entity\TnTransferencia;
use App\Entity\TnUser;
use App\Repository\TnDestinatarioRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FacturaManager
{
    private $container;
    private $entityManager;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->entityManager = $container->get('doctrine')->getManager();
    }

    /**
     * Generar el codigo de una nota de credito.
     * @return string
     * @throws \Exception
     */
    public function newCodigoFactura()
    {
        $lastCode = $this->container->get('configuration')->get(TnConfiguration::FACTURA_CODE, 1);
        $codigo = str_pad(($lastCode + 1), 5, 0, STR_PAD_LEFT);
        $this->container->get('configuration')->set(TnConfiguration::FACTURA_CODE, $lastCode + 1);

        return $codigo;
    }

    /**
     * Generar el codigo de una nota de credito.
     * @return string
     * @throws \Exception
     */
    public function newCodigoTransferencia()
    {
        $lastCode = $this->container->get('configuration')->get(TnConfiguration::TRANFERENCIA_CODE, 1);
        $codigo = str_pad(($lastCode + 1), 5, 0, STR_PAD_LEFT);
        $this->container->get('configuration')->set(TnConfiguration::TRANFERENCIA_CODE, $lastCode + 1);

        return $codigo;
    }

    public function findDistribuidorZona($municipio)
    {
        return $this->entityManager->getRepository(TnDistribuidor::class)->findByZona($municipio);
    }

    public function findAsignacionesZona(TnFactura $factura)
    {
        $total = 0;
        foreach ($factura->getRemesas() as $remesa) {
            if ($remesa->getDistribuidor() != null) {
                $total++;
            }
        }
        if ($total == $factura->getRemesas()->count()) {
            return 'primary';
        } elseif ($total != 0) {
            return 'warning';
        } else {
            return 'danger';
        }
    }

    public function getUsersAgentesByAgencia(TnAgencia $tnAgencia)
    {
        return $this->entityManager->getRepository(TnUser::class)->findUsuariosAgentesAgenciaIds($tnAgencia);
    }

    /**
     * @param TnRemesa $tnRemesa
     * Crear el historial de distribucion de la remesa
     */
    public function crearHistorialDistribucion(TnRemesa $tnRemesa)
    {
        if ($tnRemesa->getDistribuidor() != null && $tnRemesa->getDestinatario() != null && $tnRemesa->getHistorialDistribuidor() == null) {
            $tnDistribuidor = $tnRemesa->getDistribuidor();

            $tnHistorial = new TnHistorialDistribuidor();
            $tnHistorial->setRemesa($tnRemesa);
            //El importe entregado es lo que realmente se entregó, en que moneda y a qué tasa.
            $tnHistorial->setMonedaRemesa($tnRemesa->getMoneda());

            //El importe remesa es para llevar el credito del distribuidor
            $creditoUtilizado = $tnRemesa->getImporteEntregar();
            $tnHistorial->setImporteRemesa($creditoUtilizado);
            $comision = $tnRemesa->getDestinatario()->getMunicipio()->getTasaFija() ? $tnRemesa->getDestinatario()->getMunicipio()->getTasaFija() : 0.0;
            $tnHistorial->setTasaDistrucion($comision);
            $tnHistorial->setEstado(TnHistorialDistribuidor::ESTADO_EFECTIVA);
            $tnHistorial->setDistribuidor($tnDistribuidor);
            //Descontado el crédito al distribuidor
            $tnCredito = $this->entityManager->getRepository(TnCredito::class)->findOneBy(['moneda' => $tnRemesa->getMoneda(), 'distribuidor' => $tnRemesa->getDistribuidor()]);
            if (!is_null($tnCredito)) {//Si el crédito en esa moneda para ese distribuidor existe lo modifico, si no lo creo.
                if ($tnRemesa->getMoneda()->getComision()) {

                    $tnCredito->setCredito($tnCredito->getCredito() - ($creditoUtilizado + $comision));
                } else {
                    $tnCredito->setCredito($tnCredito->getCredito() - $creditoUtilizado);
                    //Actualizando la comisión temporal en el distribuidor
                    $tnDistribuidor->setComision($tnDistribuidor->getComision() + $comision);
                    //Busco la moneda de comision, y descuento del crédito de ese distribuidor en esa moneda
                    $monedaComision = $this->entityManager->getRepository(NmMoneda::class)->findOneBy(['comision' => true]);
                    $tnCreditoComision = $this->entityManager->getRepository(TnCredito::class)->findOneBy(['moneda' => $monedaComision, 'distribuidor' => $tnRemesa->getDistribuidor()]);
                    if (!is_null($tnCreditoComision)) {//Si existe lo actualizo
                        $tnCreditoComision->setCredito($tnCreditoComision->getCredito() - $comision);
                    } else {//Si no existe el credito para la moneda en el distribuidor lo creo.
                        $tnCreditoComision = new TnCredito();
                        $tnCreditoComision->setMoneda($tnRemesa->getMoneda());
                        $tnCreditoComision->setDistribuidor($tnRemesa->getDistribuidor());
                        $tnCreditoComision->setCredito(-$comision);
                    }
                    $this->entityManager->persist($tnCreditoComision);
                    $this->entityManager->persist($tnDistribuidor);
                }
                $this->entityManager->persist($tnCredito);
            } else {
                $tnCredito = new TnCredito();
                $tnCredito->setMoneda($tnRemesa->getMoneda());
                $tnCredito->setDistribuidor($tnRemesa->getDistribuidor());
                if ($tnRemesa->getMoneda()->getComision()) {
                    $tnCredito->setCredito(-($creditoUtilizado + $comision));
                } else {
                    $tnCredito->setCredito(-$creditoUtilizado);
                    //Actualizando la comisión temporal en el distribuidor
                    $tnDistribuidor->setComision($tnDistribuidor->getComision() + $comision);
                    //Busco la moneda de comision, y descuento del crédito de ese distribuidor en esa moneda
                    $monedaComision = $this->entityManager->getRepository(NmMoneda::class)->findOneBy(['comision' => true]);
                    $tnCreditoComision = $this->entityManager->getRepository(TnCredito::class)->findOneBy(['moneda' => $monedaComision, 'distribuidor' => $tnRemesa->getDistribuidor()]);
                    if (!is_null($tnCreditoComision)) {//Si existe lo actualizo
                        $tnCreditoComision->setCredito($tnCreditoComision->getCredito() - $comision);
                    } else {//Si no existe el credito para la moneda en el distribuidor lo creo.
                        $tnCreditoComision = new TnCredito();
                        $tnCreditoComision->setMoneda($tnRemesa->getMoneda());
                        $tnCreditoComision->setDistribuidor($tnRemesa->getDistribuidor());
                        $tnCreditoComision->setCredito(-$comision);
                    }
                    $this->entityManager->persist($tnCreditoComision);
                    $this->entityManager->persist($tnDistribuidor);
                }
                $this->entityManager->persist($tnCredito);
            }

            $this->entityManager->persist($tnHistorial);
            $tnRemesa->setHistorialDistribuidor($tnHistorial);
            $this->entityManager->persist($tnRemesa);
        }
    }

    /**
     * @param TnFactura $tnFactura
     * Cancelar el historial de distribución de una factura
     */
    public function cancelarHistorialFactura(TnFactura $tnFactura)
    {
        foreach ($tnFactura->getRemesas() as $tnRemesa) {
            if ($tnRemesa->getHistorialDistribuidor() != null) {
                $tnHistorial = $tnRemesa->getHistorialDistribuidor();
                $tnDistribuidor = $tnRemesa->getDistribuidor();

                $tnHistorial->setEstado(TnHistorialDistribuidor::ESTADO_CANCELADA);
                //Sumar el crédito al distribuidor
                $tnCredito = $this->entityManager->getRepository(TnCredito::class)->findOneBy(['moneda' => $tnRemesa->getMoneda(), 'distribuidor' => $tnRemesa->getDistribuidor()]);
                if (!is_null($tnCredito)) {//Si el crédito en esa moneda para ese distribuidor existe lo modifico, si no lo creo.
                    if ($tnRemesa->getMoneda()->getComision()) {
                        $tnCredito->setCredito($tnCredito->getCredito() + ($tnHistorial->getImporteRemesa() + $tnHistorial->getTasaDistrucion()));
                        //Poniendo el monto que se canceló del distribuidor
                        $tnDistribuidor->setCancelado($tnDistribuidor->getCancelado() + ($tnHistorial->getImporteRemesa() + $tnHistorial->getTasaDistrucion()));
                        $this->entityManager->persist($tnDistribuidor);
                    } else {//Si no es la moneda de comision
                        $tnCredito->setCredito($tnCredito->getCredito() + $tnHistorial->getImporteRemesa());

                        $monedaComision = $this->entityManager->getRepository(NmMoneda::class)->findOneBy(['comision' => true]);
                        $tnCreditoComision = $this->entityManager->getRepository(TnCredito::class)->findOneBy(['moneda' => $monedaComision, 'distribuidor' => $tnRemesa->getDistribuidor()]);
                        if (!is_null($tnCreditoComision)) {//Si existe lo actualizo
                            $tnCreditoComision->setCredito($tnCreditoComision->getCredito() + $tnHistorial->getTasaDistrucion());
                            if ($tnHistorial->getCierre() == null) {
                                $tnDistribuidor->setComision($tnDistribuidor->getComision() - $tnHistorial->getTasaDistrucion());
                            }
                            $tnDistribuidor->setCancelado($tnDistribuidor->getCancelado() + $tnHistorial->getTasaDistrucion());
                            $this->entityManager->persist($tnCreditoComision);
                            $this->entityManager->persist($tnDistribuidor);
                        }
                    }
                    $this->entityManager->persist($tnCredito);
                }

                $this->entityManager->persist($tnHistorial);
            }
        }
    }

    /**
     * @param TnFactura $tnFactura
     * Cancelar el historial de distribución de una factura
     */
    public function chequearHistorialFactura(TnFactura $tnFactura)
    {
        foreach ($tnFactura->getRemesas() as $tnRemesa) {
            if ($tnRemesa->getHistorialDistribuidor() != null) {
                $tnHistorial = $tnRemesa->getHistorialDistribuidor();
                $comision = $tnRemesa->getDestinatario()->getMunicipio()->getTasaFija() ? $tnRemesa->getDestinatario()->getMunicipio()->getTasaFija() : 0.0;
                if ($tnRemesa->getImporteEntregar() != $tnHistorial->getImporteRemesa() || $comision != $tnHistorial->getTasaDistrucion() || $tnRemesa->getMoneda()->getSimbolo() != $tnHistorial->getMonedaRemesa()) {
                    $tnDistribuidor = $tnRemesa->getDistribuidor();
                    //Restablezco el saldo anterior al distribuidor
                    $tnCredito = $this->entityManager->getRepository(TnCredito::class)->findOneBy(['moneda' => $tnHistorial->getMonedaRemesa(), 'distribuidor' => $tnRemesa->getDistribuidor()]);
                    if (!is_null($tnCredito)) {//Si el crédito en esa moneda para ese distribuidor existe lo modifico, si no lo creo.
                        if ($tnHistorial->getMonedaRemesa()->getComision()) {
                            $tnCredito->setCredito($tnCredito->getCredito() + ($tnHistorial->getImporteRemesa() + $tnHistorial->getTasaDistrucion()));
                        } else {
                            $tnCredito->setCredito($tnCredito->getCredito() + $tnHistorial->getImporteRemesa());

                            $monedaComision = $this->entityManager->getRepository(NmMoneda::class)->findOneBy(['comision' => true]);
                            $tnCreditoComision = $this->entityManager->getRepository(TnCredito::class)->findOneBy(['moneda' => $monedaComision, 'distribuidor' => $tnRemesa->getDistribuidor()]);
                            if (!is_null($tnCreditoComision)) {//Si existe lo actualizo
                                $tnCreditoComision->setCredito($tnCreditoComision->getCredito() + $tnHistorial->getTasaDistrucion());
                                if ($tnHistorial->getCierre() == null) {//Si la remesa es solo de hoy se resta al acumulado diario, si no se descuenta.
                                    $tnDistribuidor->setComision($tnDistribuidor->getComision() - $tnHistorial->getTasaDistrucion());
                                }
                                $this->entityManager->persist($tnCreditoComision);
                                $this->entityManager->persist($tnDistribuidor);
                            }
                        }
                        $this->entityManager->persist($tnCredito);
                    }
                    //Actualizo el historial
                    //El importe entregado es lo que realmente se entregó, en que moneda y a qué tasa.
                    $tnHistorial->setImporteRemesa($tnRemesa->getImporteEntregar());
                    $tnHistorial->setMonedaRemesa($tnRemesa->getMoneda());

                    //El importe remesa es para llevar el credito del distribuidor
                    $creditoUtilizado = $tnRemesa->getImporteEntregar();
                    $tnHistorial->setImporteRemesa($creditoUtilizado);
                    $tnHistorial->setTasaDistrucion($comision);
                    //Desuento lo nuevo con cambio el saldo anterior al distribuidor
                    if ($tnRemesa->getMoneda()->getComision()) {
                        $tnCredito->setCredito($tnCredito->getCredito() - ($creditoUtilizado + $comision));
                    } else {
                        $tnCredito->setCredito($tnCredito->getCredito() - $creditoUtilizado);

                        $monedaComision = $this->entityManager->getRepository(NmMoneda::class)->findOneBy(['comision' => true]);
                        $tnCreditoComision = $this->entityManager->getRepository(TnCredito::class)->findOneBy(['moneda' => $monedaComision, 'distribuidor' => $tnRemesa->getDistribuidor()]);
                        if (!is_null($tnCreditoComision)) {//Si existe lo actualizo
                            $tnCreditoComision->setCredito($tnCreditoComision->getCredito() - $comision);
                            if ($tnHistorial->getCierre() == null) {//Si la remesa es solo de hoy se resta al acumulado diario, si no se descuenta.
                                $tnDistribuidor->setComision($tnDistribuidor->getComision() + $comision);
                            }
                            $this->entityManager->persist($tnCreditoComision);
                            $this->entityManager->persist($tnDistribuidor);
                        }
                    }
                    $this->entityManager->persist($tnCredito);
                    $this->entityManager->persist($tnHistorial);
                }
            }
        }
    }

    /**
     * @param TnRemesa $tnRemesa
     * Cancelar el historial de distribución de una factura
     */
    public function eliminarHistorialRemesa(TnRemesa $tnRemesa)
    {
        if ($tnRemesa->getHistorialDistribuidor() != null) {
            $tnHistorial = $tnRemesa->getHistorialDistribuidor();
            $tnDistribuidor = $tnRemesa->getDistribuidor();

            $tnHistorial->setEstado(TnHistorialDistribuidor::ESTADO_CANCELADA);
            //Sumar el crédito al distribuidor
            $tnCredito = $this->entityManager->getRepository(TnCredito::class)->findOneBy(['moneda' => $tnRemesa->getMoneda(), 'distribuidor' => $tnRemesa->getDistribuidor()]);
            if (!is_null($tnCredito)) {//Si el crédito en esa moneda para ese distribuidor existe lo modifico, si no lo creo.
                if ($tnRemesa->getMoneda()->getComision()) {
                    $tnCredito->setCredito($tnCredito->getCredito() + ($tnHistorial->getImporteRemesa() + $tnHistorial->getTasaDistrucion()));
                } else {//Si no es la moneda de comision
                    $tnCredito->setCredito($tnCredito->getCredito() + $tnHistorial->getImporteRemesa());

                    $monedaComision = $this->entityManager->getRepository(NmMoneda::class)->findOneBy(['comision' => true]);
                    $tnCreditoComision = $this->entityManager->getRepository(TnCredito::class)->findOneBy(['moneda' => $monedaComision, 'distribuidor' => $tnRemesa->getDistribuidor()]);
                    if (!is_null($tnCreditoComision)) {//Si existe lo actualizo
                        $tnCreditoComision->setCredito($tnCreditoComision->getCredito() + $tnHistorial->getTasaDistrucion());
                        $tnDistribuidor->setComision($tnDistribuidor->getComision() - $tnHistorial->getTasaDistrucion());
                        $tnDistribuidor->setCancelado($tnDistribuidor->getCancelado() + $tnHistorial->getTasaDistrucion());
                        $this->entityManager->persist($tnCreditoComision);
                        $this->entityManager->persist($tnDistribuidor);
                    }
                }
                $this->entityManager->persist($tnCredito);
            }

            $this->entityManager->persist($tnHistorial);
        }
    }

    /**
     * @param TnApoderado $tnApoderado
     * @param TnFactura $tnFactura
     * Crear el historial del aporderado de la factura
     */
    public function crearApoderadoFactura(TnApoderado $tnApoderado, TnFactura $tnFactura)
    {
        if (!is_null($tnApoderado) && !is_null($tnFactura)) {
            $agenciaRepresenta = $tnApoderado->getAgencia();

            //Obteniendo la remesa de la factura y sus datos.
            $tnRemesaFactura = $tnFactura->getRemesas()[0];
            //Guardando la moneda y la tasa
            $monedaRemesa = $tnRemesaFactura->getMoneda();
            $tasa = $monedaRemesa->getTasaCambio();
            $importe = $tnFactura->getImporte();

            $importeTasa = round($importe / $tasa); //Importe por el que se debe calcular los porcientos y demás.

            $tnApoderadoFactura = new TnApoderadoFactura();
            $tnApoderadoFactura->setApoderado($tnApoderado);
            $tnApoderadoFactura->setFactura($tnFactura);

            //Datos de la agencia representa.
            $grupoPagoAgencia = $this->entityManager->getRepository(NmGrupoPago::class)->grupoPagoAgencia($agenciaRepresenta, $monedaRemesa);

            $porcientoAsig = $grupoPagoAgencia->getPorcentaje();
            $tnApoderadoFactura->setPorcentajeAsignado($porcientoAsig);
            //Calculo el porciento o utilidad fija
            $minimo = $grupoPagoAgencia->getMinimo();
            $utilidadFija = $grupoPagoAgencia->getUtilidadFija();

            if ($minimo != null && !is_null($utilidadFija) && $importeTasa < $minimo) {
                $tipoUtilidad = $grupoPagoAgencia->getTipoUtilidad();
                if ($tipoUtilidad != "" && $tipoUtilidad == NmGrupoPago::TIPO_PORCIENTO) {
                    $totalPagar = $importeTasa + round((($importeTasa * $utilidadFija) / 100), 2);
                    $tnApoderadoFactura->setPorcentajeAsignado($utilidadFija);
                    $tnApoderadoFactura->setUtilidadFija(false);
                } else {
                    $totalPagar = $importeTasa + $utilidadFija;
                    $tnApoderadoFactura->setPorcentajeAsignado($utilidadFija);
                    $tnApoderadoFactura->setUtilidadFija(true);
                }
            } elseif ($importeTasa < 100) {
                $totalPagar = $importeTasa + round(((100 * $porcientoAsig) / 100), 2);
                $tnApoderadoFactura->setUtilidadFija(false);
            } else {
                $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);
                $tnApoderadoFactura->setUtilidadFija(false);
            }
            $tnApoderadoFactura->setTotalPagar($totalPagar);


            if (!is_null($tnFactura->getAgente())) {//Si la remesa es de un agente que tiene agencia con apoderado es el proceder diferente
                if ($tnFactura->getAgente()->getAgencia()->getId() != $agenciaRepresenta->getId()) {//Si es la misma agencia, se le cobra al agente
                    //Datos de la agencia Subordinada.
                    $grupoPagoAgenciaSub = $this->entityManager->getRepository(NmGrupoPago::class)->grupoPagoAgencia($tnFactura->getAgente()->getAgencia(), $monedaRemesa);

                    $porcientoAsigSub = $grupoPagoAgenciaSub->getPorcentaje();
                    $tnApoderadoFactura->setPorcentajeAsignadoSubordinada($porcientoAsigSub);

                    //Calculo el porciento o utilidad fija
                    $minimo = $grupoPagoAgenciaSub->getMinimo();
                    $utilidadFija = $grupoPagoAgenciaSub->getUtilidadFija();

                    if ($minimo != null && !is_null($utilidadFija) && $importeTasa < $minimo) {
                        $tipoUtilidad = $grupoPagoAgenciaSub->getTipoUtilidad();
                        if ($tipoUtilidad != "" && $tipoUtilidad == NmGrupoPago::TIPO_PORCIENTO) {
                            $totalPagarSub = $importeTasa + round((($importeTasa * $utilidadFija) / 100), 2);
                            $tnApoderadoFactura->setPorcentajeAsignadoSubordinada($utilidadFija);
                            $tnApoderadoFactura->setUtilidadFijaFijaSubordinada(false);
                        } else {
                            $totalPagarSub = $importeTasa + $utilidadFija;
                            $tnApoderadoFactura->setPorcentajeAsignadoSubordinada($utilidadFija);
                            $tnApoderadoFactura->setUtilidadFijaFijaSubordinada(true);
                        }
                    } elseif ($importeTasa < 100) {
                        $totalPagarSub = $importeTasa + round(((100 * $porcientoAsigSub) / 100), 2);
                        $tnApoderadoFactura->setUtilidadFijaFijaSubordinada(false);
                    } else {
                        $totalPagarSub = $importeTasa + round((($importeTasa * $porcientoAsigSub) / 100), 2);
                        $tnApoderadoFactura->setUtilidadFijaFijaSubordinada(false);
                    }
                    $tnApoderadoFactura->setTotalPagarSubordinada($totalPagarSub);
                    $tnApoderadoFactura->setUtilidad($totalPagarSub - $totalPagar);
                    $tnApoderadoFactura->setTipoPorcentaje(TnFactura::TIPO_PORCIENTO);
                    $tnApoderadoFactura->setAgencia($tnFactura->getAgente()->getAgencia());
                } else {//Entonces se calcula en función del Agente
                    //Datos de la agencia Subordinada.
                    $grupoPagoAgente = $this->entityManager->getRepository(NmGrupoPagoAgente::class)->grupoPagoAgente($tnFactura->getAgente(), $monedaRemesa);

                    if ($grupoPagoAgente->getUtilidad()) {//Viendo el que grupo de pago está el agente
                        //Poniendo los valores con que se crea la factura
                        $porcientoAsig = $grupoPagoAgente->getUtilidad();
                        $tnApoderadoFactura->setPorcentajeAsignadoSubordinada($porcientoAsig);
                        $tnApoderadoFactura->setTipoPorcentaje(TnFactura::TIPO_UTILIDAD);
                        //Calculo el porciento
                        $totalPagar = $importeTasa + $porcientoAsig;
                        $tnApoderadoFactura->setTotalPagarSubordinada($totalPagar);
                        $tnApoderadoFactura->setUtilidadFija(false);
                    } else {
                        $porcientoAsigSub = $grupoPagoAgente->getPorcentaje();
                        $tnApoderadoFactura->setPorcentajeAsignadoSubordinada($porcientoAsigSub);

                        //Calculo el porciento o utilidad fija
                        $minimo = $grupoPagoAgente->getMinimo();
                        $utilidadFija = $grupoPagoAgente->getUtilidadFija();

                        if ($minimo != null && !is_null($utilidadFija) && $importeTasa < $minimo) {
                            $tipoUtilidad = $grupoPagoAgente->getTipoUtilidad();
                            if ($tipoUtilidad != "" && $tipoUtilidad == NmGrupoPago::TIPO_PORCIENTO) {
                                $totalPagarSub = $importeTasa + round((($importeTasa * $utilidadFija) / 100), 2);
                                $tnApoderadoFactura->setPorcentajeAsignadoSubordinada($utilidadFija);
                                $tnApoderadoFactura->setUtilidadFijaFijaSubordinada(false);
                            } else {
                                $totalPagarSub = $importeTasa + $utilidadFija;
                                $tnApoderadoFactura->setPorcentajeAsignadoSubordinada($utilidadFija);
                                $tnApoderadoFactura->setUtilidadFijaFijaSubordinada(true);
                            }
                        } elseif ($importeTasa < 100) {
                            $totalPagarSub = $importeTasa + round(((100 * $porcientoAsigSub) / 100), 2);
                            $tnApoderadoFactura->setUtilidadFijaFijaSubordinada(false);
                        } else {
                            $totalPagarSub = $importeTasa + round((($importeTasa * $porcientoAsigSub) / 100), 2);
                            $tnApoderadoFactura->setUtilidadFijaFijaSubordinada(false);
                        }
                        $tnApoderadoFactura->setTotalPagarSubordinada($totalPagarSub);
                        $tnApoderadoFactura->setUtilidad($totalPagarSub - $totalPagar);
                        $tnApoderadoFactura->setTipoPorcentaje(TnFactura::TIPO_PORCIENTO);
                        $tnApoderadoFactura->setAgencia($tnFactura->getAgente()->getAgencia());
                    }
                }
            } else {
                if ($tnFactura->getAgencia()->getId() != $agenciaRepresenta->getId()) {//Si es la misma agencia, se le cobra al agente
                    //Datos de la agencia Subordinada.
                    $grupoPagoAgenciaSub = $this->entityManager->getRepository(NmGrupoPago::class)->grupoPagoAgencia($tnFactura->getAgencia(), $monedaRemesa);

                    $porcientoAsigSub = $grupoPagoAgenciaSub->getPorcentaje();
                    $tnApoderadoFactura->setPorcentajeAsignadoSubordinada($porcientoAsigSub);

                    //Calculo el porciento o utilidad fija
                    $minimo = $grupoPagoAgenciaSub->getMinimo();
                    $utilidadFija = $grupoPagoAgenciaSub->getUtilidadFija();

                    if ($minimo != null && !is_null($utilidadFija) && $importeTasa < $minimo) {
                        $tipoUtilidad = $grupoPagoAgenciaSub->getTipoUtilidad();
                        if ($tipoUtilidad != "" && $tipoUtilidad == NmGrupoPago::TIPO_PORCIENTO) {
                            $totalPagarSub = $importeTasa + round((($importeTasa * $utilidadFija) / 100), 2);
                            $tnApoderadoFactura->setPorcentajeAsignadoSubordinada($utilidadFija);
                            $tnApoderadoFactura->setUtilidadFijaFijaSubordinada(false);
                        } else {
                            $totalPagarSub = $importeTasa + $utilidadFija;
                            $tnApoderadoFactura->setPorcentajeAsignadoSubordinada($utilidadFija);
                            $tnApoderadoFactura->setUtilidadFijaFijaSubordinada(true);
                        }
                    } elseif ($importeTasa < 100) {
                        $totalPagarSub = $importeTasa + round(((100 * $porcientoAsigSub) / 100), 2);
                        $tnApoderadoFactura->setUtilidadFijaFijaSubordinada(false);
                    } else {
                        $totalPagarSub = $importeTasa + round((($importeTasa * $porcientoAsigSub) / 100), 2);
                        $tnApoderadoFactura->setUtilidadFijaFijaSubordinada(false);
                    }
                    $tnApoderadoFactura->setTotalPagarSubordinada($totalPagarSub);
                    $tnApoderadoFactura->setUtilidad($totalPagarSub - $totalPagar);
                    $tnApoderadoFactura->setTipoPorcentaje(TnFactura::TIPO_PORCIENTO);
                    $tnApoderadoFactura->setAgencia($tnFactura->getAgencia());
                } else {
                    //Datos de la agencia Subordinada.
                    $user = $agenciaRepresenta->getUsuario();
                    $porcientoAsigSub = $user->getPorcentaje();
                    $tnApoderadoFactura->setPorcentajeAsignadoSubordinada($porcientoAsigSub);

                    $tnApoderadoFactura->setTotalPagarSubordinada($tnFactura->getTotal());
                    $tnApoderadoFactura->setUtilidadFijaFijaSubordinada(false);
                    $tnApoderadoFactura->setUtilidad($tnFactura->getTotal() - $totalPagar);
                    $tnApoderadoFactura->setTipoPorcentaje(TnFactura::TIPO_PORCIENTO);
                    $tnApoderadoFactura->setAgencia($tnFactura->getAgencia());
                }
            }

            $this->entityManager->persist($tnApoderadoFactura);
        }
    }

    /**
     * @param TnApoderado $tnApoderado
     * @param TnFactura $tnFactura
     * Actualizar el historial del aporderado de la factura
     */
    public function updateApoderadoFactura(TnApoderado $tnApoderado, TnFactura $tnFactura, TnApoderadoFactura $tnApoderadoFactura)
    {
        if (!is_null($tnApoderado) && !is_null($tnFactura)) {
            $agenciaRepresenta = $tnApoderado->getAgencia();

            //Obteniendo la remesa de la factura y sus datos.
            $tnRemesaFactura = $tnFactura->getRemesas()[0];
            //Guardando la moneda y la tasa
            $monedaRemesa = $tnRemesaFactura->getMoneda();
            $tasa = $monedaRemesa->getTasaCambio();
            $importe = $tnFactura->getImporte();

            $importeTasa = round($importe / $tasa); //Importe por el que se debe calcular los porcientos y demás.

            //Datos de la agencia representa.
            $grupoPagoAgencia = $this->entityManager->getRepository(NmGrupoPago::class)->grupoPagoAgencia($agenciaRepresenta, $monedaRemesa);

            $porcientoAsig = $grupoPagoAgencia->getPorcentaje();
            $tnApoderadoFactura->setPorcentajeAsignado($porcientoAsig);
            //Calculo el porciento o utilidad fija
            $minimo = $grupoPagoAgencia->getMinimo();
            $utilidadFija = $grupoPagoAgencia->getUtilidadFija();

            if ($minimo != null && !is_null($utilidadFija) && $importeTasa < $minimo) {
                $tipoUtilidad = $grupoPagoAgencia->getTipoUtilidad();
                if ($tipoUtilidad != "" && $tipoUtilidad == NmGrupoPago::TIPO_PORCIENTO) {
                    $totalPagar = $importeTasa + round((($importeTasa * $utilidadFija) / 100), 2);
                    $tnApoderadoFactura->setPorcentajeAsignado($utilidadFija);
                    $tnApoderadoFactura->setUtilidadFija(false);
                } else {
                    $totalPagar = $importeTasa + $utilidadFija;
                    $tnApoderadoFactura->setPorcentajeAsignado($utilidadFija);
                    $tnApoderadoFactura->setUtilidadFija(true);
                }
            } elseif ($importeTasa < 100) {
                $totalPagar = $importeTasa + round(((100 * $porcientoAsig) / 100), 2);
                $tnApoderadoFactura->setUtilidadFija(false);
            } else {
                $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);
                $tnApoderadoFactura->setUtilidadFija(false);
            }
            $tnApoderadoFactura->setTotalPagar($totalPagar);


            if (!is_null($tnFactura->getAgente())) {//Si la remesa es de un agente que tiene agencia con apoderado es el proceder diferente
                if ($tnFactura->getAgente()->getAgencia()->getId() != $agenciaRepresenta->getId()) {//Si es la misma agencia, se le cobra al agente
                    //Datos de la agencia Subordinada.
                    $grupoPagoAgenciaSub = $this->entityManager->getRepository(NmGrupoPago::class)->grupoPagoAgencia($tnFactura->getAgente()->getAgencia(), $monedaRemesa);

                    $porcientoAsigSub = $grupoPagoAgenciaSub->getPorcentaje();
                    $tnApoderadoFactura->setPorcentajeAsignadoSubordinada($porcientoAsigSub);

                    //Calculo el porciento o utilidad fija
                    $minimo = $grupoPagoAgenciaSub->getMinimo();
                    $utilidadFija = $grupoPagoAgenciaSub->getUtilidadFija();

                    if ($minimo != null && !is_null($utilidadFija) && $importeTasa < $minimo) {
                        $tipoUtilidad = $grupoPagoAgenciaSub->getTipoUtilidad();
                        if ($tipoUtilidad != "" && $tipoUtilidad == NmGrupoPago::TIPO_PORCIENTO) {
                            $totalPagarSub = $importeTasa + round((($importeTasa * $utilidadFija) / 100), 2);
                            $tnApoderadoFactura->setPorcentajeAsignadoSubordinada($utilidadFija);
                            $tnApoderadoFactura->setUtilidadFijaFijaSubordinada(false);
                        } else {
                            $totalPagarSub = $importeTasa + $utilidadFija;
                            $tnApoderadoFactura->setPorcentajeAsignadoSubordinada($utilidadFija);
                            $tnApoderadoFactura->setUtilidadFijaFijaSubordinada(true);
                        }
                    } elseif ($importeTasa < 100) {
                        $totalPagarSub = $importeTasa + round(((100 * $porcientoAsigSub) / 100), 2);
                        $tnApoderadoFactura->setUtilidadFijaFijaSubordinada(false);
                    } else {
                        $totalPagarSub = $importeTasa + round((($importeTasa * $porcientoAsigSub) / 100), 2);
                        $tnApoderadoFactura->setUtilidadFijaFijaSubordinada(false);
                    }
                    $tnApoderadoFactura->setTotalPagarSubordinada($totalPagarSub);
                    $tnApoderadoFactura->setUtilidad($totalPagarSub - $totalPagar);
                    $tnApoderadoFactura->setTipoPorcentaje(TnFactura::TIPO_PORCIENTO);
                    $tnApoderadoFactura->setAgencia($tnFactura->getAgente()->getAgencia());
                } else {//Entonces se calcula en función del Agente
                    //Datos de la agencia Subordinada.
                    $grupoPagoAgente = $this->entityManager->getRepository(NmGrupoPagoAgente::class)->grupoPagoAgente($tnFactura->getAgente(), $monedaRemesa);

                    if ($grupoPagoAgente->getUtilidad()) {//Viendo el que grupo de pago está el agente
                        //Poniendo los valores con que se crea la factura
                        $porcientoAsig = $grupoPagoAgente->getUtilidad();
                        $tnApoderadoFactura->setPorcentajeAsignadoSubordinada($porcientoAsig);
                        $tnApoderadoFactura->setTipoPorcentaje(TnFactura::TIPO_UTILIDAD);
                        //Calculo el porciento
                        $totalPagar = $importeTasa + $porcientoAsig;
                        $tnApoderadoFactura->setTotalPagarSubordinada($totalPagar);
                        $tnApoderadoFactura->setUtilidadFija(false);
                    } else {
                        $porcientoAsigSub = $grupoPagoAgente->getPorcentaje();
                        $tnApoderadoFactura->setPorcentajeAsignadoSubordinada($porcientoAsigSub);

                        //Calculo el porciento o utilidad fija
                        $minimo = $grupoPagoAgente->getMinimo();
                        $utilidadFija = $grupoPagoAgente->getUtilidadFija();

                        if ($minimo != null && !is_null($utilidadFija) && $importeTasa < $minimo) {
                            $tipoUtilidad = $grupoPagoAgente->getTipoUtilidad();
                            if ($tipoUtilidad != "" && $tipoUtilidad == NmGrupoPago::TIPO_PORCIENTO) {
                                $totalPagarSub = $importeTasa + round((($importeTasa * $utilidadFija) / 100), 2);
                                $tnApoderadoFactura->setPorcentajeAsignadoSubordinada($utilidadFija);
                                $tnApoderadoFactura->setUtilidadFijaFijaSubordinada(false);
                            } else {
                                $totalPagarSub = $importeTasa + $utilidadFija;
                                $tnApoderadoFactura->setPorcentajeAsignadoSubordinada($utilidadFija);
                                $tnApoderadoFactura->setUtilidadFijaFijaSubordinada(true);
                            }
                        } elseif ($importeTasa < 100) {
                            $totalPagarSub = $importeTasa + round(((100 * $porcientoAsigSub) / 100), 2);
                            $tnApoderadoFactura->setUtilidadFijaFijaSubordinada(false);
                        } else {
                            $totalPagarSub = $importeTasa + round((($importeTasa * $porcientoAsigSub) / 100), 2);
                            $tnApoderadoFactura->setUtilidadFijaFijaSubordinada(false);
                        }
                        $tnApoderadoFactura->setTotalPagarSubordinada($totalPagarSub);
                        $tnApoderadoFactura->setUtilidad($totalPagarSub - $totalPagar);
                        $tnApoderadoFactura->setTipoPorcentaje(TnFactura::TIPO_PORCIENTO);
                        $tnApoderadoFactura->setAgencia($tnFactura->getAgente()->getAgencia());
                    }
                }
            } else {
                if ($tnFactura->getAgencia()->getId() != $agenciaRepresenta->getId()) {//Si es la misma agencia, se le cobra al agente
                    //Datos de la agencia Subordinada.
                    $grupoPagoAgenciaSub = $this->entityManager->getRepository(NmGrupoPago::class)->grupoPagoAgencia($tnFactura->getAgencia(), $monedaRemesa);

                    $porcientoAsigSub = $grupoPagoAgenciaSub->getPorcentaje();
                    $tnApoderadoFactura->setPorcentajeAsignadoSubordinada($porcientoAsigSub);

                    //Calculo el porciento o utilidad fija
                    $minimo = $grupoPagoAgenciaSub->getMinimo();
                    $utilidadFija = $grupoPagoAgenciaSub->getUtilidadFija();

                    if ($minimo != null && !is_null($utilidadFija) && $importeTasa < $minimo) {
                        $tipoUtilidad = $grupoPagoAgenciaSub->getTipoUtilidad();
                        if ($tipoUtilidad != "" && $tipoUtilidad == NmGrupoPago::TIPO_PORCIENTO) {
                            $totalPagarSub = $importeTasa + round((($importeTasa * $utilidadFija) / 100), 2);
                            $tnApoderadoFactura->setPorcentajeAsignadoSubordinada($utilidadFija);
                            $tnApoderadoFactura->setUtilidadFijaFijaSubordinada(false);
                        } else {
                            $totalPagarSub = $importeTasa + $utilidadFija;
                            $tnApoderadoFactura->setPorcentajeAsignadoSubordinada($utilidadFija);
                            $tnApoderadoFactura->setUtilidadFijaFijaSubordinada(true);
                        }
                    } elseif ($importeTasa < 100) {
                        $totalPagarSub = $importeTasa + round(((100 * $porcientoAsigSub) / 100), 2);
                        $tnApoderadoFactura->setUtilidadFijaFijaSubordinada(false);
                    } else {
                        $totalPagarSub = $importeTasa + round((($importeTasa * $porcientoAsigSub) / 100), 2);
                        $tnApoderadoFactura->setUtilidadFijaFijaSubordinada(false);
                    }
                    $tnApoderadoFactura->setTotalPagarSubordinada($totalPagarSub);
                    $tnApoderadoFactura->setUtilidad($totalPagarSub - $totalPagar);
                    $tnApoderadoFactura->setTipoPorcentaje(TnFactura::TIPO_PORCIENTO);
                    $tnApoderadoFactura->setAgencia($tnFactura->getAgencia());
                } else {
                    //Datos de la agencia Subordinada.
                    $user = $agenciaRepresenta->getUsuario();
                    $porcientoAsigSub = $user->getPorcentaje();
                    $tnApoderadoFactura->setPorcentajeAsignadoSubordinada($porcientoAsigSub);

                    $tnApoderadoFactura->setTotalPagarSubordinada($tnFactura->getTotal());
                    $tnApoderadoFactura->setUtilidadFijaFijaSubordinada(false);
                    $tnApoderadoFactura->setUtilidad($tnFactura->getTotal() - $totalPagar);
                    $tnApoderadoFactura->setTipoPorcentaje(TnFactura::TIPO_PORCIENTO);
                    $tnApoderadoFactura->setAgencia($tnFactura->getAgencia());
                }
            }

            $this->entityManager->persist($tnApoderadoFactura);
        }
    }

    /**
     * Registra todas las remesas que vengan en $data una vez validadas
     * @param $data
     */
    public function newFacturasRemesas(TnUser $tnUser, $resultProcess)
    {
        $this->entityManager->getConnection()->beginTransaction();
        try {
            $registerCount = 0;
            $arrayRowsFails = [];
            foreach ($resultProcess as $data) {
                $tnAgencia = $tnUser->getAgencia();
                $errores = [];

                //Validando que no esté registrada una remesa con esa referencia.
                $tnFacturaExiste = $this->entityManager->getRepository(TnFactura::class)->findOneBy(['referencia' => $data->getReferencia(), 'agencia' => $tnAgencia]);

                if (!is_null($tnFacturaExiste)) {
                    $errores[] = [
                        'Referencia' => "Ya se ha registrado una factura con la referencia " . $data->getReferencia() . ", verifique."
                    ];
                }

                //Validaciones para esa remesas
                $monedas = []; // Primero la moneda
                foreach ($tnAgencia->getGruposPago() as $grupo) {
                    if ($grupo->getMoneda() != null) {
                        if (!in_array($grupo->getMoneda()->getSimbolo(), $monedas)) {
                            $monedas[] = $grupo->getMoneda()->getSimbolo();
                        }
                    }
                }
                $moneda = $this->entityManager->getRepository(NmMoneda::class)->findOneBy(['simbolo' => $data->getMoneda(), 'enabled' => true]);

                if ($moneda != null) {
                    if (!in_array($moneda->getSimbolo(), $monedas)) {
                        $errores[] = [
                            'Moneda' => "La Agencia " . $tnAgencia->getNombre() . " no tiene configurada la moneda " . $moneda->getSimbolo() . " para operar."
                        ];
                    }
                } else {
                    $errores[] = [
                        'Moneda' => "Moneda " . $data->getMoneda() . " no válida o no habilitada para el envío de remesas."
                    ];
                }

                if (!$tnAgencia->getUnlimited()) {
                    if ($data->getMonto() < $moneda->getMinimo() || $data->getMonto() > $moneda->getMaximo()) {//Moneda mínimo y máximo.
                        $errores[] = [
                            'Moneda' => "Para las remesas en " . $moneda->getSimbolo() . " el monto a entregar debe estar entre " . $moneda->getMinimo() . " y " . $moneda->getMaximo() . "."
                        ];
                    }
                }

                if ($moneda->getSimbolo() == NmMoneda::CURRENCY_USD && $data->getMonto() % 100 != 0) {//Moneda USD múltiplos de 50.
                    $errores[] = [
                        'Moneda' => "Para las remesas en USD, revise que el valor sea múltiplo de 100."
                    ];
                }

                if ($moneda->getSimbolo() == NmMoneda::CURRENCY_EUR && $data->getMonto() % 50 != 0) {//Moneda USD múltiplos de 50.
                    $errores[] = [
                        'Moneda' => "Para las remesas en EUR, revise que el valor sea múltiplo de 50."
                    ];
                }

                if ($tnAgencia->getNombre() == "Cubamax") { ///Validando el municipio exista y que lo tengamos registrado.
                    //Busco el municio por la referencia que ellos me dan.
                    $provincia = $this->entityManager->getRepository(NmProvincia::class)->findOneBy(['referencia' => $data->getProvincia()]);
                    $municipio = $this->entityManager->getRepository(NmMunicipio::class)->findOneBy(['referencia' => $data->getMunicipio(), 'provincia' => $provincia]);
                } else {
                    $codigoEnviado = trim($data->getMunicipio());
                    if (strlen($codigoEnviado) == 7) {
                        $codigo = substr($codigoEnviado, 1, strlen($codigoEnviado) - 1);
                    } else {
                        $codigo = strlen($codigoEnviado) == 5 ? "0" . $codigoEnviado : $codigoEnviado;
                    }
                    $municipio = $this->entityManager->getRepository(NmMunicipio::class)->findOneBy(['codigo' => $codigo]);
                }

                if (is_null($municipio)) {
                    $errores[] = [
                        'Municipio' => "El Municipio " . $data->getMunicipio() . " no se encontró en el sistema."
                    ];
                } else {
                    //Si la agencia es nueva y no tiene emisor, se lo creo, si no es el mismo que está creado
                    $tnEmisorAgencia = $this->entityManager->getRepository(TnEmisor::class)->findOneBy(['nombre' => $tnAgencia->getNombre(), 'usuario' => $tnUser]);
                    if ($tnEmisorAgencia == null) {
                        $tnEmisor = new TnEmisor();
                        $tnEmisor->setNombre($tnAgencia->getNombre());
                        $tnEmisor->setApellidos("Agencia");
                        $tnEmisor->setPhone($tnAgencia->getPhone());
                        $tnEmisor->setUsuario($tnAgencia->getUsuario());
                        $tnEmisor->setCountry("CU");
                        $tnEmisor->setEnabled(true);
                        $tnEmisor->setToken((sha1(uniqid())));
                        $this->entityManager->persist($tnEmisor);
                        $this->entityManager->flush();
                    } else {
                        $tnEmisor = $tnEmisorAgencia;
                    }

                    $params = [
                        'nombre' => $data->getNombre(),
                        'apellidos' => $data->getApellido1() . "" . ($data->getApellido2() != "" ? (" " . $data->getApellido2()) : ""),
                        'phone' => $data->getTelefono(),
                        'emisor' => $tnEmisor
                    ];
                    //Buscamos que exista ese destinatario para la búsqueda
                    $arrayDestinatarios = $this->entityManager->getRepository(TnDestinatario::class)->searchDestinatarioByParams($params, $tnAgencia->getUsuario());

                    if (count($arrayDestinatarios) == 0) {
                        $tnDestinatario = new TnDestinatario();
                        $tnDestinatario->setNombre($params['nombre']);
                        $tnDestinatario->setApellidos($params['apellidos']);
                        $tnDestinatario->setPhone($params['phone']);
                        $tnDestinatario->setDireccion($data->getDireccion());
                        $tnDestinatario->setEnabled(true);
                        $tnDestinatario->setCountry("CU");
                        $tnDestinatario->setMunicipio($municipio);
                        $tnDestinatario->setProvincia($municipio->getProvincia());
                        $tnDestinatario->setEmisor($tnEmisor);
                        $tnDestinatario->setUsuario($tnAgencia->getUsuario());
                        $tnDestinatario->setToken((sha1(uniqid())));

                        $this->entityManager->persist($tnDestinatario);
                    } else {//Me quedo con el destinatario que encontró
                        $tnDestinatario = $arrayDestinatarios[0];
                        $municipio = $tnDestinatario->getMunicipio();
                    }
                }

                if ($moneda->getSimbolo() == NmMoneda::CURRENCY_EUR && !$this->validarDestinatarioMonedaProvincia($tnDestinatario, $moneda)) {
                    $errores[] = [
                        'Entrega' => "No se está entregando remesas en la moneda EUR en el municipio del destinatario."
                    ];
                }

//                $validateDestino = $data->getNombre() . " " . $data->getApellido1() . "" . ($data->getApellido2() != "" ? (" " . $data->getApellido2()) : "");
//                if ($this->validarDestinatarioAPI($tnUser, $validateDestino)) {
//                    $errores[] = [
//                        'Destinatario' => "Un mismo destinatario solo le puede ser entregada 1 remesa en el día en la moneda USD."
//                    ];
//                }

                //Verificando que tenga saldo para registrar la remesa

                $nmGrupoPago = $this->entityManager->getRepository(NmGrupoPago::class)->grupoPagoAgencia($tnAgencia, $moneda);
                if (!is_null($nmGrupoPago)) {
                    $saldoMoneda = $this->entityManager->getRepository(TnSaldoAgencia::class)->findOneBy(['agencia' => $tnAgencia, 'grupoPago' => $nmGrupoPago]);
                    if (!is_null($saldoMoneda)) {
                        if ($data->getMonto() > $saldoMoneda->getSaldo()) {
                            $errores[] = [
                                'Saldo' => "No dispone de saldo suficiente en la moneda enviada para crear la remesa."
                            ];
                        }
                    } else {
                        $errores[] = [
                            'Saldo' => "No se encontró la configuración de saldo en la moneda de la remesa enviada."
                        ];
                    }
                }


                if (count($errores) > 0) {
                    $arrayRowsFails[] = [
                        'referencia' => $data->getReferencia(),
                        'errores' => $errores
                    ];
                } else {
                    $grupoPagoAgencia = $this->entityManager->getRepository(NmGrupoPago::class)->grupoPagoAgencia($tnAgencia, $moneda);
                    $tasa = $moneda->getTasaCambio();

                    $minimo = $grupoPagoAgencia->getMinimo();
                    $utilidadFija = $grupoPagoAgencia->getUtilidadFija();

                    $tnFactura = new TnFactura();
                    $fechaEntrega = new \DateTime();
                    $days = $this->container->get('configuration')->get(TnConfiguration::DIAS_ENTREGA);
                    $fechaEntrega->modify('+' . $days . ' day');
                    $tnFactura->setFechaEntrega($fechaEntrega);
                    $tnFactura->setTotal(0.0);
                    $tnFactura->setBtnEmisor(true);
                    $tnFactura->setAuth(true);
                    $tnFactura->setEmisor($tnEmisor);
                    $tnFactura->setAgencia($tnAgencia);
                    $tnFactura->setReferencia($data->getReferencia());
                    $tnFactura->setNotas($data->getReferencia() . " - " . $data->getNota());

                    //Si ya tengo el destinatario entonce sigo con los datos de la remesa
                    $tnRemesa = new TnRemesa();
                    $tnRemesa->setTotalPagar(0.0);
                    $tnRemesa->setImporteEntregar($data->getMonto());
                    $tnRemesa->setDestinatario($tnDestinatario);
                    $tnRemesa->setMoneda($moneda);
                    $tnRemesa->setEntregada(false);
                    $distZona = $this->findDistribuidorZona($municipio);
                    if (count($distZona) == 1) {//Si tiene un solo distribuidor, se lo asigno a la remesa
                        $tnRemesa->setDistribuidor($distZona[0]);
                    }
                    $this->entityManager->persist($tnRemesa);

                    $tnFactura->addRemesa($tnRemesa);//Adiciono la remesa a la factura
                    $tnFactura->setImporte($data->getMonto());

                    $tnFactura->setMoneda($moneda->getSimbolo());
                    $tnFactura->setTasa($tasa);

                    $importeTasa = round(($tnFactura->getImporte() / $tasa), 2); //Importe por el que se debe calcular los porcientos y demás.
                    //Completando los datos de la factura.
                    //Buscando el porcentaje configurado
                    $porcentaje = $this->porcientoOperacionAgencia($tnAgencia, $moneda);
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

                    $tnFactura->setNoFactura($this->newCodigoFactura());

                    $estado = $this->entityManager->getRepository(NmEstado::class)->findOneBy(['codigo' => NmEstado::ESTADO_PENDIENTE]);
                    $tnFactura->setEstado($estado);
                    $tnFactura->setSospechosa(false);
                    $tnFactura->setToken((sha1(uniqid())));
                    $this->entityManager->persist($tnFactura);

                    //Actualizando saldo agencia
                    $this->updateSaldoAgencia($tnAgencia, $moneda, $data->getMonto());

                    $registerCount++;
                }
            }

            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();

            return ['success' => true, 'msg' => 'Remesas registradas satisfactoriamente. Total correctas ' . $registerCount, 'fallidas' => $arrayRowsFails];
        } catch
        (\Exception $e) {
            // Rollback the failed transaction attempt
            $this->entityManager->getConnection()->rollback();
            return ['success' => false, 'msg' => 'Ha ocurrido un eror al registrar las remesas.'];
        }
    }


    /**
     * Registra todas las remesas que vengan en $data una vez validadas
     * @param $data
     */
    public function newTransferencias(TnUser $tnUser, $resultProcess)
    {
        $this->entityManager->getConnection()->beginTransaction();
        try {
            $registerCount = 0;
            $arrayRowsFails = [];
            foreach ($resultProcess as $data) {
                $tnAgencia = $tnUser->getAgencia();
                $errores = [];

                //Validando que no esté registrada una remesa con esa referencia.
                $tnFacturaExiste = $this->entityManager->getRepository(TnTransferencia::class)->findOneBy(['referencia' => $data->getReferencia(), 'agencia' => $tnAgencia]);

                if (!is_null($tnFacturaExiste)) {
                    $errores[] = [
                        'Referencia' => "Ya se ha registrado una transferencia con la referencia " . $data->getReferencia() . ", verifique."
                    ];
                }

                //Validaciones para esa remesas
                $monedas = []; // Primero la moneda
                foreach ($tnAgencia->getGruposPagoTransferencias() as $grupo) {
                    if ($grupo->getMoneda() != null) {
                        if (!in_array($grupo->getMoneda()->getSimbolo(), $monedas)) {
                            $monedas[] = $grupo->getMoneda()->getSimbolo();
                        }
                    }
                }
                $moneda = $this->entityManager->getRepository(NmMoneda::class)->findOneBy(['simbolo' => $data->getMoneda(), 'enabled' => true]);

                if ($moneda != null) {
                    if (!in_array($moneda->getSimbolo(), $monedas)) {
                        $errores[] = [
                            'Moneda' => "La Agencia " . $tnAgencia->getNombre() . " no tiene configurada la moneda " . $moneda->getSimbolo() . " para operar."
                        ];
                    }
                } else {
                    $errores[] = [
                        'Moneda' => "Moneda " . $data->getMoneda() . " no válida o no habilitada para el envío de transferencias."
                    ];
                }

                if ($data->getMonto() < $moneda->getMinimoTransferencia() || $data->getMonto() > $moneda->getMaximoTransferencia()) {//Moneda mínimo y máximo.
                    $errores[] = [
                        'Moneda' => "Para las transferencias en " . $moneda->getSimbolo() . " el monto a entregar debe estar entre " . $moneda->getMinimoTransferencia() . " y " . $moneda->getMaximoTransferencia() . "."
                    ];
                }


                if (count($errores) > 0) {
                    $arrayRowsFails[] = [
                        'referencia' => $data->getReferencia(),
                        'errores' => $errores
                    ];
                } else {
                    $grupoPagoAgencia = $this->entityManager->getRepository(NmGrupoPagoTransf::class)->grupoPagoAgencia($tnAgencia, $moneda);
                    $tasa = $moneda->getTasaCambio();

                    $tnTransferencia = new TnTransferencia();
                    $estado = $this->entityManager->getRepository(NmEstadoTransferencia::class)->findOneBy(['codigo' => NmEstadoTransferencia::ESTADO_PENDIENTE]);
                    $tnTransferencia->setEstado($estado);
                    $tnTransferencia->setAgencia($tnAgencia);
                    $tnTransferencia->setCodigo($this->newCodigoTransferencia());
                    $tnTransferencia->setEmisor($data->getEmisor());
                    $tnTransferencia->setTitularTarjeta($data->getTitularTarjeta());
                    $tnTransferencia->setNumeroTarjeta(trim(chunk_split($data->getNumeroTarjeta(), 4, ' ')));
                    $tnTransferencia->setMoneda($moneda);
                    $tnTransferencia->setMonto($data->getMonto());
                    $tnTransferencia->setImporte($data->getMonto());
                    $tnTransferencia->setNotas($data->getNota());
                    $tnTransferencia->setReferencia($data->getReferencia());
                    $tnTransferencia->setAuth(false);

                    $importeTasa = round($data->getMonto() / $tasa); //Importe por el que se debe calcular los porcientos y demás.

                    $porcientoAsig = $grupoPagoAgencia->getPorcentaje();
                    $tnTransferencia->setPorcentajeAsignado($porcientoAsig);
                    //Calculando el porciento con que opera
                    $porcentaje = $this->porcientoOperacionAgenciaTransferencia($tnAgencia, $moneda);
                    if ($porcentaje != null) {
                        $tnTransferencia->setPorcentajeOpera($porcentaje);
                    } else {
                        $tnTransferencia->setPorcentajeOpera($porcientoAsig);
                    }
                    $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);
                    $tnTransferencia->setTotalPagar($totalPagar);
                    $tnTransferencia->setTotalCobrar(0.0);

                    $tnTransferencia->setToken((sha1(uniqid())));

                    $this->entityManager->persist($tnTransferencia);

                    $registerCount++;
                }
            }

            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();

            return ['success' => true, 'msg' => 'Transferencias registradas satisfactoriamente. Total correctas ' . $registerCount, 'fallidas' => $arrayRowsFails];
        } catch
        (\Exception $e) {
            // Rollback the failed transaction attempt
            $this->entityManager->getConnection()->rollback();
            return ['success' => false, 'msg' => 'Ha ocurrido un error al registrar las transferencias.'];
        }
    }

    /**
     * Devuelve la actualización de todas las transferencias que vengan en $data una vez validadas, actualización de estados.
     * @param $data
     */
    public function updateTransferencias(TnUser $tnUser, $resultProcess)
    {
        $this->entityManager->getConnection()->beginTransaction();
        try {
            $arraySuccess = [];
            $arrayRowsFails = [];
            foreach ($resultProcess as $data) {
                $tnAgencia = $tnUser->getAgencia();
                $errores = [];

                //Validando que no esté registrada una remesa con esa referencia.
                $tnFacturaExiste = $this->entityManager->getRepository(TnTransferencia::class)->findOneBy(['referencia' => $data->getReferencia(), 'agencia' => $tnAgencia]);

                if (is_null($tnFacturaExiste)) {
                    $errores[] = [
                        'Referencia' => "No se ha registrado una transferencia con la referencia " . $data->getReferencia() . ", verifique."
                    ];
                }

                if (count($errores) > 0) {//Si esta transferencia tiene errores, la añado a erroes
                    $arrayRowsFails[] = [
                        'referencia' => $data->getReferencia(),
                        'errores' => $errores
                    ];
                } else {
                    if ($tnFacturaExiste instanceof TnTransferencia) {
                        $estado = $tnFacturaExiste->getEstado()->getNombre();
                        $fecha = $tnFacturaExiste->getCreated()->format('d/m/Y H:i:s');
                        if ($tnFacturaExiste->getEstado()->getCodigo() == NmEstadoTransferencia::ESTADO_ASINGADA) {
                            $fecha = $tnFacturaExiste->getAsignadaAt()->format('d/m/Y H:i:s');
                        } elseif ($tnFacturaExiste->getEstado()->getCodigo() == NmEstadoTransferencia::ESTADO_ENVIADO) {
                            $fecha = $tnFacturaExiste->getEnviadaAt()->format('d/m/Y H:i:s');
                        } elseif ($tnFacturaExiste->getEstado()->getCodigo() == NmEstadoTransferencia::ESTADO_CANCELADA) {
                            $fecha = $tnFacturaExiste->getCanceladaAt()->format('d/m/Y H:i:s');
                        }
                        $evidencia = '';
                        if ($tnFacturaExiste->getEvidencia()) {
                            $evidencia = 'https://www.telluspros.com/uploads/evidencias/' . $tnFacturaExiste->getEvidencia();
                        }
                        $arraySuccess[] = [
                            'referencia' => $data->getReferencia(),
                            'estado' => $estado,
                            'fecha' => $fecha,
                            'evidencia' => $evidencia,
                            'notas' => ($tnFacturaExiste->getNotas() != null) ? $tnFacturaExiste->getNotas() : ''
                        ];
                    }
                }
            }

            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();

            return ['success' => true, 'msg' => 'Actualización de las transferencias', 'correctas' => $arraySuccess, 'fallidas' => $arrayRowsFails];
        } catch
        (\Exception $e) {
            // Rollback the failed transaction attempt
            $this->entityManager->getConnection()->rollback();
            return ['success' => false, 'msg' => 'Ha ocurrido un error al verificar las transferencias.'];
        }
    }

    /**
     * Devuelve la actualización de todas las remesas que vengan en $data una vez validadas, actualización de estados.
     * @param $data
     */
    public function updateRemesas(TnUser $tnUser, $resultProcess)
    {
        $this->entityManager->getConnection()->beginTransaction();
        try {
            $arraySuccess = [];
            $arrayRowsFails = [];
            foreach ($resultProcess as $data) {
                $tnAgencia = $tnUser->getAgencia();
                $errores = [];

                //Validando que no esté registrada una remesa con esa referencia.
                $tnFacturaExiste = $this->entityManager->getRepository(TnFactura::class)->findOneBy(['referencia' => $data->getReferencia(), 'agencia' => $tnAgencia]);

                if (is_null($tnFacturaExiste)) {
                    $errores[] = [
                        'Referencia' => "No se ha registrado una factura con la referencia " . $data->getReferencia() . ", verifique."
                    ];
                }

                if (count($errores) > 0) {//Si esta transferencia tiene errores, la añado a erroes
                    $arrayRowsFails[] = [
                        'referencia' => $data->getReferencia(),
                        'errores' => $errores
                    ];
                } else {
                    if ($tnFacturaExiste instanceof TnFactura) {
                        $estado = $tnFacturaExiste->getEstado()->getNombre();
                        $fecha = $tnFacturaExiste->getCreated()->format('d/m/Y H:i:s');
                        if ($tnFacturaExiste->getEstado()->getCodigo() == NmEstado::ESTADO_APROBADA) {
                            $fecha = $tnFacturaExiste->getAprobadaAt()->format('d/m/Y H:i:s');
                        } elseif ($tnFacturaExiste->getEstado()->getCodigo() == NmEstado::ESTADO_DISTRIBUCION) {
                            $fecha = $tnFacturaExiste->getDistribuidaAt()->format('d/m/Y H:i:s');
                        } elseif ($tnFacturaExiste->getEstado()->getCodigo() == NmEstado::ESTADO_ENTREGADA) {
                            $fecha = $tnFacturaExiste->getUpdated()->format('d/m/Y H:i:s');
                        } elseif ($tnFacturaExiste->getEstado()->getCodigo() == NmEstado::ESTADO_CANCELADA) {
                            //Verifico si hay alguna remesa con refencia anterior, para devolver. los datos.
                            $tnFacturaReferencia = $this->entityManager->getRepository(TnFactura::class)->findOneBy(['referenciaOld' => $tnFacturaExiste->getNoFactura(), 'agencia' => $tnAgencia]);
                            if ($tnFacturaReferencia != null && $tnFacturaReferencia->getEstado()->getCodigo() != NmEstado::ESTADO_CANCELADA) {
                                $estado = $tnFacturaReferencia->getEstado()->getNombre();
                                if ($tnFacturaReferencia->getEstado()->getCodigo() == NmEstado::ESTADO_PENDIENTE) {
                                    $fecha = $tnFacturaReferencia->getCreated()->format('d/m/Y H:i:s');
                                } elseif ($tnFacturaReferencia->getEstado()->getCodigo() == NmEstado::ESTADO_APROBADA) {
                                    $fecha = $tnFacturaReferencia->getAprobadaAt()->format('d/m/Y H:i:s');
                                } elseif ($tnFacturaReferencia->getEstado()->getCodigo() == NmEstado::ESTADO_DISTRIBUCION) {
                                    $fecha = $tnFacturaReferencia->getDistribuidaAt()->format('d/m/Y H:i:s');
                                } elseif ($tnFacturaReferencia->getEstado()->getCodigo() == NmEstado::ESTADO_ENTREGADA) {
                                    $fecha = $tnFacturaReferencia->getUpdated()->format('d/m/Y H:i:s');
                                }
                            } else {
                                $fecha = $tnFacturaExiste->getCanceladaAt()->format('d/m/Y H:i:s');
                            }
                        }

                        $arraySuccess[] = [
                            'referencia' => $data->getReferencia(),
                            'estado' => $estado,
                            'fecha' => $fecha,
                            'evidencia' => '',
                            'notas' => ($tnFacturaExiste->getNotas() != null) ? $tnFacturaExiste->getNotas() : ''
                        ];
                    }
                }
            }

            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();

            return ['success' => true, 'msg' => 'Actualización de las facturas', 'correctas' => $arraySuccess, 'fallidas' => $arrayRowsFails];
        } catch
        (\Exception $e) {
            // Rollback the failed transaction attempt
            $this->entityManager->getConnection()->rollback();
            return ['success' => false, 'msg' => 'Ha ocurrido un error al verificar las facturas.'];
        }
    }


    public function validarDestinatario(TnUser $tnUser, TnDestinatario $tnDestinatario)
    {
        $params = [];
        $params['fi'] = new \DateTime(date("Y/m/d 00:00:00"));
        $params['ff'] = new \DateTime(date("Y/m/d 23:59:59"));

        if ($tnUser->getAgencia() != null) {
            $params['agencia'] = $tnUser->getAgencia()->getId();
        }

        if ($tnUser->getAgente() != null) {
            $params['agente'] = $tnUser->getAgente()->getId();
        }

        $result = $this->entityManager->getRepository(TnRemesa::class)->findRemesasDestinatarioCreateRemesa($params);

        foreach ($result as $remesa) {
            $nombre = $remesa->getDestinatario()->getNombre();
            $apellidos = $remesa->getDestinatario()->getApellidos();

            $nombreParam = $tnDestinatario->getNombre();
            $apellidosParam = $tnDestinatario->getApellidos();
            if ($nombre . " " . $apellidos == $nombreParam . " " . $apellidosParam || $remesa->getDestinatario()->getId() == $tnDestinatario->getId()) {
                return true;
            }
        }
        return false;
    }

    public function validarDestinatarioMonedaProvincia(TnDestinatario $tnDestinatario, NmMoneda $nmMoneda)
    {
        $municipio = $tnDestinatario->getMunicipio();
        if ($municipio != null) {
            foreach ($municipio->getMonedasEntrega() as $mon) {
                if ($mon->getSimbolo() == $nmMoneda->getSimbolo()) {
                    return true;
                }

            }
        }
        return false;
    }

    public function validarDestinatarioMaximo(TnUser $tnUser, TnDestinatario $tnDestinatario)
    {
        $params = [];
        $params['fi'] = new \DateTime(date("Y/m/d 00:00:00"));
        $params['ff'] = new \DateTime(date("Y/m/d 23:59:59"));

        if ($tnUser->getAgencia() != null) {
            $params['agencia'] = $tnUser->getAgencia()->getId();
        }

        if ($tnUser->getAgente() != null) {
            $params['agente'] = $tnUser->getAgente()->getId();
        }

        $nmMoneda = $this->entityManager->getRepository(NmMoneda::class)->findOneBy(['codigo' => NmMoneda::CURRENCY_USD]);
        if ($nmMoneda && $nmMoneda->getMaximo()) {
            $params['maximo'] = $nmMoneda->getMaximo();
        }

        $result = $this->entityManager->getRepository(TnRemesa::class)->findRemesasDestinatarioCreateRemesa($params);

        foreach ($result as $remesa) {
            $nombre = $remesa->getDestinatario()->getNombre();
            $apellidos = $remesa->getDestinatario()->getApellidos();

            $nombreParam = $tnDestinatario->getNombre();
            $apellidosParam = $tnDestinatario->getApellidos();
            if ($nombre . " " . $apellidos == $nombreParam . " " . $apellidosParam || $remesa->getDestinatario()->getId() == $tnDestinatario->getId()) {
                return true;
            }
        }
        return false;
    }

    public function validarDestinatarioAPI(TnUser $tnUser, $tnDestinatario)
    {
        $params = [];
        $params['fi'] = new \DateTime(date("Y/m/d 00:00:00"));
        $params['ff'] = new \DateTime(date("Y/m/d 23:59:59"));

        if ($tnUser->getAgencia() != null) {
            $params['agencia'] = $tnUser->getAgencia()->getId();
        }

        if ($tnUser->getAgente() != null) {
            $params['agente'] = $tnUser->getAgente()->getId();
        }

        $result = $this->entityManager->getRepository(TnRemesa::class)->findRemesasDestinatarioCreateRemesa($params);

        foreach ($result as $remesa) {
            $nombre = $remesa->getDestinatario()->getNombre();
            $apellidos = $remesa->getDestinatario()->getApellidos();

            if ($nombre . " " . $apellidos == $tnDestinatario) {
                return true;
            }
        }
        return false;
    }

    public function updateSaldoAgencia(TnAgencia $tnAgencia, NmMoneda $nmMoneda, $monto, $anterior = null)
    {
        $nmGrupoPago = $this->entityManager->getRepository(NmGrupoPago::class)->grupoPagoAgencia($tnAgencia, $nmMoneda);
        if (!is_null($nmGrupoPago)) {
            $saldoMoneda = $this->entityManager->getRepository(TnSaldoAgencia::class)->findOneBy(['agencia' => $tnAgencia, 'grupoPago' => $nmGrupoPago]);
            if (!is_null($saldoMoneda)) {
                $saldo = $saldoMoneda->getSaldo();
                if ($anterior != null) {
                    $saldo = $saldo + $anterior;
                }
                $saldo = $saldo - $monto;
                $saldoMoneda->setSaldo($saldo);

                $this->entityManager->persist($saldoMoneda);
            }
        }
    }

    public function restablecerSaldoAgencia(TnAgencia $tnAgencia, NmMoneda $nmMoneda, $monto)
    {
        $nmGrupoPago = $this->entityManager->getRepository(NmGrupoPago::class)->grupoPagoAgencia($tnAgencia, $nmMoneda);
        if (!is_null($nmGrupoPago)) {
            $saldoMoneda = $this->entityManager->getRepository(TnSaldoAgencia::class)->findOneBy(['agencia' => $tnAgencia, 'grupoPago' => $nmGrupoPago]);
            if (!is_null($saldoMoneda)) {
                $saldo = $saldoMoneda->getSaldo();
                $saldo = $saldo + $monto;
                $saldoMoneda->setSaldo($saldo);

                $this->entityManager->persist($saldoMoneda);
            }
        }
    }

    public function updateSaldoAgente(TnAgente $tnAgente, NmMoneda $nmMoneda, $monto, $anterior = null)
    {
        $nmGrupoPago = $this->entityManager->getRepository(NmGrupoPagoAgente::class)->grupoPagoAgente($tnAgente, $nmMoneda);
        if (!is_null($nmGrupoPago)) {
            $saldoMoneda = $this->entityManager->getRepository(TnSaldoAgente::class)->findOneBy(['agente' => $tnAgente, 'grupoPagoAgente' => $nmGrupoPago]);
            if (!is_null($saldoMoneda)) {
                $saldo = $saldoMoneda->getSaldo();
                if ($anterior != null) {
                    $saldo = $saldo + $anterior;
                }
                $saldo = $saldo - $monto;
                $saldoMoneda->setSaldo($saldo);

                $this->entityManager->persist($saldoMoneda);
            }
        }
    }

    public function restablecerSaldoAgente(TnAgente $tnAgente, NmMoneda $nmMoneda, $monto)
    {
        $nmGrupoPago = $this->entityManager->getRepository(NmGrupoPagoAgente::class)->grupoPagoAgente($tnAgente, $nmMoneda);
        if (!is_null($nmGrupoPago)) {
            $saldoMoneda = $this->entityManager->getRepository(TnSaldoAgente::class)->findOneBy(['agente' => $tnAgente, 'grupoPagoAgente' => $nmGrupoPago]);
            if (!is_null($saldoMoneda)) {
                $saldo = $saldoMoneda->getSaldo();
                $saldo = $saldo + $monto;
                $saldoMoneda->setSaldo($saldo);

                $this->entityManager->persist($saldoMoneda);
            }
        }
    }


    public function porcientoOperacionAgencia($tnAgencia, $moneda)
    {
        $nmGrupoPago = $this->entityManager->getRepository(NmGrupoPago::class)->grupoPagoAgencia($tnAgencia, $moneda);
        $porcentajeOperacion = $this->entityManager->getRepository(TnOperacionAgencia::class)->findOneBy(['agencia' => $tnAgencia, 'grupoPago' => $nmGrupoPago]);
        if (!is_null($porcentajeOperacion) && !is_null($porcentajeOperacion->getPorcentaje())) {
            return $porcentajeOperacion->getPorcentaje();
        }
        return null;
    }

    public function porcientoOperacionAgente($tnAgente, $moneda)
    {
        $nmGrupoPago = $this->entityManager->getRepository(NmGrupoPagoAgente::class)->grupoPagoAgente($tnAgente, $moneda);
        $porcentajeOperacion = $this->entityManager->getRepository(TnOperacionAgente::class)->findOneBy(['agente' => $tnAgente, 'grupoPagoAgente' => $nmGrupoPago]);
        if (!is_null($porcentajeOperacion) && !is_null($porcentajeOperacion->getPorcentaje())) {
            return $porcentajeOperacion->getPorcentaje();
        }
        return null;
    }

    public function porcientoOperacionAgenciaTransferencia($tnAgencia, $moneda)
    {
        $nmGrupoPago = $this->entityManager->getRepository(NmGrupoPagoTransf::class)->grupoPagoAgencia($tnAgencia, $moneda);
        $porcentajeOperacion = $this->entityManager->getRepository(TnOperacionAgenciaTransf::class)->findOneBy(['agencia' => $tnAgencia, 'grupoPago' => $nmGrupoPago]);
        if (!is_null($porcentajeOperacion) && !is_null($porcentajeOperacion->getPorcentaje())) {
            return $porcentajeOperacion->getPorcentaje();
        }
        return null;
    }

    public function porcientoOperacionAgenteTransferencia($tnAgente, $moneda)
    {
        $nmGrupoPago = $this->entityManager->getRepository(NmGrupoPagoTransfAgente::class)->grupoPagoAgente($tnAgente, $moneda);
        $porcentajeOperacion = $this->entityManager->getRepository(TnOperacionAgenteTransf::class)->findOneBy(['agente' => $tnAgente, 'grupoPago' => $nmGrupoPago]);
        if (!is_null($porcentajeOperacion) && !is_null($porcentajeOperacion->getPorcentaje())) {
            return $porcentajeOperacion->getPorcentaje();
        }
        return null;
    }
}