<?php

namespace App\Command;

use App\Entity\NmEstado;
use App\Entity\NmGrupoPago;
use App\Entity\NmGrupoPagoAgente;
use App\Entity\NmMoneda;
use App\Entity\NmMunicipio;
use App\Entity\NmProvincia;
use App\Entity\TnAgencia;
use App\Entity\TnAgente;
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

class UpdateCanceladasCommand extends ContainerAwareCommand
{
    protected static $defaultName = 'update:canceladas';

    protected function configure()
    {
        $this
            ->setDescription('Actualiza los facturas con datos incorrectos');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $cont = 0;
        $entityManager = $this->getContainer()->get('doctrine')->getManager();

        $nmCancelada = $entityManager->getRepository(NmEstado::class)->findOneBy(['codigo' => NmEstado::ESTADO_CANCELADA]);
        for ($i = 11; $i <= 99; $i++) {
            $factura = $entityManager->getRepository(TnFactura::class)->findOneBy(['no_factura' => '206' . $i]);
            if ($factura instanceof TnFactura && $factura->getEstado()->getCodigo() != NmEstado::ESTADO_CANCELADA) {
                $factura->setEstado($nmCancelada);
                $entityManager->persist($factura);
                $cont++;
            }
        }


        $entityManager->flush();

        $io->success('Facturas actualizados satisfactoriamente, total: ' . $cont);

        return 0;
    }

}
