<?php

namespace App\Command;

use App\Entity\NmGrupoPago;
use App\Entity\NmGrupoPagoAgente;
use App\Entity\NmMoneda;
use App\Entity\NmMunicipio;
use App\Entity\NmProvincia;
use App\Entity\TnAgencia;
use App\Entity\TnAgente;
use App\Entity\TnCierreDistribuidor;
use App\Entity\TnCredito;
use App\Entity\TnDestinatario;
use App\Entity\TnDistribuidor;
use App\Entity\TnEmisor;
use App\Entity\TnFactura;
use App\Entity\TnHistorialDistribuidor;
use App\Entity\TnOperacionDist;
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

class RegisterCierreCommand extends ContainerAwareCommand
{
    protected static $defaultName = 'register:cierres';

    protected function configure()
    {
        $this
            ->setDescription('Registra los cierres diarios a los distribuidores.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $entityManager = $this->getContainer()->get('doctrine')->getManager();

        $distribuidores = $entityManager->getRepository(TnDistribuidor::class)->findBy(['enabled' => true]);//Distribuidores habilitados
        $monedas = $entityManager->getRepository(NmMoneda::class)->findBy(['enabled' => true]); // Monedas habilitadas y activadas
        $residuosCancelados = [];
        foreach ($monedas as $moneda) {
            foreach ($distribuidores as $distribuidor) {
                $tnCierre = new TnCierreDistribuidor();
                $tnCierre->setMoneda($moneda);
                $tnCierre->setDistribuidor($distribuidor);

                $credito = $entityManager->getRepository(TnCredito::class)->findOneBy(['moneda' => $moneda, 'distribuidor' => $distribuidor]);
                $inicio = $credito->getLastCierre(); //Busco las operaciones desde el último cierre
                $fin = new \DateTime('now'); //Hasta el momento

                $historiales = $entityManager->getRepository(TnHistorialDistribuidor::class)->findHistorialesDistribuidorParams($distribuidor, $moneda, $inicio, $fin);
                $totalRemesasEfectivas = 0;
                $totalComision = 0;
                $totalEnvios = 0;
                foreach ($historiales as $historial) {
                    if ($historial instanceof TnHistorialDistribuidor) {
                        if ($historial->getEstado() == TnHistorialDistribuidor::ESTADO_EFECTIVA) {
                            $totalRemesasEfectivas += $historial->getImporteRemesa();
                            $totalComision += $historial->getTasaDistrucion();
                            $totalEnvios++;
                        }
                        $historial->setCierre($tnCierre);
                        $entityManager->persist($historial);
                    }
                }
                $canceladas = [];
                $totalCanceladas = 0;
                //Buscando las canceladas en ese rango de fechas.
                $historialesCanceladas = $entityManager->getRepository(TnHistorialDistribuidor::class)->findHistorialesDistribuidorParamsCanceladas($distribuidor, $moneda, $inicio, $fin);
                foreach ($historialesCanceladas as $historial) {
                    if ($historial instanceof TnHistorialDistribuidor) {
                        if ($moneda->getComision()) {//Si es la moneda de la comisión, le sumo la cantidad que otras monedas que tiene ese distribuidor
                            $totalCanceladas += $historial->getImporteRemesa() + $historial->getTasaDistrucion();
                        } else {
                            $totalCanceladas += $historial->getImporteRemesa();
                            if (array_key_exists($distribuidor->getId(), $residuosCancelados)) {
                                $residuosCancelados[$distribuidor->getId()] = $residuosCancelados[$distribuidor->getId()] + $historial->getTasaDistrucion();
                            } else {
                                $residuosCancelados[$distribuidor->getId()] = $historial->getTasaDistrucion();
                            }
                        }
                        $canceladas[] = $historial->getRemesa()->getFactura()->getNoFactura();
                    }
                }
                $totalRecibido = $entityManager->getRepository(TnOperacionDist::class)->findOperacionesDistribuidorParams($distribuidor, $moneda, $inicio, $fin, TnOperacionDist::OPERACION_RECIBIDO);
                $totalTransferdido = $entityManager->getRepository(TnOperacionDist::class)->findOperacionesDistribuidorParams($distribuidor, $moneda, $inicio, $fin, TnOperacionDist::OPERACION_TRANSFERIDO);
                $totalGastos = $entityManager->getRepository(TnOperacionDist::class)->findOperacionesDistribuidorParams($distribuidor, $moneda, $inicio, $fin, TnOperacionDist::OPERACION_GASTOS);

                if ($moneda->getComision()) {//Si es la moneda de la comisión, le sumo la cantidad que otras monedas que tiene ese distribuidor
                    $totalComision = $totalComision + $distribuidor->getComision();
                    //Si es la moneda, reviso si hay residuos en esa moneda y se lo sumo a los cancelados
                    if (array_key_exists($distribuidor->getId(), $residuosCancelados)) {
                        $totalCanceladas += $residuosCancelados[$distribuidor->getId()];
                    }
                }
                //Registrando el cierre para esa moneda y ese distribuidor
                $tnCierre->setSaldoInicial($credito->getLastCredito() ? $credito->getLastCredito() : 0);
                $tnCierre->setRecibido($totalRecibido ? $totalRecibido : 0);
                $tnCierre->setEntregado($totalRemesasEfectivas);
                $tnCierre->setEnvios($totalEnvios);
                $tnCierre->setComision($totalComision);
                $tnCierre->setCancelado($totalCanceladas);
                $tnCierre->setTransferido($totalTransferdido ? $totalTransferdido : 0);
                $tnCierre->setGastos($totalGastos ? $totalGastos : 0);
                $tnCierre->setCredito($credito->getCredito());

                $entityManager->persist($tnCierre);
                //Seteando los valores al crédito
                $credito->setLastCredito($credito->getCredito());
                $credito->setLastCierre($fin);
                $entityManager->persist($credito);
                //Actualizando las comisiones a 0 del distribuidor
                $distribuidor->setComision(0);
                $entityManager->persist($distribuidor);
            }
        }

        $entityManager->flush();

        $io->success('Cierres registrados satisfactoriamente.');

        return 0;
    }

}
