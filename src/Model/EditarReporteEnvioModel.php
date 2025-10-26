<?php

namespace App\Model;


class EditarReporteEnvioModel
{

    private $reporte;
    private $remesas;


    /**
     * @return mixed
     */
    public function getRemesas()
    {
        return $this->remesas;
    }

    /**
     * @return mixed
     */
    public function getReporte()
    {
        return $this->reporte;
    }

    /**
     * @param mixed $remesas
     */
    public function setRemesas($remesas): void
    {
        $this->remesas = $remesas;
    }

    /**
     * @param mixed $reporte
     */
    public function setReporte($reporte): void
    {
        $this->reporte = $reporte;
    }
}