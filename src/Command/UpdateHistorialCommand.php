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
use App\Entity\TnHistorialDistribuidor;
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

class UpdateHistorialCommand extends ContainerAwareCommand
{
    protected static $defaultName = 'update:historial';

    protected function configure()
    {
        $this
            ->setDescription('Actualiza el historial de los distribuidores.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $cont = 0;
        $entityManager = $this->getContainer()->get('doctrine')->getManager();

        //Historiales
        $historiales = $entityManager->getRepository(TnHistorialDistribuidor::class)->findAll();
        foreach ($historiales as $historial) {
            if ($historial instanceof TnHistorialDistribuidor) {
                $historial->setImporteEntregado($historial->getImporteRemesa());
                $historial->setMoneda("CUC");
                $historial->setTasa(1);

                $entityManager->persist($historial);
                $cont++;
            }
        }

        $entityManager->flush();

        $io->success('Historiales actualizados satisfactoriamente, total: ' . $cont);

        return 0;
    }

}
