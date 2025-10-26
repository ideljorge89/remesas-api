<?php

namespace App\Command;

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

class UpdateLocationsCommand extends ContainerAwareCommand
{
    protected static $defaultName = 'update:locations';

    private $csvParsingOptions = array(
        'finder_in' => 'data',
        'finder_name' => 'municipios.csv',
        'ignoreFirstLine' => true
    );

    protected function configure()
    {
        $this
            ->setDescription('Actualiza las localizaciones de las provincias para utilizar el mapa');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $manager = $this->getContainer()->get('doctrine')->getManager();

        $parser = new Parser();
        $data = $parser->parse(@file_get_contents('data/provincias-municipios.yml'));

        foreach ($data['data'] as $key => $value) {
            $provincia = $manager->getRepository(NmProvincia::class)->findOneBy(['acronimo' => $value['acronimo']]);
            if ($provincia instanceof NmProvincia) {
                $provincia->setLatitud($value['latitud']);
                $provincia->setLongitud($value['longitud']);
                $provincia->setMapZoom($value['mapZoom']);
                $manager->persist($provincia);
            }
        }

        $manager->flush();

        $io->success("Provincias actualizados satisfactoriamente");

        return 0;
    }
}
