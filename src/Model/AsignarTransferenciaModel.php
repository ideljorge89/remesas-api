<?php

namespace App\Model;


class AsignarTransferenciaModel
{

    private $transferencias;

    /**
     * @return mixed
     */
    public function getTransferencias()
    {
        return $this->transferencias;
    }

    /**
     * @param mixed $transferencias
     */
    public function setTransferencias($transferencias): void
    {
        $this->transferencias = $transferencias;
    }
}