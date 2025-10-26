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
 *     "new_remesas_bulk"={"security"="is_granted('ROLE_AGENCIA')","method"="POST","route_name"="new_remesas_bulk"}
 * })
 */
class Remesa
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
    protected $Nombre;

    /**
     * @var string
     * @Assert\NotBlank(groups={"register"})
     * @Assert\Length(
     *     max = 50,
     *     groups={"register"}
     * )
     */
    protected $Apellido1;

    /**
     * @var string
     * @Assert\Length(
     *     max = 255,
     *     groups={"register"}
     * )
     */
    protected $Apellido2;

    /**
     * @var string
     * @Assert\NotBlank(groups={"register"})
     * @Assert\Length(
     *     max = 255,
     *     groups={"register"}
     * )
     */
    protected $Direccion;

    /**
     * @var string
     * @Assert\Length(
     *     max = 255,
     *     groups={"register"}
     * )
     */
    protected $Provincia;

    /**
     * @var string
     * @Assert\NotBlank(groups={"register"})
     * @Assert\Length(
     *     max = 255,
     *     groups={"register"}
     * )
     */
    protected $Municipio;

    /**
     * @var string
     * @Assert\NotBlank(groups={"register"})
     */
    protected $Telefono;

    /**
     * @var float
     * @Assert\NotBlank(groups={"register"})
     * @Assert\GreaterThan(value=0,groups={"register"})
     */
    protected $Monto;

    /**
     * @var string
     * @Assert\NotBlank(groups={"register"})
     * @Assert\Choice(choices={"CUC","CUP","USD","EUR"},message="Este valor deberia ser EUR,USD,CUP o CUC",groups={"register"})
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
     * @return Remesa
     */
    public function setReferencia(string $Referencia): Remesa
    {
        $this->Referencia = $Referencia;
        return $this;
    }

    /**
     * @return string
     */
    public function getNombre(): string
    {
        return $this->Nombre;
    }

    /**
     * @param string $Nombre
     * @return Remesa
     */
    public function setNombre(string $Nombre): Remesa
    {
        $this->Nombre = $Nombre;
        return $this;
    }

    /**
     * @return string
     */
    public function getApellido1(): string
    {
        return $this->Apellido1;
    }

    /**
     * @param string $Apellido1
     * @return Remesa
     */
    public function setApellido1(string $Apellido1): Remesa
    {
        $this->Apellido1 = $Apellido1;
        return $this;
    }

    /**
     * @return string
     */
    public function getApellido2(): string
    {
        return $this->Apellido2;
    }

    /**
     * @param string $Apellido2
     * @return Remesa
     */
    public function setApellido2(string $Apellido2): Remesa
    {
        $this->Apellido2 = $Apellido2;
        return $this;
    }

    /**
     * @return string
     */
    public function getDireccion(): string
    {
        return $this->Direccion;
    }

    /**
     * @param string $Direccion
     * @return Remesa
     */
    public function setDireccion(string $Direccion): Remesa
    {
        $this->Direccion = $Direccion;
        return $this;
    }

    /**
     * @return string
     */
    public function getProvincia(): string
    {
        return $this->Provincia;
    }

    /**
     * @param string $Provincia
     * @return Remesa
     */
    public function setProvincia(string $Provincia): Remesa
    {
        $this->Provincia = $Provincia;
        return $this;
    }

    /**
     * @return string
     */
    public function getMunicipio(): string
    {
        return $this->Municipio;
    }

    /**
     * @param string $Municipio
     * @return Remesa
     */
    public function setMunicipio(string $Municipio): Remesa
    {
        $this->Municipio = $Municipio;
        return $this;
    }

    /**
     * @return string
     */
    public function getTelefono(): string
    {
        return $this->Telefono;
    }

    /**
     * @param string $Telefono
     * @return Remesa
     */
    public function setTelefono(string $Telefono): Remesa
    {
        $this->Telefono = $Telefono;
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
     * @return Remesa
     */
    public function setMonto(float $Monto): Remesa
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
     * @return Remesa
     */
    public function setMoneda(string $Moneda): Remesa
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
     * @return Remesa
     */
    public function setNota(string $Nota): Remesa
    {
        $this->Nota = $Nota;
        return $this;
    }


}
