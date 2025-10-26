<?php

namespace App\Command;

use App\Entity\NmEstado;
use App\Entity\TnConfiguration;
use App\Entity\TnDistribuidor;
use App\Entity\TnFactura;
use App\Entity\TnRemesa;
use App\Entity\TnReporteEnvio;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Parser;

class AprobarFacturasCommand extends ContainerAwareCommand
{
    protected static $defaultName = 'aprobar:facturas';

    protected function configure()
    {
        $this
            ->setDescription('Aprueba las facturas pendientes y crea los reportes de envío.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        date_default_timezone_set('America/Havana');//Seteando el uso horario para correr la tarea y que la hora salga normal

        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $cont = 0;

        $facturas = $entityManager->getRepository(TnFactura::class)->getFacturasPendienteAprobacion();

        $estadoAprobada = $entityManager->getRepository(NmEstado::class)->findOneBy(array('codigo' => NmEstado::ESTADO_APROBADA));
        $estadoDistribucion = $entityManager->getRepository(NmEstado::class)->findOneBy(array('codigo' => NmEstado::ESTADO_DISTRIBUCION));

        $entityManager->getConnection()->beginTransaction();
        try {
            foreach ($facturas as $factura) {
                $count = 0;
                foreach ($factura->getRemesas() as $remesa) {
                    if ($remesa->getDistribuidor() != null) {
                        $count++;
                        //Creando el historial de distribución de la remesa
                        $this->getContainer()->get('factura_manager')->crearHistorialDistribucion($remesa);
                    }
                }
                if (count($factura->getRemesas()) == $count) {
                    $factura->setEstado($estadoDistribucion);
                } else {
                    $factura->setEstado($estadoAprobada);
                }
                $cont++;

                $entityManager->persist($factura);
            }
            $entityManager->flush();

            //Ya aquí aprobé todas las remmesas que estaban pendientes, ahora vamos a los reportes de Envío.
            $tnDistribuidores = $entityManager->getRepository(TnDistribuidor::class)->findBy(['enabled' => true]);
            foreach ($tnDistribuidores as $tnDistribuidor) {
                $reporte = $entityManager->getRepository(TnReporteEnvio::class)->findLastReporteEnvio($tnDistribuidor);

                if ($reporte instanceof TnReporteEnvio) {
                    $remesas = $entityManager->getRepository(TnRemesa::class)->getRemesasPendientesEnvioDistribuidor($tnDistribuidor, $reporte);
                } else {
                    $remesas = $entityManager->getRepository(TnRemesa::class)->getRemesasPendientesEnvioDistribuidor($tnDistribuidor);
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
                    $tnReporteEnvio->setDistribuidor($tnDistribuidor);
                    $tnReporteEnvio->setImporte($importe);
                    $tnReporteEnvio->setToken((sha1(uniqid())));
                    $entityManager->persist($tnReporteEnvio);

                    foreach ($remesas as $remesa) {
                        $remesa->setReporteEnvio($tnReporteEnvio);
                        $entityManager->persist($remesa);
                    }
                    $entityManager->flush();
                }
            }

            $entityManager->getConnection()->commit();

            $this->getContainer()->get('configuration')->set(TnConfiguration::STATUS_NEW_FACTURA, 'DESABILITADO');

            $io->success('Remesas aprobadas y enviadas satisfactoriamente, total: ' . $cont);

        } catch (\Exception $e) {
            // Rollback the failed transaction attempt
            $entityManager->getConnection()->rollback();
            $io->error('Ha ocurrido un error al aprobar las remesas.');
        }

        return 0;
    }

}
