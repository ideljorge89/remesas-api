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
use App\Entity\TnConfiguration;
use App\Entity\TnDestinatario;
use App\Entity\TnDistribuidor;
use App\Entity\TnEmisor;
use App\Entity\TnFactura;
use App\Entity\TnListaNegra;
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

class CheckFacturaStatusCommand extends ContainerAwareCommand
{
    protected static $defaultName = 'check:factura-status';

    protected function configure()
    {
        $this
            ->setDescription('Verifica el estado de nuevas facturas, habilita o desabilita el estado.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $state = $this->getContainer()->get('configuration')->get(TnConfiguration::STATUS_NEW_FACTURA);

       if($state == 'HABILITADO'){
           $this->getContainer()->get('configuration')->set(TnConfiguration::STATUS_NEW_FACTURA, 'DESABILITADO');
       }else{
           $this->getContainer()->get('configuration')->set(TnConfiguration::STATUS_NEW_FACTURA, 'HABILITADO');
       }
        //Actualizando el Id de la última factura que comprobé para no volver a comprobar.

        $this->getContainer()->get('doctrine')->getManager()->flush();

        $io->success('ESTADO ACTUALIZADO CORRECTAMENTE');

        return 0;
    }

}
