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
use App\Entity\TnOperacionAgencia;
use App\Entity\TnOperacionAgenciaTransf;
use App\Entity\TnOperacionAgente;
use App\Entity\TnOperacionAgenteTransf;
use App\Entity\TnOperacionDist;
use App\Entity\TnReporteEnvio;
use App\Entity\TnSaldoAgencia;
use App\Entity\TnSaldoAgente;
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

class RegisterPorcientosTransfCommand extends ContainerAwareCommand
{
    protected static $defaultName = 'transfer:porcientos:register';

    protected function configure()
    {
        $this
            ->setDescription('Registra los porcientos de transferencias con que operan las agencias y los agentes.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $entityManager = $this->getContainer()->get('doctrine')->getManager();

//        $tnAgencias = $entityManager->getRepository(TnAgencia::class)->findBy(['enabled' => true]);//Agencias habilitadas
//
//        foreach ($tnAgencias as $tnAgencia) {
//            foreach ($tnAgencia->getGruposPagoTransferencias() as $grupoPago) {
//                $tnOperacionAgencia = new TnOperacionAgenciaTransf();
//                $tnOperacionAgencia->setAgencia($tnAgencia);
//                $tnOperacionAgencia->setGrupoPago($grupoPago);
//                $tnOperacionAgencia->setPorcentaje($tnAgencia->getUsuario() ? $tnAgencia->getUsuario()->getPorcentaje() : 0);
//                $entityManager->persist($tnOperacionAgencia);
//            }
//        }

        $tnAgentes = $entityManager->getRepository(TnAgente::class)->findBy(['enabled' => true]);//Agentes habilitadas

        foreach ($tnAgentes as $tnAgente) {
            foreach ($tnAgente->getGruposPagoTransferencias() as $grupoPago) {
                $tnOperacionAgente = new TnOperacionAgenteTransf();
                $tnOperacionAgente->setAgente($tnAgente);
                $tnOperacionAgente->setGrupoPago($grupoPago);
                $tnOperacionAgente->setPorcentaje($tnAgente->getUsuario() ? $tnAgente->getUsuario()->getPorcentaje() : 0);
                $entityManager->persist($tnOperacionAgente);
            }
        }

        $entityManager->flush();

        $io->success('Porcientos registrados satisfactoriamente.');

        return 0;
    }

}
