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

class RegisterSaldosCommand extends ContainerAwareCommand
{
    protected static $defaultName = 'register:saldos';

    protected function configure()
    {
        $this
            ->setDescription('Registra los saldos iniciales de las agencias según los grupos de pago.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $entityManager = $this->getContainer()->get('doctrine')->getManager();

        $tnAgencias = $entityManager->getRepository(TnAgencia::class)->findBy(['enabled' => true]);//Agencias habilitadas

        foreach ($tnAgencias as $tnAgencia) {
            foreach ($tnAgencia->getGruposPago() as $grupoPago) {
                $tnSaldoAgencia = new TnSaldoAgencia();
                $tnSaldoAgencia->setAgencia($tnAgencia);
                $tnSaldoAgencia->setGrupoPago($grupoPago);
                $tnSaldoAgencia->setSaldo(0.0);
                $entityManager->persist($tnSaldoAgencia);
            }
        }

        $tnAgentes = $entityManager->getRepository(TnAgente::class)->findBy(['enabled' => true]);//Agentes habilitadas

        foreach ($tnAgentes as $tnAgente) {
            foreach ($tnAgente->getGruposPago() as $grupoPago) {
                $tnSaldoAgente = new TnSaldoAgente();
                $tnSaldoAgente->setAgente($tnAgente);
                $tnSaldoAgente->setGrupoPagoAgente($grupoPago);
                $tnSaldoAgente->setSaldo(0.0);
                $entityManager->persist($tnSaldoAgente);
            }
        }

        $entityManager->flush();

        $io->success('Saldos registrados satisfactoriamente.');

        return 0;
    }

}
