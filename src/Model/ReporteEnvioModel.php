<?php

namespace App\Model;


class ReporteEnvioModel
{

    private $reportes;

    /**
     * @return mixed
     */
    public function getReportes()
    {
        return $this->reportes;
    }

    /**
     * @param $reportes
     */
    public function setReportes($reportes): void
    {
        $this->reportes = $reportes;
    }
}