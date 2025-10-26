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

class UpdateFacturaCommand extends ContainerAwareCommand
{
    protected static $defaultName = 'update:facturas';

    protected function configure()
    {
        $this
            ->setDescription('Actualiza los facturas con datos incorrectos');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $cont = 0;

        $params = [
            'fi' => new \DateTime(date('2021/10/18 00:00:00')),
            'ff' => new \DateTime(date('2021/10/18 23:59:59')),
            'agencias' => [],
            'agentes' => [],
            'monedas' => [NmMoneda::CURRENCY_CUP],
            'facturas' => [],
            'grupoPago' => [13]
        ];

        $facturas = $entityManager->getRepository(TnFactura::class)->findFacturasCuadreAgencia($params);

        foreach ($facturas as $tnFactura) {
            if ($tnFactura instanceof TnFactura) {
//                $tnFactura->setEmisor($tnEmisor);
//                $tnFactura->setAgencia($tnAgencia);
                $dataPorcentajes = [
                    NmMoneda::CURRENCY_CUP => -10
                ];

                $dataTasaCambio = [
                    NmMoneda::CURRENCY_CUP => 50
                ];

                //Obteniendo la remesa de la factura y sus datos.
                $tnRemesaFactura = $tnFactura->getRemesas()[0];
                //Guardando la moneda y la tasa
                $monedaRemesa = $tnRemesaFactura->getMoneda();
                $tasa = $dataTasaCambio[$tnFactura->getMoneda()];//Obteniendo la tasa de la moneda.
                $importe = $tnFactura->getImporte();

                $importeTasa = round(($importe / $tasa), 2); //Importe por el que se debe calcular los porcientos y demás.

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

        $entityManager->flush();

        $io->success('Facturas actualizados satisfactoriamente, total: ' . $cont);

        return 0;
    }

}
