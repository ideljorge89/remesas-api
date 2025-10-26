<?php

namespace App\Command;

use App\Entity\NmGrupoPago;
use App\Entity\NmGrupoPagoAgente;
use App\Entity\NmMunicipio;
use App\Entity\NmProvincia;
use App\Entity\TnAgencia;
use App\Entity\TnAgente;
use App\Entity\TnConfiguration;
use App\Entity\TnDestinatario;
use App\Entity\TnDistribuidor;
use App\Entity\TnEmisor;
use App\Entity\TnFactura;
use App\Entity\TnReporteEnvio;
use App\Entity\TnUser;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Parser;

class UpdateImporteCommand extends ContainerAwareCommand
{
    protected static $defaultName = 'update:importe';

    protected function configure()
    {
        $this
            ->setDescription('Actualiza los importes de las facturas con los nuevos datos.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $cont = 0;
        $entityManager = $this->getContainer()->get('doctrine')->getManager();

        $fecha = new \DateTime(date('2020/10/05 00:00:00'));
        $facturas = $entityManager->getRepository(TnFactura::class)->findFacturasGreatThan($fecha);

        foreach ($facturas as $tnFactura) {
            if ($tnFactura instanceof TnFactura) {
                $cantRemesas = $tnFactura->getRemesas()->count();
                //Verificando si es para de una agencia o un agente
                if ($tnFactura->getAgente() != null) {
                    $tnFactura->setAgencia($tnFactura->getAgente()->getAgencia());
                    if ($tnFactura->getAgente()->getGrupoPago()->getUtilidad()) {//Viendo el que grupo de pago está el agente
                        //Poniendo los valores con que se crea la factura
                        $porcientoAsig = $tnFactura->getAgente()->getGrupoPago()->getUtilidad();
                        $tnFactura->setPorcentajeAsignadoAgente($porcientoAsig);
                        $tnFactura->setPorcentajeOperaAgente($tnFactura->getAgente()->getUsuario()->getPorcentaje());
                        $tnFactura->setTipoPorcentajeAgente(TnFactura::TIPO_UTILIDAD);
                        //Calculo el porciento
                        $totalPagar = $tnFactura->getImporte() + $porcientoAsig * $cantRemesas;
                        $tnFactura->setTotalPagarAgente($totalPagar);
                        $tnFactura->setUtilidadFijaAgente(false);
                    } else {
                        //Poniendo los valores con que se crea la factura
                        $porcientoAsig = $tnFactura->getAgente()->getGrupoPago()->getPorcentaje();
                        $tnFactura->setPorcentajeAsignadoAgente($porcientoAsig);
                        $tnFactura->setPorcentajeOperaAgente($tnFactura->getAgente()->getUsuario()->getPorcentaje());
                        $tnFactura->setTipoPorcentajeAgente(TnFactura::TIPO_PORCIENTO);
                        //Calculo el porciento o utilidad fija
                        $minimo = $tnFactura->getAgente()->getGrupoPago()->getMinimo();
                        $utilidadFija = $tnFactura->getAgente()->getGrupoPago()->getUtilidadFija();
                        if ($minimo != null && !is_null($utilidadFija) && $tnFactura->getImporte() < $minimo) {
                            $tipoUtilidad = $tnFactura->getAgente()->getGrupoPago()->getTipoUtilidad();
                            if ($tipoUtilidad != "" && $tipoUtilidad == NmGrupoPago::TIPO_PORCIENTO) {
                                $totalPagar = $tnFactura->getImporte() + round((($tnFactura->getImporte() * $utilidadFija) / 100), 2);
                                $tnFactura->setPorcentajeAsignadoAgente($utilidadFija);
                                $tnFactura->setUtilidadFijaAgente(false);
                            } else {
                                $totalPagar = $tnFactura->getImporte() + $utilidadFija;
                                $tnFactura->setPorcentajeAsignadoAgente($utilidadFija);
                                $tnFactura->setUtilidadFijaAgente(true);
                            }
                        } elseif ($tnFactura->getImporte() < 100) {
                            $totalPagar = $tnFactura->getImporte() + round(((100 * $porcientoAsig) / 100), 2);
                            $tnFactura->setUtilidadFijaAgente(false);
                        } else {
                            $totalPagar = $tnFactura->getImporte() + round((($tnFactura->getImporte() * $porcientoAsig) / 100), 2);
                            $tnFactura->setUtilidadFijaAgente(false);
                        }

                        $tnFactura->setTotalPagarAgente($totalPagar);
                    }
                    if ($tnFactura->getAgente()->getAgencia() != null) {//Verifico si el agente es de una agencia y guardo sus datos
                        $porcientoAsig = $tnFactura->getAgente()->getAgencia()->getGrupoPago()->getPorcentaje();
                        $tnFactura->setPorcentajeAsignado($porcientoAsig);
                        $tnFactura->setPorcentajeOpera($tnFactura->getAgente()->getAgencia()->getUsuario() ? $tnFactura->getAgente()->getAgencia()->getUsuario()->getPorcentaje() : $porcientoAsig);
                        $tnFactura->setTipoPorcentaje(TnFactura::TIPO_PORCIENTO);
                        //Calculo el porciento o utilidad fija
                        $minimo = $tnFactura->getAgente()->getAgencia()->getGrupoPago()->getMinimo();
                        $utilidadFija = $tnFactura->getAgente()->getAgencia()->getGrupoPago()->getUtilidadFija();
                        if ($minimo != null && !is_null($utilidadFija) && $tnFactura->getImporte() < $minimo) {
                            $tipoUtilidad = $tnFactura->getAgente()->getAgencia()->getGrupoPago()->getTipoUtilidad();
                            if ($tipoUtilidad != "" && $tipoUtilidad == NmGrupoPago::TIPO_PORCIENTO) {
                                $totalPagar = $tnFactura->getImporte() + round((($tnFactura->getImporte() * $utilidadFija) / 100), 2);
                                $tnFactura->setUtilidadFija(false);
                                $tnFactura->setPorcentajeAsignado($utilidadFija);
                            } else {
                                $totalPagar = $tnFactura->getImporte() + $utilidadFija;
                                $tnFactura->setUtilidadFija(true);
                                $tnFactura->setPorcentajeAsignado($utilidadFija);
                            }
                        } elseif ($tnFactura->getImporte() < 100) {
                            $totalPagar = $tnFactura->getImporte() + round(((100 * $porcientoAsig) / 100), 2);
                            $tnFactura->setUtilidadFija(false);
                        } else {
                            $totalPagar = $tnFactura->getImporte() + round((($tnFactura->getImporte() * $porcientoAsig) / 100), 2);
                            $tnFactura->setUtilidadFija(false);
                        }
                        $tnFactura->setTotalPagar($totalPagar);
                    } else {
                        $tnFactura->setTotalPagar(0);
                        $tnFactura->setPorcentajeAsignado(0);
                        $tnFactura->setPorcentajeOpera(0);
                        $tnFactura->setTipoPorcentaje(TnFactura::TIPO_PORCIENTO);
                    }
                } elseif ($tnFactura->getAgencia() != null) {
                    //Poniendo los valores con que se crea la factura
                    $porcientoAsig = $tnFactura->getAgencia()->getGrupoPago()->getPorcentaje();
                    $tnFactura->setPorcentajeAsignado($porcientoAsig);
                    $tnFactura->setPorcentajeOpera($tnFactura->getAgencia()->getUsuario()->getPorcentaje());
                    $tnFactura->setTipoPorcentaje(TnFactura::TIPO_PORCIENTO);

                    //Calculo el porciento o utilidad fija
                    $minimo = $tnFactura->getAgencia()->getGrupoPago()->getMinimo();
                    $utilidadFija = $tnFactura->getAgencia()->getGrupoPago()->getUtilidadFija();

                    if ($minimo != null && !is_null($utilidadFija) && $tnFactura->getImporte() < $minimo) {
                        $tipoUtilidad = $tnFactura->getAgencia()->getGrupoPago()->getTipoUtilidad();
                        if ($tipoUtilidad != "" && $tipoUtilidad == NmGrupoPago::TIPO_PORCIENTO) {
                            $totalPagar = $tnFactura->getImporte() + round((($tnFactura->getImporte() * $utilidadFija) / 100), 2);
                            $tnFactura->setPorcentajeAsignado($utilidadFija);
                            $tnFactura->setUtilidadFija(false);
                        } else {
                            $totalPagar = $tnFactura->getImporte() + $utilidadFija;
                            $tnFactura->setPorcentajeAsignado($utilidadFija);
                            $tnFactura->setUtilidadFija(true);
                        }
                    } elseif ($tnFactura->getImporte() < 100) {
                        $totalPagar = $tnFactura->getImporte() + round(((100 * $porcientoAsig) / 100), 2);
                        $tnFactura->setUtilidadFija(false);
                    } else {
                        $totalPagar = $tnFactura->getImporte() + round((($tnFactura->getImporte() * $porcientoAsig) / 100), 2);
                        $tnFactura->setUtilidadFija(false);
                    }
                    $tnFactura->setTotalPagar($totalPagar);
                } else {
                    //Poniendo los valores con que se crea la factura
                    $porcientoAsig = $this->getContainer()->get('configuration')->get(TnConfiguration::PORCENTAJE);
                    $tnFactura->setPorcentajeAsignado($porcientoAsig);
                    $tnFactura->setPorcentajeOpera(5);
                    $tnFactura->setTipoPorcentaje(TnFactura::TIPO_PORCIENTO);
                    //Calculo el porciento
                    if ($porcientoAsig > 0) {
                        if ($tnFactura->getImporte() < 100) {
                            $totalPagar = $tnFactura->getImporte() + round(((100 * $porcientoAsig) / 100), 2);
                        } else {
                            $totalPagar = $tnFactura->getImporte() + round((($tnFactura->getImporte() * $porcientoAsig) / 100), 2);
                        }
                    } else {
                        $totalPagar = 0;
                    }
                    $tnFactura->setUtilidadFija(false);
                    $tnFactura->setUtilidadFijaAgente(false);
                    $tnFactura->setTotalPagar($totalPagar);
                }


                $entityManager->persist($tnFactura);
                $cont++;
            }

        }



        $entityManager->flush();

        $io->success('Facturas actualizados satisfactoriamente, total: ' . $cont);

        return 0;
    }

}
