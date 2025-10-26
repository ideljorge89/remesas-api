<?php

namespace App\Manager;

use App\Entity\NmMoneda;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TcPdfManager
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param $title
     * @param $html
     * @param $output_type S , E, F, FI,FD, D, I,
     * @return string
     */
    public function getDocument($title, $html, $output_type, $options = [])
    {
        $pdf = $this->container->get("white_october.tcpdf")->create('D', PDF_UNIT, array(), true, 'UTF-8', false);
        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetTitle($title);
        $encabezado = $this->container->get('twig')->render('backend/default/encabezado.html.twig', array('title' => $title));
        $pdf->setEncabezado($encabezado);

        $pdf->SetPrintHeader(true);
        $pdf->SetPrintFooter(true);

        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $pdf->SetMargins(15, PDF_MARGIN_TOP, 10);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        if (isset($options['orientation'])) $pdf->setPageOrientation($options['orientation']);

        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        $pdf->AddPage();

        $pdf->SetFont('dejavuserif', '', 9);
        $pdf->writeHTML($html, true, false, true, false, '');

        //---> FIN DEL LISTADO <----//
        $titleDoc = $title;

        //$fileatt and output PDF document
        $data = $pdf->Output($titleDoc . '.pdf', $output_type);

        return $data;
    }

    /**
     * @param $title
     * @param $html
     * @param $output_type S , E, F, FI,FD, D, I,
     * @return string
     */
    public function getDocumentReport($title, $html, $output_type, $options = [])
    {
        $pdf = $this->container->get("white_october.tcpdf")->create('D', PDF_UNIT, array(), true, 'UTF-8', false);
        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetTitle($title);
        $encabezado = $this->container->get('twig')->render('backend/default/reportes.html.twig', array('options' => $options));
        $pdf->setEncabezado($encabezado);

        $pdf->SetPrintHeader(true);
        $pdf->SetPrintFooter(true);

        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $pdf->SetMargins(10, 5, 5);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        if (isset($options['orientation'])) $pdf->setPageOrientation($options['orientation']);

        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, 15);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        $pdf->AddPage();

        $pdf->SetFont('dejavuserif', '', 9);
        $pdf->writeHTML($html, true, false, true, false, '');

        //---> FIN DEL LISTADO <----//
        $titleDoc = $title;

        //$fileatt and output PDF document
        $data = $pdf->Output($titleDoc . '.pdf', $output_type);

        return $data;
    }

    /**
     * @param $title
     * @param $html
     * @param $output_type S , E, F, FI,FD, D, I,
     * @return string
     */
    public function getDocumentReportEnvio($title, $result, $output_type, $options = [])
    {
        $pdf = $this->container->get("white_october.tcpdf")->create('D', PDF_UNIT, array(), true, 'UTF-8', false);
        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetTitle($title);
        $encabezado = $this->container->get('twig')->render('backend/default/reportes.html.twig', array('options' => $options));
        $pdf->setEncabezado($encabezado);

        $pdf->SetPrintHeader(true);
        $pdf->SetPrintFooter(true);

        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $pdf->SetMargins(10, 2, 5);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        if (isset($options['orientation'])) $pdf->setPageOrientation($options['orientation']);

        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, 10);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        $pdf->SetFont('dejavuserif', '', 9);
        $totalRemesas = 0;
        $montoTotal = 0;
        $montoTotalCUP = 0;
        $montoTotalUSD = 0;
        $montoTotalEUR = 0;
        foreach ($result as $province => $remesas) {
            $parts = array_chunk($remesas, 8);
            $totalProv = 0;
            $totalProvCUP = 0;
            $totalProvUSD = 0;
            $totalProvEUR = 0;
            $montoProv = 0;
            $montoProvCUP = 0;
            $montoProvUSD = 0;
            $montoProvEUR = 0;
            foreach ($remesas as $remesa) {//Contando el total de remesas.
                $totalProv++;
                if ($remesa->getMoneda()->getSimbolo() == NmMoneda::CURRENCY_CUC) {
                    $montoProv += $remesa->getImporteEntregar();
                } elseif ($remesa->getMoneda()->getSimbolo() == NmMoneda::CURRENCY_CUP) {
                    $montoProvCUP += $remesa->getImporteEntregar();
                } elseif ($remesa->getMoneda()->getSimbolo() == NmMoneda::CURRENCY_EUR) {
                    $montoProvEUR += $remesa->getImporteEntregar();
                } else {
                    $montoProvUSD += $remesa->getImporteEntregar();
                }

            }
            $numerPart = 1;
            foreach ($parts as $part) {
                $pdf->AddPage();

                $html = $this->container->get('twig')->render('backend/tn_remesa/reporte_remesas_pdf.html.twig', array(
                    'page' => $numerPart,
                    'prov' => $province,
                    'array_remesas' => $part,
                    'remesas' => $province,
                    'totalProvincia' => $totalProv,
                    'totalProvinciaCUP' => $totalProvCUP,
                    'totalProvinciaUSD' => $totalProvUSD,
                    'totalProvinciaEUR' => $totalProvEUR,
                    'montoProvincia' => $montoProv,
                    'montoProvinciaCUP' => $montoProvCUP,
                    'montoProvinciaUSD' => $montoProvUSD,
                    'montoProvinciaEUR' => $montoProvEUR,
                    'last' => ($numerPart == count($parts) ? true : false)
                ));

                $numerPart++;

                $pdf->writeHTML($html, true, false, true, false, '');
                $pdf->lastPage();
            }

            $totalRemesas = $totalRemesas + $totalProv;
            $montoTotal = $montoTotal + $montoProv;
            $montoTotalCUP = $montoTotalCUP + $montoProvCUP;
            $montoTotalUSD = $montoTotalUSD + $montoProvUSD;
            $montoTotalEUR = $montoTotalEUR + $montoProvEUR;
        }

        $pdf->AddPage();
        $html = $this->container->get('twig')->render('backend/tn_remesa/total_reporte_remesas_pdf.html.twig', array(
            'totalRemesas' => $totalRemesas,
            'importeRemesas' => $montoTotal,
            'importeRemesasCUP' => $montoTotalCUP,
            'importeRemesasUSD' => $montoTotalUSD,
            'importeRemesasEUR' => $montoTotalEUR
        ));
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->lastPage();

        //---> FIN DEL LISTADO <----//
        $titleDoc = $title;

        //$fileatt and output PDF document
        $data = $pdf->Output($titleDoc . '.pdf', $output_type);

        return $data;
    }

    /**
     * @param $title
     * @param $html
     * @param $output_type S , E, F, FI,FD, D, I,
     * @return string
     */
    public function getDocumentCierre($title, $html, $output_type, $options = [])
    {
        $pdf = $this->container->get("white_october.tcpdf")->create('D', PDF_UNIT, array(), true, 'UTF-8', false);
        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetTitle($title);
        $encabezado = $this->container->get('twig')->render('backend/default/reportes.html.twig', array('options' => $options));
        $pdf->setEncabezado($encabezado);

        $pdf->SetPrintHeader(true);
        $pdf->SetPrintFooter(true);

        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $pdf->SetMargins(10, 2, 5);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        if (isset($options['orientation'])) $pdf->setPageOrientation($options['orientation']);

        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, 15);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        $pdf->AddPage();

        $pdf->SetFont('dejavuserif', '', 9);

        $pdf->writeHTML($html, true, false, true, false, '');
        //---> FIN DEL LISTADO <----//
        $titleDoc = $title;
        //$fileatt and output PDF document
        $data = $pdf->Output($titleDoc . '.pdf', $output_type);

        return $data;
    }
}