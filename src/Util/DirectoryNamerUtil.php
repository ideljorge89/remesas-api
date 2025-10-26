<?php

namespace App\Util;

use App\Entity\TnDocumento;
use App\Entity\TnTransferencia;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\File;

class DirectoryNamerUtil
{

    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getNamer($prefix, $file)
    {
        //$originalFilename = pathinfo($blFile->getClientOriginalName(), PATHINFO_FILENAME);
        // this is needed to safely include the file name as part of the URL
        //$safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', rand(0, 9999) . sha1(md5(time())));
        $newFilename = uniqid($prefix) . '.' . $file->guessExtension();
        return $newFilename;
    }

    public function getDocumentPath($object)
    {
        $dirpath = $this->getDocumentDirPath($object);

        if ($object instanceof TnDocumento)
            return $dirpath . DIRECTORY_SEPARATOR . $object->getUrl();

    }

    public function getDocumentOldPath($object)
    {
        $dirpath = $this->getDocumentOldDirPath($object);

        if ($object instanceof TnDocumento)
            return $dirpath . DIRECTORY_SEPARATOR . $object->getUrl();

    }

    public function getDocumentOldDirPath($object)
    {
        $path = null;
        try {
            if ($object instanceof TnDocumento) {
                $path = $this->container->getParameter('files_path');
            }else if ($object instanceof TnTransferencia) {
                $path = $this->container->getParameter('evidencia_transferencia_path');
            }
        } catch (\Exception $e) {

        }
        if ($path === null) {
            throw new \Exception("Object not supported".get_class($object));
        }


        return $path;
    }

    public function getDocumentDirPath($object)
    {
        $path = null;
        try {
            if ($object instanceof TnDocumento) {
                $path = $this->container->getParameter('files_path');
            }else if ($object instanceof TnTransferencia) {
                $path = $this->container->getParameter('evidencia_transferencia_path');
            }
        } catch (\Exception $e) {

        }
        if ($path === null) {
            throw new \Exception("Object not supported");
        }


        return $path;
    }

    public function parseCsv(TnDocumento $tnDocumento){
        $finder = new Finder();

        $finder->files()
            ->in($this->container->getParameter('files_path'))
            ->name($tnDocumento->getUrl());

        foreach ($finder as $file) {
            $csv = $file;
        }

        $rows = array();
        if (($handle = fopen($csv->getRealPath(), "r")) !== FALSE) {
            $i = 0;
            $header = fgetcsv($handle);
            while (($data = fgetcsv($handle)) !== FALSE) {
                $i++;
                $arr = [];
                foreach ($header as $j => $col)
                    $arr[] = $data[$j];
                $rows[] = $arr;
            }
            fclose($handle);
        }
        return $rows;
    }

    public function parseCsvCubaMax(TnDocumento $tnDocumento){
        $finder = new Finder();

        $finder->files()
            ->in($this->container->getParameter('files_path'))
            ->name($tnDocumento->getUrl());

        foreach ($finder as $file) {
            $csv = $file;
        }

        $rows = array();
        if (($handle = fopen($csv->getRealPath(), "r")) !== FALSE) {
            $i = 0;
            $header = fgetcsv($handle);
            while (($data = fgetcsv($handle)) !== FALSE) {
                $i++;
                $arr = [];
                foreach ($header as $j => $col)
                    $arr[] = $data[$j];
                $rows[] = $arr;
            }
            fclose($handle);
        }
        return $rows;
    }

    public function parseCsvTransExport(TnDocumento $tnDocumento){
        $finder = new Finder();

        $finder->files()
            ->in($this->container->getParameter('files_path'))
            ->name($tnDocumento->getUrl());

        foreach ($finder as $file) {
            $csv = $file;
        }

        $rows = array();
        if (($handle = fopen($csv->getRealPath(), "r")) !== FALSE) {
            while (($data = fgetcsv($handle)) !== FALSE) {
                $arr = [];
                for ($j = 0; $j < 9; $j++){
                    $arr[] = $data[$j];
                }
                $rows[] = $arr;
            }
            fclose($handle);
        }
        return $rows;
    }

    public function parseCsvCanada(TnDocumento $tnDocumento){
        $finder = new Finder();

        $finder->files()
            ->in($this->container->getParameter('files_path'))
            ->name($tnDocumento->getUrl());

        foreach ($finder as $file) {
            $csv = $file;
        }

        $rows = array();
        if (($handle = fopen($csv->getRealPath(), "r")) !== FALSE) {
            while (($data = fgetcsv($handle)) !== FALSE) {
                $arr = [];
                for ($j = 0; $j < 23; $j++){
                    $arr[] = $data[$j];
                }
                $rows[] = $arr;
            }
            fclose($handle);
        }
        return $rows;
    }

    public function parseCsvTramiPro(TnDocumento $tnDocumento){
        $finder = new Finder();

        $finder->files()
            ->in($this->container->getParameter('files_path'))
            ->name($tnDocumento->getUrl());

        foreach ($finder as $file) {
            $csv = $file;
        }

        $rows = array();
        if (($handle = fopen($csv->getRealPath(), "r")) !== FALSE) {
            $i = 0;
            $header = fgetcsv($handle);
            while (($data = fgetcsv($handle)) !== FALSE) {
                $i++;
                $arr = [];
                foreach ($header as $j => $col)
                    $arr[] = $data[$j];
                $rows[] = $arr;
            }
            fclose($handle);
        }
        return $rows;
    }

    function eliminar_tildes($cadena){
        //Ahora reemplazamos las letras
        $cadena = str_replace(
            array('á', 'à', 'ä', 'â', 'ª', 'Á', 'À', 'Â', 'Ä'),
            array('a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A'),
            $cadena
        );

        $cadena = str_replace(
            array('é', 'è', 'ë', 'ê', 'É', 'È', 'Ê', 'Ë'),
            array('e', 'e', 'e', 'e', 'E', 'E', 'E', 'E'),
            $cadena );

        $cadena = str_replace(
            array('í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î'),
            array('i', 'i', 'i', 'i', 'I', 'I', 'I', 'I'),
            $cadena );

        $cadena = str_replace(
            array('ó', 'ò', 'ö', 'ô', 'Ó', 'Ò', 'Ö', 'Ô'),
            array('o', 'o', 'o', 'o', 'O', 'O', 'O', 'O'),
            $cadena );

        $cadena = str_replace(
            array('ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Û', 'Ü'),
            array('u', 'u', 'u', 'u', 'U', 'U', 'U', 'U'),
            $cadena );

        $cadena = str_replace(
            array('ñ', 'Ñ', 'ç', 'Ç', '�'),
            array('n', 'N', 'c', 'C', "n"),
            $cadena
        );

        return $cadena;
    }

    function eliminar_extrannos($cadena){
        //Ahora caracteres extraños
        $cadena = str_replace(
            array('ñ', 'Ñ', 'ç', 'Ç', '�', '‘'),
            array('n', 'N', 'c', 'C', "n", ''),
            $cadena
        );

        return $cadena;
    }
}