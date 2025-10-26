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

class UpdateTokenCommand extends ContainerAwareCommand
{
    protected static $defaultName = 'update:token';

    protected function configure()
    {
        $this
            ->setDescription('Actualiza los token de las entidades para aumentar seguridad');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $cont = 0;
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        //Agencias
        $agencias = $entityManager->getRepository(TnAgencia::class)->findAll();
        foreach ($agencias as $agencia) {
            $agencia->setToken((sha1(uniqid())));
            $entityManager->persist($agencia);
            $cont++;
        }
        //Agentes
        $agentes = $entityManager->getRepository(TnAgente::class)->findAll();
        foreach ($agentes as $agente) {
            $agente->setToken((sha1(uniqid())));
            $entityManager->persist($agente);
            $cont++;
        }
        //Emisores
        $emisores = $entityManager->getRepository(TnEmisor::class)->findAll();
        foreach ($emisores as $emisor) {
            $emisor->setToken((sha1(uniqid())));
            $entityManager->persist($emisor);
            $cont++;
        }

        //Destinatarios
        $destinatarios = $entityManager->getRepository(TnDestinatario::class)->findAll();
        foreach ($destinatarios as $destinatario) {
            $destinatario->setToken((sha1(uniqid())));
            $entityManager->persist($destinatario);
            $cont++;
        }

        //Distribuidores
        $distribuidores = $entityManager->getRepository(TnDistribuidor::class)->findAll();
        foreach ($distribuidores as $distribuidor) {
            $distribuidor->setToken((sha1(uniqid())));
            $entityManager->persist($distribuidor);
            $cont++;
        }
        //Grupos de pago
        $grupos = $entityManager->getRepository(NmGrupoPago::class)->findAll();
        foreach ($grupos as $grupo) {
            $grupo->setToken((sha1(uniqid())));
            $entityManager->persist($grupo);
            $cont++;
        }

        //Grupos de pago agente
        $gruposAgente = $entityManager->getRepository(NmGrupoPagoAgente::class)->findAll();
        foreach ($gruposAgente as $grupo) {
            $grupo->setToken((sha1(uniqid())));
            $entityManager->persist($grupo);
            $cont++;
        }

        //Usuarios
        $usuarios = $entityManager->getRepository(TnUser::class)->findAll();
        foreach ($usuarios as $usuario) {
            $usuario->setToken((sha1(uniqid())));
            $entityManager->persist($usuario);
            $cont++;
        }

        //Facturas
        $facturas = $entityManager->getRepository(TnFactura::class)->findAll();
        foreach ($facturas as $factura) {
            $factura->setToken((sha1(uniqid())));
            $entityManager->persist($factura);
            $cont++;
        }

        //Reportes de envio
        $reportes = $entityManager->getRepository(TnReporteEnvio::class)->findAll();
        foreach ($reportes as $reporte) {
            $reporte->setToken((sha1(uniqid())));
            $entityManager->persist($reporte);
            $cont++;
        }

        $entityManager->flush();

        $io->success('Token actualizados satisfactoriamente, total: ' . $cont);

        return 0;
    }

}
