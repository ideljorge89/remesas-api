<?php

namespace App\Command;

use App\Entity\NmGrupoPago;
use App\Entity\NmGrupoPagoAgente;
use App\Entity\NmMunicipio;
use App\Entity\NmProvincia;
use App\Entity\TnAgencia;
use App\Entity\TnAgente;
use App\Entity\TnDestinatario;
use App\Entity\TnDistribuidor;
use App\Entity\TnEmisor;
use App\Entity\TnFactura;
use App\Entity\TnRemesa;
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

class UpdateContableCommand extends ContainerAwareCommand
{
    protected static $defaultName = 'update:contable';

    protected function configure()
    {
        $this
            ->setDescription('Actualiza la fecha contable de todas las facturas en el sistema');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $cont = 0;
        $entityManager = $this->getContainer()->get('doctrine')->getManager();

        //Facturas
        $facturas = $entityManager->getRepository(TnFactura::class)->findAll();
        foreach ($facturas as $factura) {
            if ($factura instanceof TnFactura) {
                $factura->setFechaContable($factura->getCreated());
                $entityManager->persist($factura);
                $cont++;
            }
        }

        $entityManager->flush();

        $io->success('Facturas actualizadas satisfactoriamente, total: ' . $cont);

        return 0;
    }

}
