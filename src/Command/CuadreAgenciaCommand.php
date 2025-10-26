<?php

namespace App\Command;

use App\Entity\NmMoneda;
use App\Entity\NmMunicipio;
use App\Entity\NmProvincia;
use App\Entity\TnAgencia;
use App\Entity\TnFactura;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Parser;

class CuadreAgenciaCommand extends ContainerAwareCommand
{
    protected static $defaultName = 'cuadre:agencia';

    private $csvParsingOptions = array(
        'finder_in' => 'data',
        'finder_name' => '5.csv',
        'ignoreFirstLine' => true
    );

    protected function configure()
    {
        $this
            ->setDescription('Compara el cuadre de las remesas de las agencias.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $csv = $this->parseCSV();

        $entityManager = $this->getContainer()->get('doctrine')->getManager();

        $tnAgencia = $entityManager->getRepository(TnAgencia::class)->find(30);
        //Obteniendo los agentes también para buscar todas las remesas
        $tempAgentes = [];
        foreach ($tnAgencia->getAgentes() as $agente) {
            $tempAgentes[] = $agente->getId();
        }
        $params = [
            'fi' => new \DateTime(date('2021/01/11 00:00:00')),
            'ff' => new \DateTime(date('2021/01/11 23:59:59')),
            'agencias' => [$tnAgencia->getId()],
            'agentes' => $tempAgentes
        ];

//        $tnFacturas = $entityManager->getRepository(TnFactura::class)->findFacturasCuadreAgencia($params);
        $diferencia = [];

        foreach ($csv as $data) {
            $importe = (integer)trim($data["17"]);
            $tnFactura = $entityManager->getRepository(TnFactura::class)->findOneBy(['notas' => trim($data["FACTURA"])]);
            if ($tnFactura) {
                if ($importe * 30 != $tnFactura->getImporte()) {
                    $diferencia[] = $data["FACTURA"];
                }
            } else {
                $diferencia[] = $data["FACTURA"];
            }
        }

        dump($diferencia);
        exit;


        $entityManager->flush();

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
