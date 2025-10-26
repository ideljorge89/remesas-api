<?php

namespace App\Manager;

use App\Entity\NmMoneda;
use App\Entity\TnDocumento;
use App\Entity\TnFactura;
use PhpOffice\PhpSpreadsheet\Shared\Font;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\IWriter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class PhpExcelManager
{
    private $container;
    private $em;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->em = $container->get('doctrine')->getManager();
    }


    public function validateRowDestinatario($cols, $row)
    {
        $errores = [];
        if ($row[$cols['FirstName']] == "") {
            $errores[] = 'FirstName';
        }
        if (($row[$cols['LastName']] == "" && $row[$cols['MaidenName']] == "")) {
            $errores[] = 'LastName';
            $errores[] = 'MaidenName';
        }
        if ($row[$cols['Adrs1']] == "") {
            $errores[] = 'Adrs1';
        }
        if ($row[$cols['Adrs2']] == "") {
            $errores[] = 'Adrs2';
        }
        if ($row[$cols['Phone']] == "") {
            $errores[] = 'Phone';
        }

        return $errores;
    }


    public function validateRowFactura($type, $cols, $row)
    {
        $errores = [];
        if ($type == TnDocumento::TIPO_FACTURA_TE) {
            if ($row[$cols['FirstName']] == "") {
                $errores[] = 'FirstName';
            }
            if (($row[$cols['LastName']] == "" && $row[$cols['MaidenName']] == "")) {
                $errores[] = 'LastName';
                $errores[] = 'MaidenName';
            }
            if ($row[$cols['Address']] == "") {
                $errores[] = 'Address';
            }
            if ($row[$cols['CodigoMunicipio']] == "" && (strlen($row[$cols['CodigoMunicipio']]) != 6 || strlen($row[$cols['CodigoMunicipio']]) != 5)) {
                $errores[] = 'CodigoMunicipio';
            }
            if ($row[$cols['Phone']] == "") {
                $errores[] = 'Phone';
            }
            if ($row[$cols['Monto']] == "") {
                $errores[] = 'Monto';
            }
            if ($row[$cols['Moneda']] == "" || $row[$cols['Moneda']] == NmMoneda::CURRENCY_CUC) {
                $errores[] = 'Moneda';
            }
        } elseif ($type == TnDocumento::TIPO_FACTURA_CMX) { // Cubamax
            if ($row[$cols['Nota']] == "") {
                $errores[] = 'Referencia';
            }
            if ($row[$cols['FirstName']] == "") {
                $errores[] = 'FirstName';
            }
            if (($row[$cols['LastName']] == "" && $row[$cols['MaidenName']] == "")) {
                $errores[] = 'LastName';
                $errores[] = 'MaidenName';
            }
            if ($row[$cols['Address']] == "") {
                $errores[] = 'Address';
            }
            if ($row[$cols['Municipio']] == "") {
                $errores[] = 'Municipio';
            }
            if ($row[$cols['Phone']] == "") {
                $errores[] = 'Phone';
            }
            if ($row[$cols['Monto']] == "") {
                $errores[] = 'Monto';
            }
            if ($row[$cols['Moneda']] == "" || $row[$cols['Moneda']] == NmMoneda::CURRENCY_CUC) {
                $errores[] = 'Moneda';
            }
        } elseif ($type == TnDocumento::TIPO_FACTURA_VCB) { // VaCuba
            if ($row[$cols['Referencia']] == "") {
                $errores[] = 'Referencia';
            }
            if ($row[$cols['FirstName']] == "") {
                $errores[] = 'FirstName';
            }
            if (($row[$cols['LastName']] == "" && $row[$cols['MaidenName']] == "")) {
                $errores[] = 'LastName';
                $errores[] = 'MaidenName';
            }
            if ($row[$cols['Address']] == "") {
                $errores[] = 'Address';
            }
            if ($row[$cols['Municipio']] == "") {
                $errores[] = 'Municipio';
            }
            if ($row[$cols['Phone']] == "") {
                $errores[] = 'Phone';
            }
            if ($row[$cols['Monto']] == "") {
                $errores[] = 'Monto';
            }
            if ($row[$cols['Moneda']] == "" || $row[$cols['Moneda']] == NmMoneda::CURRENCY_CUC) {
                $errores[] = 'Moneda';
            }
        } elseif ($type == TnDocumento::TIPO_FACTURA_CA) {
            if ($row[$cols['FirstName']] == "") {
                $errores[] = 'FirstName';
            }
            if (($row[$cols['LastName']] == "" && $row[$cols['MaidenName']] == "")) {
                $errores[] = 'LastName';
                $errores[] = 'MaidenName';
            }
            if ($row[$cols['Address_1']] == "" && $row[$cols['Address_2']] == "") {
                $errores[] = 'Address_1';
                $errores[] = 'Address_2';
            }
            if ($row[$cols['CodigoMunicipio']] == "" && strlen($row[$cols['CodigoMunicipio']]) != 7) {
                $errores[] = 'CodigoMunicipio';
            }
            if ($row[$cols['Phone']] == "") {
                $errores[] = 'Phone';
            }
            if ($row[$cols['Monto']] == "") {
                $errores[] = 'Monto';
            }
            if ($row[$cols['Moneda']] == "" || $row[$cols['Moneda']] == NmMoneda::CURRENCY_CUC) {
                $errores[] = 'Moneda';
            }
        } else {
            if ($row[$cols['FirstName']] == "") {
                $errores[] = 'FirstName';
            }
            if (($row[$cols['LastName']] == "" && $row[$cols['MaidenName']] == "")) {
                $errores[] = 'LastName';
                $errores[] = 'MaidenName';
            }
            if ($row[$cols['Address_1']] == "" && $row[$cols['Address_2']] == "") {
                $errores[] = 'Address_1';
                $errores[] = 'Address_2';
            }
            if ($row[$cols['CodigoMunicipio']] == "" && (strlen($row[$cols['CodigoMunicipio']]) != 6 || strlen($row[$cols['CodigoMunicipio']]) != 5)) {
                $errores[] = 'CodigoMunicipio';
            }
            if ($row[$cols['Phone']] == "" && $row[$cols['Phone_Alt']] == "") {
                $errores[] = 'Phone';
            }

            if ($row[$cols['Moneda']] == "" || $row[$cols['Moneda']] == NmMoneda::CURRENCY_CUC) {
                $errores[] = 'Moneda';
            }

            if ($row[$cols['Monto']] == "") {
                $errores[] = 'Monto';
            }
        }

        return $errores;
    }

    public function validateRowMultplie($type, $cols, $row)
    {
        $errores = [];
        if ($type == TnDocumento::TIPO_FACTURA_TE) {
            if ($row[$cols['FirstName']] == "") {
                $errores[] = 'FirstName';
            }
            if (($row[$cols['LastName']] == "" && $row[$cols['MaidenName']] == "")) {
                $errores[] = 'LastName';
                $errores[] = 'MaidenName';
            }
            if ($row[$cols['Address']] == "") {
                $errores[] = 'Address';
            }
            if ($row[$cols['CodigoMunicipio']] == "" && (strlen($row[$cols['CodigoMunicipio']]) != 6 || strlen($row[$cols['CodigoMunicipio']]) != 5)) {
                $errores[] = 'CodigoMunicipio';
            }
            if ($row[$cols['Phone']] == "") {
                $errores[] = 'Phone';
            }
            if ($row[$cols['Monto']] == "") {
                $errores[] = 'Monto';
            }
        } elseif ($type == TnDocumento::TIPO_FACTURA_CA) {
            if ($row[$cols['FirstName']] == "") {
                $errores[] = 'FirstName';
            }
            if (($row[$cols['LastName']] == "" && $row[$cols['MaidenName']] == "")) {
                $errores[] = 'LastName';
                $errores[] = 'MaidenName';
            }
            if ($row[$cols['Adr1']] == "") {
                $errores[] = 'Adr1';
            }
            if ($row[$cols['CodigoMunicipio']] == "" && (strlen($row[$cols['CodigoMunicipio']]) != 6 || strlen($row[$cols['CodigoMunicipio']]) != 5)) {
                $errores[] = 'CodigoMunicipio';
            }
            if ($row[$cols['Phone']] == "") {
                $errores[] = 'Phone';
            }
            if ($row[$cols['Monto']] == "") {
                $errores[] = 'Monto';
            }
        } elseif ($type == TnDocumento::TIPO_FACTURA_TM) {

            if ($row[$cols['Agency']] == "") {
                $errores[] = 'Agency';
            }
            if ($row[$cols['FirstName']] == "") {
                $errores[] = 'FirstName';
            }
            if (($row[$cols['LastName']] == "" && $row[$cols['MaidenName']] == "")) {
                $errores[] = 'LastName';
                $errores[] = 'MaidenName';
            }
            if ($row[$cols['Address_1']] == "" && $row[$cols['Address_2']] == "") {
                $errores[] = 'Address_1';
                $errores[] = 'Address_2';
            }
            if ($row[$cols['CodigoMunicipio']] == "" && (strlen($row[$cols['CodigoMunicipio']]) != 6 || strlen($row[$cols['CodigoMunicipio']]) != 5)) {
                $errores[] = 'CodigoMunicipio';
            }
            if ($row[$cols['Phone']] == "" && $row[$cols['Phone_Alt']] == "") {
                $errores[] = 'Phone';
            }
            if ($row[$cols['Moneda']] == "" || $row[$cols['Moneda']] == NmMoneda::CURRENCY_CUC) {
                $errores[] = 'Moneda';
            }
            if ($row[$cols['Monto']] == "") {
                $errores[] = 'Monto';
            }
        } else {

            $cols = ['FirstName' => 6, 'LastName' => 7, 'MaidenName' => 8, 'Address_1' => 9, 'Address_2' => 10, 'CodigoMunicipio' => 14, 'Phone' => 16, 'Phone_Alt' => 17, 'Monto' => 19];

            if ($row[$cols['FirstName']] == "") {
                $errores[] = 'FirstName';
            }
            if (($row[$cols['LastName']] == "" && $row[$cols['MaidenName']] == "")) {
                $errores[] = 'LastName';
                $errores[] = 'MaidenName';
            }
            if ($row[$cols['Address_1']] == "" && $row[$cols['Address_2']] == "") {
                $errores[] = 'Address_1';
                $errores[] = 'Address_2';
            }
            if ($row[$cols['CodigoMunicipio']] == "" && (strlen($row[$cols['CodigoMunicipio']]) != 6 || strlen($row[$cols['CodigoMunicipio']]) != 5)) {
                $errores[] = 'CodigoMunicipio';
            }
            if ($row[$cols['Phone']] == "" && $row[$cols['Phone_Alt']] == "") {
                $errores[] = 'Phone';
            }
            if ($row[$cols['Monto']] == "") {
                $errores[] = 'Monto';
            }
        }

        return $errores;
    }

    public function createDocument($meta)
    {
        $phpExcelObject = $this->container->get('phpexcel')->createPHPExcelObject();

        if (!empty($meta)) {
            foreach ($meta as $key => $value) {
                $set = 'set' . $key;
                if (method_exists($phpExcelObject->getProperties(), $set)) {
                    $phpExcelObject->getProperties()->$set($value);
                }
            }
        }
        return $phpExcelObject;
    }

    public function outputPhpExcel(IWriter $writer, $title)
    {
        // create the response
        $response = $this->container->get('phpexcel')->createStreamedResponse($writer);

        // adding headers
        $dispositionHeader = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $title . '.csv'
        );
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');

        return $response;
    }

    public function outputFile($content, $title)
    {
        // create the response
        $response = new Response($content);

        // adding headers
        $dispositionHeader = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $title
        );

        // Redirect output to a client’s web browser (Excel2007)
        $response->headers->set('Content-Type', ' application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', ' attachment;filename="' . $title . '.xlsx"');
        $response->headers->set('Cache-Control', ' max-age=0');
        $response->headers->set('Cache-Control', ' max-age=1');

        $response->headers->set('Expires', ' Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        $response->headers->set('Last-Modified', ' ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        $response->headers->set('Cache-Control', ' cache, must-revalidate'); // HTTP/1.1
        $response->headers->set('Pragma', ' public'); // HTTP/1.0

        return $response;
    }

    public function getContent(Spreadsheet $document, $type = 'Xlsx')
    {

        $cod = uniqid('excel-doc-');
        $subpath = $this->container->getParameter('excel_sub_path');
        if (!is_dir($this->container->getParameter('kernel.root_dir') . $subpath))
            mkdir($this->container->getParameter('kernel.root_dir') . $subpath, 0777, true);
        $originalPath = $this->container->getParameter('kernel.root_dir') . $subpath . $cod . '.xls';

        $writer = $this->container->get('phpexcel')->createWriter($document, $type);
        $writer->save($originalPath);

        $data = @file_get_contents($originalPath);
        $delete = $this->container->getParameter('excel_remove');
        if ($delete == true)
            @unlink($originalPath);

        return $data;
    }

    /**
     * @param $data
     * @param string $orientacion
     * @return Spreadsheet
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function exportDocumentoNoValidos($data, $orientacion = 'P')
    {
        ini_set('memory_limit', '-1');

        $columnas = ['FirstName', 'LastName', 'MaidenName', 'AdrsNo', 'Apto', 'Adrs1', 'Adrs2', 'Adrs3', 'Adrs4', 'Municipio', 'Phone', 'DateBirth', 'ProvinceCode', 'Dest.ID', 'Obsv.'];

        $datetime = new \DateTime('now');
        $phpExcelObject = $this->createDocument(array(
            'Title' => 'Destinatarios_no_validos_' . $datetime->format('Y-m-d H:i:s'),
            'LastModifiedBy' => 'Quickmoney system',
            'Creator' => 'Quickmoney system',
        ));
        $phpExcelObject->getDefaultStyle()->getFont()->setName('Calibri')
            ->setSize(9);

        $cells = [];

        $dataInitColumn = 'A';
        $dataInitRow = 1;
        $i = $dataInitRow;
        $init_coords = $dataInitColumn . $dataInitRow;
        $last_coords = $dataInitColumn . $dataInitRow;
        $v = $dataInitColumn;
        foreach ($columnas as $columna) {
            $last_coords = $cell = $v . $i;
            $cells[$cell] = $columna;
            $v++;
        }

        foreach ($cells as $cell => $value) {
            $phpExcelObject->getActiveSheet()->setCellValue($cell, $value);
        }
        $phpExcelObject
            ->getActiveSheet()
            ->getRowDimension(0)
            ->setRowHeight(-1);

        $initial = 'A';
        foreach ($cells as $cell => $value) {
            $phpExcelObject
                ->getActiveSheet()
                ->getColumnDimension($initial)
                ->setAutoSize(true);
            $phpExcelObject
                ->getActiveSheet()->getCell($cell)->getStyle()->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM);
            $initial++;
        }
        $phpExcelObject
            ->getActiveSheet()->setAutoFilter($init_coords . ":" . $last_coords);
        $phpExcelObject
            ->getActiveSheet()->setShowSummaryBelow(true)->setTitle('Destinatarios inválidos');

        Font::setAutoSizeMethod(Font::AUTOSIZE_METHOD_APPROX);

        $sheet = $phpExcelObject
            ->getActiveSheet();
        //data
        $rows = $dataInitRow + 1;
        $sheet->freezePane('A' . $rows);
        foreach ($data as $datum) {
            $cols = $dataInitColumn;
            foreach ($datum as $columna) {
                $sheet->getCell($cols . $rows)->setValue($columna);
                $cols++;
            }
            $rows++;
        }

        $orientation = ('P' == $orientacion) ? PageSetup::ORIENTATION_PORTRAIT : PageSetup::ORIENTATION_LANDSCAPE;
        $sheet->getPageSetup()->setOrientation($orientation);

        return $phpExcelObject;
    }

    /**
     * @param $data
     * @param string $orientacion
     * @return Spreadsheet
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function exportDocumentoFacturasNoValidas($data, $orientacion = 'P')
    {
        ini_set('memory_limit', '-1');

        $datetime = new \DateTime('now');
        $phpExcelObject = $this->createDocument(array(
            'Title' => 'Facturas_no_validas_' . $datetime->format('Y-m-d H:i:s'),
            'LastModifiedBy' => 'Quickmoney system',
            'Creator' => 'Quickmoney system',
        ));
        $phpExcelObject->getDefaultStyle()->getFont()->setName('Calibri')
            ->setSize(9);

        $dataInitColumn = 'A';
        $dataInitRow = 1;

        $phpExcelObject
            ->getActiveSheet()
            ->getRowDimension(0)
            ->setRowHeight(-1);

        $phpExcelObject
            ->getActiveSheet()->setShowSummaryBelow(true)->setTitle('Facturas inválidas');

        Font::setAutoSizeMethod(Font::AUTOSIZE_METHOD_APPROX);

        $sheet = $phpExcelObject
            ->getActiveSheet();
        //data
        $rows = $dataInitRow;
        foreach ($data as $datum) {
            $cols = $dataInitColumn;
            foreach ($datum as $columna) {
                $sheet->getCell($cols . $rows)->setValue($columna);
                $cols++;
            }
            $rows++;
        }

        $orientation = ('P' == $orientacion) ? PageSetup::ORIENTATION_PORTRAIT : PageSetup::ORIENTATION_LANDSCAPE;
        $sheet->getPageSetup()->setOrientation($orientation);

        return $phpExcelObject;
    }

    /**
     * @param $data
     * @param string $orientacion
     * @return Spreadsheet
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function exportDocumentoFacturasTramipro($data, $orientacion = 'P')
    {
        ini_set('memory_limit', '-1');

        $columnas = ['CName', 'CLastName', 'CMaidenName', 'CDriverLic', 'CDriverLicExpDate', 'CPhone', 'DName', 'DLastName', 'DMaidenName', 'DAddress1', 'DAddress2', 'DLocation', 'DProvince', 'DMunicipality', 'DMunicipalityCode', 'DCarnetId', 'DPhone1', 'DPhone2', 'Currrency', 'AmountSent', 'ConversionRate', 'SpecialMessage', 'TransactionNo', 'Obsv.'];

        $datetime = new \DateTime('now');
        $phpExcelObject = $this->createDocument(array(
            'Title' => 'Facturas_no_validas_' . $datetime->format('Y-m-d H:i:s'),
            'LastModifiedBy' => 'Quickmoney system',
            'Creator' => 'Quickmoney system',
        ));
        $phpExcelObject->getDefaultStyle()->getFont()->setName('Calibri')
            ->setSize(9);

        $cells = [];

        $dataInitColumn = 'A';
        $dataInitRow = 1;
        $i = $dataInitRow;
        $init_coords = $dataInitColumn . $dataInitRow;
        $last_coords = $dataInitColumn . $dataInitRow;
        $v = $dataInitColumn;
        foreach ($columnas as $columna) {
            $last_coords = $cell = $v . $i;
            $cells[$cell] = $columna;
            $v++;
        }

        foreach ($cells as $cell => $value) {
            $phpExcelObject->getActiveSheet()->setCellValue($cell, $value);
        }

        $initial = 'A';
        foreach ($cells as $cell => $value) {
            $phpExcelObject
                ->getActiveSheet()
                ->getColumnDimension($initial)
                ->setAutoSize(true);
            $phpExcelObject
                ->getActiveSheet()->getCell($cell)->getStyle()->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM);
            $initial++;
        }
        $phpExcelObject
            ->getActiveSheet()->setAutoFilter($init_coords . ":" . $last_coords);
        $phpExcelObject
            ->getActiveSheet()->setShowSummaryBelow(true)->setTitle('Facturas inválidas');

        Font::setAutoSizeMethod(Font::AUTOSIZE_METHOD_APPROX);

        $sheet = $phpExcelObject
            ->getActiveSheet();
        //data
        $rows = $dataInitRow + 1;
        $sheet->freezePane('A' . $rows);
        foreach ($data as $datum) {
            $cols = $dataInitColumn;
            foreach ($datum as $columna) {
                $sheet->getCell($cols . $rows)->setValue($columna);
                $phpExcelObject
                    ->getActiveSheet()
                    ->getColumnDimension($cols)
                    ->setAutoSize(true);

                $cols++;
            }
            $rows++;
        }

        $orientation = ('P' == $orientacion) ? PageSetup::ORIENTATION_PORTRAIT : PageSetup::ORIENTATION_LANDSCAPE;
        $sheet->getPageSetup()->setOrientation($orientation);

        return $phpExcelObject;
    }


    /**
     * @param $data
     * @param string $orientacion
     * @return Spreadsheet
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function exportDocumentoEnvioTransferencias($data, $orientacion = 'P')
    {
        ini_set('memory_limit', '-1');

        $columnas = ['Transaction ID', 'Sender user', 'Card Number', 'Card Holder Name', 'Phone', 'Amount', 'Currency'];

        $datetime = new \DateTime('now');
        $phpExcelObject = $this->createDocument(array(
            'Title' => 'Quickmoney Reporte Transferencias - ' . $datetime->format('Y-m-d H:i:s'),
            'LastModifiedBy' => 'Quickmoney system',
            'Creator' => 'Quickmoney system',
        ));
        $phpExcelObject->getDefaultStyle()->getFont()->setName('Calibri')
            ->setSize(9);

        $cells = [];

        $dataInitColumn = 'A';
        $dataInitRow = 1;
        $i = $dataInitRow;
        $init_coords = $dataInitColumn . $dataInitRow;
        $last_coords = $dataInitColumn . $dataInitRow;
        $v = $dataInitColumn;
        foreach ($columnas as $columna) {
            $last_coords = $cell = $v . $i;
            $cells[$cell] = $columna;
            $v++;
        }

        foreach ($cells as $cell => $value) {
            $phpExcelObject->getActiveSheet()->setCellValue($cell, $value);
        }

        $initial = 'A';
        foreach ($cells as $cell => $value) {
            $phpExcelObject
                ->getActiveSheet()
                ->getColumnDimension($initial)
                ->setAutoSize(true);
            $phpExcelObject
                ->getActiveSheet()->getCell($cell)->getStyle()->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM);
            $initial++;
        }
        $phpExcelObject
            ->getActiveSheet()->setAutoFilter($init_coords . ":" . $last_coords);
        $phpExcelObject
            ->getActiveSheet()->setShowSummaryBelow(true)->setTitle('TRANSFERENCIAS');

        Font::setAutoSizeMethod(Font::AUTOSIZE_METHOD_APPROX);

        $sheet = $phpExcelObject
            ->getActiveSheet();
        //data
        $rows = $dataInitRow + 1;
        $sheet->freezePane('A' . $rows);
        foreach ($data as $datum) {
            $cols = $dataInitColumn;
            foreach ($datum as $key => $columna) {
                if ($cols == "C" && strlen($columna) != 19) { // Para saber si es el número de tarjeta
                    $sheet->getCell($cols . $rows)->setValue(trim(chunk_split($columna, 4, ' ')));
                    $phpExcelObject
                        ->getActiveSheet()
                        ->getColumnDimension($cols)
                        ->setAutoSize(true);
                } else {
                    $sheet->getCell($cols . $rows)->setValue($columna);
                    $phpExcelObject
                        ->getActiveSheet()
                        ->getColumnDimension($cols)
                        ->setAutoSize(true);
                }

                $cols++;
            }
            $rows++;
        }

        $orientation = ('P' == $orientacion) ? PageSetup::ORIENTATION_PORTRAIT : PageSetup::ORIENTATION_LANDSCAPE;
        $sheet->getPageSetup()->setOrientation($orientation);

        return $phpExcelObject;
    }


    /**
     * @param $data
     * @param string $orientacion
     * @return Spreadsheet
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function exportDocumentoReporteAgencias($data, $orientacion = 'P')
    {
        ini_set('memory_limit', '-1');

        $columnas = ['Factura', 'Fecha', 'Emisor', 'Destinatario', 'Estado', 'Enviado', '% Asig.', 'A Pagar'];

        $datetime = new \DateTime('now');
        $phpExcelObject = $this->createDocument(array(
            'Title' => 'Quickmoney Reporte Agencia - ' . $datetime->format('Y-m-d H:i:s'),
            'LastModifiedBy' => 'Quickmoney system',
            'Creator' => 'Quickmoney system',
        ));
        $phpExcelObject->getDefaultStyle()->getFont()->setName('Calibri')
            ->setSize(9);

        $phpExcelObject
            ->getActiveSheet()->setShowSummaryBelow(true)->setTitle('AGENCIAS');

        Font::setAutoSizeMethod(Font::AUTOSIZE_METHOD_APPROX);

        $sheet = $phpExcelObject
            ->getActiveSheet();
        //data
        $rows = 1;
        $totalTCUC = 0;
        $totalTCUP = 0;
        $totalTUSD = 0;
        $totalTEUR = 0;
        $totalTPagar = 0;
        $totalRemesas = 0;
        foreach ($data as $agencia => $facturas) {
            $sheet->mergeCells('A' . $rows . ':H' . $rows);
            $sheet->getCell('A' . $rows)->getStyle()->getFont()->setBold(true)->setSize(9);
            $sheet->setCellValue('A' . $rows, $agencia);
            $sheet->getCell('A' . $rows)->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $rows++;

            $cells = [];

            $dataInitColumn = 'A';
            $i = $rows;
            $v = $dataInitColumn;
            foreach ($columnas as $columna) {
                $cell = $v . $i;
                $cells[$cell] = $columna;
                $v++;
            }

            foreach ($cells as $cell => $value) {
                $sheet->getCell($cell)->getStyle()->getFont()->setBold(true)->setSize(9);
                $phpExcelObject->getActiveSheet()->setCellValue($cell, $value);
            }

            $initial = 'A';
            foreach ($cells as $cell => $value) {
                $phpExcelObject
                    ->getActiveSheet()
                    ->getColumnDimension($initial)
                    ->setAutoSize(true);
                $initial++;
            }

            $rows++;

            $totalACUC = 0;
            $totalACUP = 0;
            $totalAUSD = 0;
            $totalAEUR = 0;
            $totalAPagar = 0;
            foreach ($facturas as $factura) {
                if ($factura instanceof TnFactura) {
                    $sheet->setCellValue('A' . $rows, $factura->getNoFactura());
                    $sheet->setCellValue('B' . $rows, $factura->getFechaContable()->format('d/m/Y'));
                    $sheet->setCellValue('C' . $rows, $factura->getEmisor()->showData());
                    foreach ($factura->getRemesas() as $remesa) {
                        $sheet->setCellValue('D' . $rows, $remesa->getDestinatario());
                    }
                    $sheet->setCellValue('E' . $rows, $factura->getEstado());
                    $sheet->setCellValue('F' . $rows, $factura->getImporte() . " " . $factura->getMoneda());
                    $sheet->setCellValue('G' . $rows, ($factura->getTipoPorcentaje() == 'porciento' ? ($factura->getUtilidadFija() ? $factura->getPorcentajeAsignado() . " USD" : $factura->getPorcentajeAsignado() . " %") : $factura->getPorcentajeAsignado() . " USD"));
                    $sheet->setCellValue('H' . $rows, number_format($factura->getTotalPagar(), 2) . " USD");

                    if ($factura->getMoneda() == NmMoneda::CURRENCY_CUC) {
                        $totalACUC += $factura->getImporte();
                    } elseif ($factura->getMoneda() == NmMoneda::CURRENCY_CUP) {
                        $totalACUP += $factura->getImporte();
                    } elseif ($factura->getMoneda() == NmMoneda::CURRENCY_EUR) {
                        $totalAEUR += $factura->getImporte();
                    } else {
                        $totalAUSD += $factura->getImporte();
                    }
                    $totalAPagar += $factura->getTotalPagar();

                    $rows++;
                }
            }

            //Resumen de esa agencia.
            $sheet->mergeCells('A' . $rows . ':B' . $rows);
            $sheet->getCell('A' . $rows)->getStyle()->getFont()->setBold(true)->setSize(9);
            $sheet->setCellValue('A' . $rows, $agencia);
            //Total
            $sheet->setCellValue('C' . $rows, count($facturas) . ' remesa(s)');
            $sheet->getCell('C' . $rows)->getStyle()->getFont()->setBold(true)->setSize(9);
            $sheet->mergeCells('D' . $rows . ':E' . $rows);
            $sheet->setCellValue('D' . $rows, 'IMPORTE: ' . number_format($totalACUC, 2) . ' CUC, ' . number_format($totalACUP, 2) . ' CUP, ' . number_format($totalAUSD, 2) . ' USD,' . number_format($totalAEUR, 2) . ' EUR');
            $sheet->getCell('D' . $rows)->getStyle()->getFont()->setBold(true)->setSize(9);
            $sheet->getCell('D' . $rows)->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->mergeCells('F' . $rows . ':H' . $rows);
            $sheet->setCellValue('F' . $rows, 'PAGAR: ' . number_format($totalAPagar, 2) . ' USD');
            $sheet->getCell('F' . $rows)->getStyle()->getFont()->setBold(true)->setSize(9);
            $sheet->getCell('F' . $rows)->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $rows++;

            $totalTCUC += $totalACUC;
            $totalTCUP += $totalACUP;
            $totalTUSD += $totalAUSD;
            $totalTEUR += $totalAEUR;
            $totalTPagar += $totalAPagar;
            $totalRemesas += count($facturas);
        }
        $rows++;
        //Resumen totales.
        $sheet->mergeCells('A' . $rows . ':B' . $rows);
        $sheet->getCell('A' . $rows)->getStyle()->getFont()->setBold(true)->setSize(9);
        $sheet->setCellValue('A' . $rows, "RESUMEN TOTAL");
        //Total
        $sheet->setCellValue('C' . $rows, $totalRemesas . ' remesa(s)');
        $sheet->getCell('C' . $rows)->getStyle()->getFont()->setBold(true)->setSize(9);
        $sheet->mergeCells('D' . $rows . ':E' . $rows);
        $sheet->setCellValue('D' . $rows, 'IMPORTE: ' . number_format($totalTCUC, 2) . ' CUC, ' . number_format($totalTCUP, 2) . ' CUP, ' . number_format($totalTUSD, 2) . ' USD,' . number_format($totalTEUR, 2) . ' EUR');
        $sheet->getCell('D' . $rows)->getStyle()->getFont()->setBold(true)->setSize(9);
        $sheet->getCell('D' . $rows)->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->mergeCells('F' . $rows . ':H' . $rows);
        $sheet->setCellValue('F' . $rows, 'PAGAR: ' . number_format($totalTPagar, 2) . ' USD');
        $sheet->getCell('F' . $rows)->getStyle()->getFont()->setBold(true)->setSize(9);
        $sheet->getCell('F' . $rows)->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);


        $orientation = ('P' == $orientacion) ? PageSetup::ORIENTATION_PORTRAIT : PageSetup::ORIENTATION_LANDSCAPE;
        $sheet->getPageSetup()->setOrientation($orientation);

        return $phpExcelObject;
    }

    /**
     * @param $data
     * @param string $orientacion
     * @return Spreadsheet
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public
    function exportDocumentoFacturasCanada($data, $orientacion = 'P')
    {
        ini_set('memory_limit', '-1');

        $datetime = new \DateTime('now');
        $phpExcelObject = $this->createDocument(array(
            'Title' => 'Facturas_no_validas_' . $datetime->format('Y-m-d H:i:s'),
            'LastModifiedBy' => 'Quickmoney system',
            'Creator' => 'Quickmoney system',
        ));

        $phpExcelObject->getDefaultStyle()->getFont()->setName('Calibri')
            ->setSize(9);

        $phpExcelObject
            ->getActiveSheet()->setShowSummaryBelow(true)->setTitle('Facturas inválidas');

        Font::setAutoSizeMethod(Font::AUTOSIZE_METHOD_APPROX);

        $sheet = $phpExcelObject
            ->getActiveSheet();
        //data
        $rows = 1;
        $dataInitColumn = 'A';
        foreach ($data as $datum) {
            $cols = $dataInitColumn;
            foreach ($datum as $columna) {
                $sheet->getCell($cols . $rows)->setValue($columna);
                if ($columna != "") {
                    $phpExcelObject
                        ->getActiveSheet()
                        ->getColumnDimension($cols)
                        ->setAutoSize(true);
                } else {
                    $phpExcelObject
                        ->getActiveSheet()
                        ->getColumnDimension($cols)
                        ->setWidth(10);
                }

                $cols++;
            }
            $rows++;
        }

        $orientation = ('P' == $orientacion) ? PageSetup::ORIENTATION_PORTRAIT : PageSetup::ORIENTATION_LANDSCAPE;
        $sheet->getPageSetup()->setOrientation($orientation);

        return $phpExcelObject;
    }
}
