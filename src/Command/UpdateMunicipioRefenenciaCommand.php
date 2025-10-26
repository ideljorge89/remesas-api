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

class UpdateMunicipioRefenenciaCommand extends ContainerAwareCommand
{
    protected static $defaultName = 'update:referencia';

    private $csvParsingOptions = array(
        'finder_in' => 'data',
        'finder_name' => 'municipios.csv',
        'ignoreFirstLine' => true
    );

    protected function configure()
    {
        $this
            ->setDescription('Actualiza la referenncia de los municipios para Cuba Max');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $csv = $this->parseCSV();

        $cont = 0;
        $fails = 0;
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        foreach ($csv as $data) {
            $municipio = $entityManager->getRepository(NmMunicipio::class)->findOneBy(['codigo' => substr($data['CodigoMunicipio'], 1,strlen($data['CodigoMunicipio']) - 1)]);
            if ($municipio instanceof NmMunicipio) {
                $municipio->setReferencia($data['Referencia']);
                $entityManager->persist($municipio);
                $cont++;
            } else {
                $fails++;
            }
        }

        $entityManager->flush();

        $io->success('Municipios actualizados satisfactoriamente, total: ' . $cont . ". Fallidos: " . $fails);

        return 0;
    }

    /**
     * Parse a csv file
     * @return array
     */

    private function parseCSV()
    {
        $ignoreFirstLine = $this->csvParsingOptions['ignoreFirstLine'];

        $finder = new Finder();

        $finder->files()
            ->in($this->csvParsingOptions['finder_in'])
            ->name($this->csvParsingOptions['finder_name']);

        foreach ($finder as $file) {
            $csv = $file;
        }

        $rows = array();
        if (($handle = fopen($csv->getRealPath(), "r")) !== FALSE) {
            $i = 0;
            $header = fgetcsv($handle);
            while (($data = fgetcsv($handle)) !== FALSE) {
                $i++;
                if ($ignoreFirstLine && $i == 0) {
                    continue;
                }
                foreach ($header as $j => $col)
                    $arr[$col] = $data[$j];
                $rows[] = $arr;
            }
            fclose($handle);
        }
        return $rows;
    }
}
