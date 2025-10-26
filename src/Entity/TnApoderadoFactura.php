<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TnApoderadoFacturaRepository")
 */
class TnApoderadoFactura
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $porcentajeAsignado;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $utilidadFija;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $totalPagar;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $porcentajeAsignadoSubordinada;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $utilidadFijaFijaSubordinada;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $totalPagarSubordinada;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $tipoPorcentaje;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $utilidad;

    /**
     * @var \DateTime|null
     * @ORM\Column(name="created",type="datetime",nullable=true)
     * @Gedmo\Timestampable(on="create")
     */
    private $created;

    /**
     * @var \DateTime|null
     * @ORM\Column(name="updated",type="datetime",nullable=true)
     * @Gedmo\Timestampable(on="update")
     */
    private $updated;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\TnFactura", inversedBy="apoderadoFactura")
     */
    private $factura;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\TnApoderado", inversedBy="apoderadoFacturas")
     */
    private $apoderado;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\TnAgencia", inversedBy="apoderadoFacturas")
     */
    private $agencia;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPorcentajeAsignado(): ?float
    {
        return $this->porcentajeAsignado;
    }

    public function setPorcentajeAsignado(?float $porcentajeAsignado): self
    {
        $this->porcentajeAsignado = $porcentajeAsignado;

        return $this;
    }

    public function getUtilidadFija(): ?bool
    {
        return $this->utilidadFija;
    }

    public function setUtilidadFija(?bool $utilidadFija): self
    {
        $this->utilidadFija = $utilidadFija;

        return $this;
    }

    public function getTotalPagar(): ?float
    {
        return $this->totalPagar;
    }

    public function setTotalPagar(?float $totalPagar): self
    {
        $this->totalPagar = $totalPagar;

        return $this;
    }

    public function getPorcentajeAsignadoSubordinada(): ?float
    {
        return $this->porcentajeAsignadoSubordinada;
    }

    public function setPorcentajeAsignadoSubordinada(?float $porcentajeAsignadoSubordinada): self
    {
        $this->porcentajeAsignadoSubordinada = $porcentajeAsignadoSubordinada;

        return $this;
    }

    public function getUtilidadFijaFijaSubordinada(): ?bool
    {
        return $this->utilidadFijaFijaSubordinada;
    }

    public function setUtilidadFijaFijaSubordinada(?bool $utilidadFijaFijaSubordinada): self
    {
        $this->utilidadFijaFijaSubordinada = $utilidadFijaFijaSubordinada;

        return $this;
    }

    public function getTotalPagarSubordinada(): ?float
    {
        return $this->totalPagarSubordinada;
    }

    public function setTotalPagarSubordinada(?float $totalPagarSubordinada): self
    {
        $this->totalPagarSubordinada = $totalPagarSubordinada;

        return $this;
    }

    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(?\DateTimeInterface $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getUpdated(): ?\DateTimeInterface
    {
        return $this->updated;
    }

    public function setUpdated(?\DateTimeInterface $updated): self
    {
        $this->updated = $updated;

        return $this;
    }

    public function getFactura(): ?TnFactura
    {
        return $this->factura;
    }

    public function setFactura(?TnFactura $factura): self
    {
        $this->factura = $factura;

        return $this;
    }

    public function getApoderado(): ?TnApoderado
    {
        return $this->apoderado;
    }

    public function setApoderado(?TnApoderado $apoderado): self
    {
        $this->apoderado = $apoderado;

        return $this;
    }

    public function getUtilidad(): ?float
    {
        return $this->utilidad;
    }

    public function setUtilidad(?float $utilidad): self
    {
        $this->utilidad = $utilidad;

        return $this;
    }

    public function getTipoPorcentaje(): ?string
    {
        return $this->tipoPorcentaje;
    }

    public function setTipoPorcentaje(?string $tipoPorcentaje): self
    {
        $this->tipoPorcentaje = $tipoPorcentaje;

        return $this;
    }

    public function getAgencia(): ?TnAgencia
    {
        return $this->agencia;
    }

    public function setAgencia(?TnAgencia $agencia): self
    {
        $this->agencia = $agencia;

        return $this;
    }
}
