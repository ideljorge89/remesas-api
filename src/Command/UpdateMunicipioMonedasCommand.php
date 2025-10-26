<?php

namespace App\Command;

use App\Entity\NmMoneda;
use App\Entity\NmMunicipio;
use App\Entity\NmProvincia;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Parser;

class UpdateMunicipioMonedasCommand extends ContainerAwareCommand
{
    protected static $defaultName = 'update:moneda:entrega';

    protected function configure()
    {
        $this
            ->setDescription('Actualiza las monedas de entrega en los municipios.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $cont = 0;
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $municipios = $entityManager->getRepository(NmMunicipio::class)->findAll();
        $nmMonedas = $entityManager->getRepository(NmMoneda::class)->findBy(['enabled' => true, 'aviable' => true]);

        $euro = $entityManager->getRepository(NmMoneda::class)->findOneBy(['simbolo' => NmMoneda::CURRENCY_EUR]);
//        $usd = $entityManager->getRepository(NmMoneda::class)->findOneBy(['simbolo' => NmMoneda::CURRENCY_USD]);
//        $cup = $entityManager->getRepository(NmMoneda::class)->findOneBy(['simbolo' => NmMoneda::CURRENCY_CUP]);

        $provincias = ['La Habana'];
        foreach ($municipios as $municipio) {//
            if ($municipio instanceof NmMunicipio && !in_array($municipio->getProvincia()->getName(), $provincias)) {
                $tieneEuro = false;
                foreach ($municipio->getMonedasEntrega() as $moneda) {
                    if($moneda->getSimbolo() == $euro->getSimbolo()){
                        $tieneEuro = true;
                    }
                }

                if($tieneEuro){
                    $municipio->removeMonedasEntrega($euro);
                    $entityManager->persist($municipio);
                    $cont++;
                }
//                if (in_array($municipio->getProvincia()->getName(), $provincias)) {
//                    foreach ($nmMonedas as $moneda) {//Las añado todas
//                        $municipio->addMonedasEntrega($moneda);
//                        $entityManager->persist($municipio);
//                        $cont++;
//                    }
//                } else {//Solo añado cup y usd
//                    $municipio->addMonedasEntrega($usd);
//                    $municipio->addMonedasEntrega($cup);
//                    $entityManager->persist($municipio);
//                    $cont++;
//                }
            }
        }

        $entityManager->flush();

        $io->success('Municipios actualizados satisfactoriamente, total: ' . $cont);

        return 0;
    }

}
