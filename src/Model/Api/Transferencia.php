<?php

namespace App\Model\Api;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use App\Validator\Constraints\MinimalProperties;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * @ApiResource(attributes={
 *     "security"="is_granted('ROLE_AGENCIA')",
 *     "validation_groups"={"register"}},
 *     itemOperations={},
 *     collectionOperations={
 *     "new_transferencias_bulk"={"security"="is_granted('ROLE_AGENCIA')","method"="POST","route_name"="new_transferencias_bulk"}
 * })
 */
class Transferencia
{
    /**
     * @var string
     * @Assert\NotBlank(groups={"register"})
     */
    protected $Referencia;

    /**
     * @var string
     * @Assert\NotBlank(groups={"register"})
     * @Assert\Length(
     *     max = 50,
     *     groups={"register"}
     * )
     */
    protected $Emisor;

    /**
     * @var string
     * @Assert\NotBlank(groups={"register"})
     * @Assert\Length(
     *     max = 255,
     *     groups={"register"}
     * )
     */
    protected $TitularTarjeta;

    /**
     * @var string
     * @Assert\NotBlank(groups={"register"})
     * @Assert\Length(
     *     max = 255,
     *     groups={"register"}
     * )
     */
    protected $NumeroTarjeta;

    /**
     * @var float
     * @Assert\NotBlank(groups={"register"})
     * @Assert\GreaterThan(value=0,groups={"register"})
     */
    protected $Monto;

    /**
     * @var string
     * @Assert\NotBlank(groups={"register"})
     * @Assert\Choice(choices={"USD"},message="Este valor deberia ser USD",groups={"register"})
     */
    protected $Moneda;

    /**
     * @var string
     * @Assert\Length(
     *     max = 255,
     *     groups={"register"}
     * )
     */
    protected $Nota;

    /**
     * @return string
     */
    public function getReferencia(): string
    {
        return $this->Referencia;
    }

    /**
     * @param string $Referencia
     * @return Transferencia
     */
    public function setReferencia(string $Referencia): Transferencia
    {
        $this->Referencia = $Referencia;
        return $this;
    }

    /**
     * @return string
     */
    public function getEmisor(): string
    {
        return $this->Emisor
            ;
    }

    /**
     * @param string $Emisor
     * @return Transferencia
     */
    public function setEmisor(string $Emisor): Transferencia
    {
        $this->Emisor = $Emisor;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitularTarjeta(): string
    {
        return $this->TitularTarjeta;
    }

    /**
     * @param string $TitularTarjeta
     * @return Transferencia
     */
    public function setTitularTarjeta(string $TitularTarjeta): Transferencia
    {
        $this->TitularTarjeta = $TitularTarjeta;
        return $this;
    }

    /**
     * @return string
     */
    public function getNumeroTarjeta(): string
    {
        return $this->NumeroTarjeta;
    }

    /**
     * @param string $NumeroTarjeta
     * @return Transferencia
     */
    public function setNumeroTarjeta(string $NumeroTarjeta): Transferencia
    {
        $this->NumeroTarjeta = $NumeroTarjeta;
        return $this;
    }

    /**
     * @return float
     */
    public function getMonto(): float
    {
        return $this->Monto;
    }

    /**
     * @param float $Monto
     * @return Transferencia
     */
    public function setMonto(float $Monto): Transferencia
    {
        $this->Monto = $Monto;
        return $this;
    }

    /**
     * @return string
     */
    public function getMoneda(): string
    {
        return $this->Moneda;
    }

    /**
     * @param string $Moneda
     * @return Transferencia
     */
    public function setMoneda(string $Moneda): Transferencia
    {
        $this->Moneda = $Moneda;
        return $this;
    }

    /**
     * @return string
     */
    public function getNota(): string
    {
        return $this->Nota;
    }

    /**
     * @param string $Nota
     * @return Transferencia
     */
    public function setNota(string $Nota): Transferencia
    {
        $this->Nota = $Nota;
        return $this;
    }
}
