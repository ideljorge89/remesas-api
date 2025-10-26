<?php

namespace App\Command;

use App\Entity\NmGrupoPago;
use App\Entity\NmGrupoPagoAgente;
use App\Entity\NmMunicipio;
use App\Entity\NmProvincia;
use App\Entity\TnAgencia;
use App\Entity\TnAgente;
use App\Entity\TnApoderado;
use App\Entity\TnApoderadoFactura;
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

class UpdateApoderadosCommand extends ContainerAwareCommand
{
    protected static $defaultName = 'update:apoderados';

    protected function configure()
    {
        $this
            ->setDescription('Actualiza el histórico de un apoderado.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $cont = 0;
        $entityManager = $this->getContainer()->get('doctrine')->getManager();

        $dataLoadQuickMoney = [];
        $dataLoadQuickMoney[] = [//del 1/10/ al  3/10, dario tenia el -4% y menos de 100 a 1x1
            'dates' => [
                'inicio' => new \DateTime(date('2020/10/01 00:00:00')),
                'fin' => new \DateTime(date('2020/10/03 23:59:59'))
            ],
            'data' => [
                'normal' => -4,
                'fijo' => 0
            ]
        ];
        $dataLoadQuickMoney[] = [//del 4/10 al  18/10, dario tenia el -6% y menos de 100 a -2%
            'dates' => [
                'inicio' => new \DateTime(date('2020/10/04 00:00:00')),
                'fin' => new \DateTime(date('2020/10/18 23:59:59'))
            ],
            'data' => [
                'normal' => -6,
                'porciento' => -2
            ]
        ];
        $dataLoadQuickMoney[] = [//del 19/10 al  31/10, dario tenia el -8% y menos de 100 a -4%
            'dates' => [
                'inicio' => new \DateTime(date('2020/10/19 00:00:00')),
                'fin' => new \DateTime(date('2020/10/31 23:59:59'))
            ],
            'data' => [
                'normal' => -8,
                'porciento' => -4
            ]
        ];
        $dataLoadQuickMoney[] = [//del 1/11 al  10/11, dario tenia el -10% y menos de 100 a -5%
            'dates' => [
                'inicio' => new \DateTime(date('2020/11/01 00:00:00')),
                'fin' => new \DateTime(date('2020/11/10 23:59:59'))
            ],
            'data' => [
                'normal' => -10,
                'porciento' => -5
            ]
        ];

        $dataLoadAgencias = [];
        $dataLoadAgencias[] = [//del 1/10/ al  3/10, Quickmoney tenia el 3% y menos de 100 a 3 fijo,
            'agencias' => [3, 4, 6, 8, 11, 39],
            'agentes' => [7],
            'dates' => [
                'inicio' => new \DateTime(date('2020/10/01 00:00:00')),
                'fin' => new \DateTime(date('2020/10/03 23:59:59'))
            ],
            'data' => [
                'normal' => 3,
                'fijo' => 3
            ]
        ];
        $dataLoadAgencias[] = [//del 4/10 al  10/11, Quickmoneytenia el 1x1 y menos de 100 a 3 fijo,
            'agencias' => [3, 4, 6, 8, 11, 39],
            'agentes' => [7],
            'dates' => [
                'inicio' => new \DateTime(date('2020/10/04 00:00:00')),
                'fin' => new \DateTime(date('2020/11/10 23:59:59'))
            ],
            'data' => [
                'normal' => 0,
                'fijo' => 3
            ]
        ];
        $dataLoadAgencias[] = [//las palmas si estaban a 3% desde el  1/10/ al  31/10y menos de 100 3 fijo
            'agencias' => [7],
            'agentes' => [],
            'dates' => [
                'inicio' => new \DateTime(date('2020/10/01 00:00:00')),
                'fin' => new \DateTime(date('2020/10/31 23:59:59'))
            ],
            'data' => [
                'normal' => 3,
                'fijo' => 3
            ]
        ];
        $dataLoadAgencias[] = [//las palmas si estaban a 1x1 desde el  1/11/ al  10/11 y menos de 100 3 fijo
            'agencias' => [7],
            'agentes' => [],
            'dates' => [
                'inicio' => new \DateTime(date('2020/11/01 00:00:00')),
                'fin' => new \DateTime(date('2020/11/10 23:59:59'))
            ],
            'data' => [
                'normal' => 0,
                'fijo' => 3
            ]
        ];

        $tnApoderado = $entityManager->getRepository(TnApoderado::class)->findOneBy(['nombre' => 'Pavel']);
        if ($tnApoderado instanceof TnApoderado) {
            $agenciaRepresenta = $tnApoderado->getAgencia();
            $agencias = [];
            foreach ($tnApoderado->getSubordinadas() as $agencia) {
                $agencias[] = $agencia->getId();
            }
            $params = [
                'fi' => new \DateTime(date('2020/10/01 00:00:00')),
                'ff' => new \DateTime(date('2020/11/10 23:59:59')),
                'agencias' => $agencias
            ];
            //Facturas
            $tnFacturas = $entityManager->getRepository(TnFactura::class)->findFacturasApoderadoParams($params);
            foreach ($tnFacturas as $tnFactura) {
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
                $grupoPagoAgencia = $this->dataLoadQuickMoney($dataLoadQuickMoney, $tnFactura);

                $porcientoAsig = $grupoPagoAgencia['normal'];
                $tnApoderadoFactura->setPorcentajeAsignado($porcientoAsig);

                if ($importeTasa < 100) {
                    if (isset($grupoPagoAgencia['porciento'])) {
                        $totalPagar = $importeTasa + round((($importeTasa * $grupoPagoAgencia['porciento']) / 100), 2);
                        $tnApoderadoFactura->setPorcentajeAsignado($grupoPagoAgencia['porciento']);
                        $tnApoderadoFactura->setUtilidadFija(false);
                    } else {
                        $totalPagar = $importeTasa + $grupoPagoAgencia['fijo'];
                        $tnApoderadoFactura->setPorcentajeAsignado($grupoPagoAgencia['fijo']);
                        $tnApoderadoFactura->setUtilidadFija(true);
                    }
                } else {
                    $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);
                    $tnApoderadoFactura->setUtilidadFija(false);
                }
                $tnApoderadoFactura->setTotalPagar($totalPagar);

                if (!is_null($tnFactura->getAgente())) {//Si la remesa es de un agente que tiene agencia con apoderado es el proceder diferente
                    if ($tnFactura->getAgente()->getAgencia()->getId() != $agenciaRepresenta->getId()) {//Si es la misma agencia, se le cobra al agente
                        //Datos de la agencia Subordinada.
                        $grupoPagoAgenciaSub = $this->dataLoadAgencies($dataLoadAgencias, $tnFactura, $tnFactura->getAgente()->getAgencia());

                        $porcientoAsigSub = $grupoPagoAgenciaSub['normal'];
                        $tnApoderadoFactura->setPorcentajeAsignadoSubordinada($porcientoAsigSub);

                        //Calculo el porciento o utilidad fija
                        if ($importeTasa < 100) {
                            if (isset($grupoPagoAgenciaSub['porciento'])) {
                                $totalPagarSub = $importeTasa + round((($importeTasa * $grupoPagoAgenciaSub['porciento']) / 100), 2);
                                $tnApoderadoFactura->setPorcentajeAsignadoSubordinada($grupoPagoAgenciaSub['porciento']);
                                $tnApoderadoFactura->setUtilidadFijaFijaSubordinada(false);
                            } else {
                                $totalPagarSub = $importeTasa + $grupoPagoAgenciaSub['fijo'];
                                $tnApoderadoFactura->setPorcentajeAsignadoSubordinada($grupoPagoAgenciaSub['fijo']);
                                $tnApoderadoFactura->setUtilidadFijaFijaSubordinada(true);
                            }
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
                        $grupoPagoAgente = $this->dataLoadAgentes($dataLoadAgencias, $tnFactura, $tnFactura->getAgente());

                        $porcientoAsigSub = $grupoPagoAgente['normal'];
                        $tnApoderadoFactura->setPorcentajeAsignadoSubordinada($porcientoAsigSub);

                        //Calculo el porciento o utilidad fija
                        if ($importeTasa < 100) {
                            if (isset($grupoPagoAgente['porciento'])) {
                                $totalPagarSub = $importeTasa + round((($importeTasa * $grupoPagoAgente['porciento']) / 100), 2);
                                $tnApoderadoFactura->setPorcentajeAsignadoSubordinada($grupoPagoAgente['porciento']);
                                $tnApoderadoFactura->setUtilidadFijaFijaSubordinada(false);
                            } else {
                                $totalPagarSub = $importeTasa + $grupoPagoAgente['fijo'];
                                $tnApoderadoFactura->setPorcentajeAsignadoSubordinada($grupoPagoAgente['fijo']);
                                $tnApoderadoFactura->setUtilidadFijaFijaSubordinada(true);
                            }
                        } else {
                            $totalPagarSub = $importeTasa + round((($importeTasa * $porcientoAsigSub) / 100), 2);
                            $tnApoderadoFactura->setUtilidadFijaFijaSubordinada(false);
                        }
                        $tnApoderadoFactura->setTotalPagarSubordinada($totalPagarSub);
                        $tnApoderadoFactura->setUtilidad($totalPagarSub - $totalPagar);
                        $tnApoderadoFactura->setTipoPorcentaje(TnFactura::TIPO_PORCIENTO);
                        $tnApoderadoFactura->setAgencia($tnFactura->getAgente()->getAgencia());
                    }
                } else {
                    if ($tnFactura->getAgencia()->getId() != $agenciaRepresenta->getId()) {//Si es la misma agencia, se le cobra al agente
                        //Datos de la agencia Subordinada.
                        $grupoPagoAgenciaSub = $this->dataLoadAgencies($dataLoadAgencias, $tnFactura, $tnFactura->getAgencia());

                        $porcientoAsigSub = $grupoPagoAgenciaSub['normal'];
                        $tnApoderadoFactura->setPorcentajeAsignadoSubordinada($porcientoAsigSub);

                        //Calculo el porciento o utilidad fija
                        if ($importeTasa < 100) {
                            if (isset($grupoPagoAgenciaSub['porciento'])) {
                                $totalPagarSub = $importeTasa + round((($importeTasa * $grupoPagoAgenciaSub['porciento']) / 100), 2);
                                $tnApoderadoFactura->setPorcentajeAsignadoSubordinada($grupoPagoAgenciaSub['porciento']);
                                $tnApoderadoFactura->setUtilidadFijaFijaSubordinada(false);
                            } else {
                                $totalPagarSub = $importeTasa + $grupoPagoAgenciaSub['fijo'];
                                $tnApoderadoFactura->setPorcentajeAsignadoSubordinada($grupoPagoAgenciaSub['fijo']);
                                $tnApoderadoFactura->setUtilidadFijaFijaSubordinada(true);
                            }
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
                        $porcientoAsigSub = 5;
                        $tnApoderadoFactura->setPorcentajeAsignadoSubordinada($porcientoAsigSub);

                        $tnApoderadoFactura->setTotalPagarSubordinada($tnFactura->getTotal());
                        $tnApoderadoFactura->setUtilidadFijaFijaSubordinada(false);
                        $tnApoderadoFactura->setUtilidad($tnFactura->getTotal() - $totalPagar);
                        $tnApoderadoFactura->setTipoPorcentaje(TnFactura::TIPO_PORCIENTO);
                        $tnApoderadoFactura->setAgencia($tnFactura->getAgencia());
                    }
                }
                $cont++;
                $entityManager->persist($tnApoderadoFactura);
            }
        }

        $entityManager->flush();

        $io->success('Histórico actualizado satisfactoriamente, total: ' . $cont);

        return 0;
    }


    private function dataLoadQuickMoney($dataLoadQuickMoney, TnFactura $tnFactura)
    {
        foreach ($dataLoadQuickMoney as $data) {
            if ($tnFactura->getFechaContable() >= $data['dates']['inicio'] && $tnFactura->getFechaContable() <= $data['dates']['fin']) {
                return $data['data'];
            }
        }

        return [];
    }

    private function dataLoadAgencies($dataLoadAgencias, TnFactura $tnFactura, TnAgencia $tnAgencia)
    {
        foreach ($dataLoadAgencias as $data) {
            if ($tnFactura->getFechaContable() >= $data['dates']['inicio'] && $tnFactura->getFechaContable() <= $data['dates']['fin'] && in_array($tnAgencia->getId(), $data['agencias'])) {
                return $data['data'];
            }
        }

        return [];
    }

    private function dataLoadAgentes($dataLoadAgencias, TnFactura $tnFactura, TnAgente $tnAgente)
    {
        foreach ($dataLoadAgencias as $data) {
            if ($tnFactura->getFechaContable() >= $data['dates']['inicio'] && $tnFactura->getFechaContable() <= $data['dates']['fin'] && in_array($tnAgente->getId(), $data['agentes'])) {
                return $data['data'];
            }
        }

        return [];
    }

}
