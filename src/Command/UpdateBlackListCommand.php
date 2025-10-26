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

class UpdateBlackListCommand extends ContainerAwareCommand
{
    protected static $defaultName = 'update:black-list';

    protected function configure()
    {
        $this
            ->setDescription('Verifica si alguna factura es sospechosa y la marca como tal.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $cont = 0;
        $lastFactura = $this->getContainer()->get('configuration')->get(TnConfiguration::LAST_CHECK_FACTURA);

        $facturas = $entityManager->getRepository(TnFactura::class)->findToCheckData($lastFactura);

        foreach ($facturas as $tnFactura) {
            if ($tnFactura instanceof TnFactura) {
                $remesa = $tnFactura->getRemesas()[0];

                $params = [
                    'nombre' => $remesa->getDestinatario()->getNombre(),
                    'apellidos' => $remesa->getDestinatario()->getApellidos(),
                    'phone' => $remesa->getDestinatario()->getPhone(),
                    'ci' => $remesa->getDestinatario()->getCi() && $remesa->getDestinatario()->getCi() != '' ? $remesa->getDestinatario()->getCi() : 'No definido',
                    'direccion' => $remesa->getDestinatario()->getDireccion(),
                ];

                $listaNegra = $entityManager->getRepository(TnListaNegra::class)->findInBlackList($params);

                if (count($listaNegra) > 0) {
                    $tnFactura->setSospechosa(true);
                    $entityManager->persist($tnFactura);

                    $cont++;
                }
                $lastFactura = $tnFactura->getId();
            }
        }
        //Actualizando el Id de la última factura que comprobé para no volver a comprobar.
        $this->getContainer()->get('configuration')->set(TnConfiguration::LAST_CHECK_FACTURA, $lastFactura);

        $entityManager->flush();

        $io->success('Facturas encontradas sospechosas total: ' . $cont);

        return 0;
    }

}
