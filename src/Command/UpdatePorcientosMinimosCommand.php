<?php

namespace App\Command;

use App\Entity\NmGrupoPago;
use App\Entity\NmGrupoPagoAgente;
use App\Entity\NmMoneda;
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

class UpdatePorcientosMinimosCommand extends ContainerAwareCommand
{
    protected static $defaultName = 'update:minimos';

    protected function configure()
    {
        $this
            ->setDescription('Actualiza los procientos para las remesas menores de 100.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $cont = 0;
        $entityManager = $this->getContainer()->get('doctrine')->getManager();

        $dataLoadEspecial = [];
        $dataLoadEspecial[] = [//del 25/11 al  30/11, dario tenia el -12 cup, -12 cuc y 12
            'dates' => [
                'inicio' => new \DateTime(date('2020/11/25 00:00:00')),
                'fin' => new \DateTime(date('2020/11/30 23:59:59'))
            ],
            'data' => [
                NmMoneda::CURRENCY_CUC => -12,
                NmMoneda::CURRENCY_CUP => -12,
                NmMoneda::CURRENCY_USD => 12
            ]
        ];

        $dataLoadEspecial[] = [//del 01/12 al  06/12, dario tenia el -12 cup, -12 cuc y 12
            'dates' => [
                'inicio' => new \DateTime(date('2020/12/01 00:00:00')),
                'fin' => new \DateTime(date('2020/12/06 23:59:59'))
            ],
            'data' => [
                NmMoneda::CURRENCY_CUC => -15,
                NmMoneda::CURRENCY_CUP => -15,
                NmMoneda::CURRENCY_USD => 12
            ]
        ];

        $dataLoadAgencias = [];
        $dataLoadAgencias[] = [//del 01/12 al  06/12, dario tenia el -12 cup, -12 cuc y 12
            'dates' => [
                'inicio' => new \DateTime(date('2020/12/01 00:00:00')),
                'fin' => new \DateTime(date('2020/12/06 23:59:59'))
            ],
            'data' => [
                NmMoneda::CURRENCY_CUC => -15,
                NmMoneda::CURRENCY_CUP => -15,
                NmMoneda::CURRENCY_USD => 13
            ]
        ];

        $tnAgencias = $entityManager->getRepository(TnAgencia::class)->findBy(['enabled' => true]);

        foreach ($tnAgencias as $tnAgencia) {
            $loadData = null;
            if ($tnAgencia->getId() == 49 || $tnAgencia->getId() == 50) {
                $loadData = $dataLoadEspecial;
            } else {
                $loadData = $dataLoadAgencias;
            }
            foreach ($loadData as $data) {
                //Obteniendo los agentes también para buscar todas las remesas
                $tempAgentes = [];
                foreach ($tnAgencia->getAgentes() as $agente) {
                    $tempAgentes[] = $agente->getId();
                }
                $params = [
                    'fi' => $data['dates']['inicio'],
                    'ff' => $data['dates']['fin'],
                    'agencias' => [$tnAgencia->getId()],
                    'agentes' => $tempAgentes
                ];
                $dataPorcentajes = $data['data'];
                $tnFacturas = $entityManager->getRepository(TnFactura::class)->findFacturasApoderadoParams($params);
                foreach ($tnFacturas as $tnFactura) {
                    if ($tnFactura instanceof TnFactura) {
                        //Obteniendo la remesa de la factura y sus datos.
                        $tnRemesaFactura = $tnFactura->getRemesas()[0];
                        //Guardando la moneda y la tasa
                        $monedaRemesa = $tnRemesaFactura->getMoneda();
                        $tasa = $monedaRemesa->getTasaCambio();
                        $importe = $tnFactura->getImporte();

                        $importeTasa = round($importe / $tasa); //Importe por el que se debe calcular los porcientos y demás.

                        if ($importeTasa < 100) { //Solo para las remesas que tengan valores menores que 100 actualizar el costo.
                            //Poniendo los valores con que se crea la factura
                            $porcientoAsig = $dataPorcentajes[$monedaRemesa->getSimbolo()];
                            $tnFactura->setPorcentajeAsignado($porcientoAsig);
                            $tnFactura->setPorcentajeOpera($tnFactura->getAgencia()->getUsuario()->getPorcentaje());
                            $tnFactura->setTipoPorcentaje(TnFactura::TIPO_PORCIENTO);

                            $totalPagar = $importeTasa + round((($importeTasa * $porcientoAsig) / 100), 2);
                            $tnFactura->setUtilidadFija(false);

                            $tnFactura->setTotalPagar($totalPagar);

                            $entityManager->persist($tnFactura);
                            $cont++;
                        }
                    }

                }
            }

        }

        $entityManager->flush();

        $io->success('Facturas actualizadas satisfactoriamente, total: ' . $cont);

        return 0;
    }
}
