<?php

namespace App\Command;

use App\Entity\NmGrupoPago;
use App\Entity\NmGrupoPagoAgente;
use App\Entity\NmMoneda;
use App\Entity\NmMunicipio;
use App\Entity\NmProvincia;
use App\Entity\TnAgencia;
use App\Entity\TnAgente;
use App\Entity\TnCredito;
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

class UpdateContabilidadCommand extends ContainerAwareCommand
{
    protected static $defaultName = 'update:contabilidad';

    protected function configure()
    {
        $this
            ->setDescription('Actualiza los créditos iniciales de los distribuidores');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $entityManager = $this->getContainer()->get('doctrine')->getManager();

        $tnDistribuidores = $entityManager->getRepository(TnDistribuidor::class)->findBy(['enabled' => true]);
        $nmMonedas = $entityManager->getRepository(NmMoneda::class)->findBy(['aviable' => true]);

        foreach ($tnDistribuidores as $distribuidor) {
            foreach ($nmMonedas as $moneda) {
                $tnCredito = new TnCredito();
                $tnCredito->setMoneda($moneda);
                $tnCredito->setDistribuidor($distribuidor);
                $tnCredito->setCredito(0);

                $entityManager->persist($tnCredito);
            }

        }


        $entityManager->flush();

        $io->success('Créditos creados correctamente.' );

        return 0;
    }

}
