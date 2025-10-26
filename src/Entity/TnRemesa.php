<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * @ORM\Table(name="tn_remesa")
 * @Gedmo\Loggable()
 * @ORM\Entity(repositoryClass="App\Repository\TnRemesaRepository")
 *
 */
class TnRemesa
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Gedmo\Versioned()
     * @ORM\Column(type="float")
     */
    private $total_pagar;

    /**
     * @Gedmo\Versioned()
     * @ORM\Column(type="float")
     */
    private $importe_entregar;

    /**
     * @Gedmo\Versioned()
     *
     * @ORM\Column(type="boolean")
     */
    private $entregada;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $evidencia;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\TnDistribuidor", inversedBy="remesas")
     */
    private $distribuidor;

    /**
     * @Assert\NotNull()
     * @Assert\NotBlank()
     * @ORM\ManyToOne(targetEntity="App\Entity\NmMoneda", inversedBy="remesas")
     */
    private $moneda;

    /**
     * @Gedmo\Versioned()
     * @Assert\NotNull()
     * @Assert\NotBlank()
     * @ORM\ManyToOne(targetEntity="App\Entity\TnDestinatario", inversedBy="remesas")
     */
    private $destinatario;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\TnFactura", inversedBy="remesas")
     */
    private $factura;

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
     * @ORM\ManyToOne(targetEntity="App\Entity\TnReporteEnvio", inversedBy="remesas")
     */
    private $reporteEnvio;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\TnHistorialDistribuidor", inversedBy="remesa")
     * 
     */
    private $historialDistribuidor;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\TnSubDistribuidor", inversedBy="remesas")
     */
    private $subDistribuidor;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTotalPagar(): ?float
    {
        return $this->total_pagar;
    }

    public function setTotalPagar(float $total_pagar): self
    {
        $this->total_pagar = $total_pagar;

        return $this;
    }

    public function getImporteEntregar(): ?float
    {
        return $this->importe_entregar;
    }

    public function setImporteEntregar(float $importe_entregar): self
    {
        $this->importe_entregar = $importe_entregar;

        return $this;
    }

    public function getEntregada(): ?bool
    {
        return $this->entregada;
    }

    public function setEntregada(bool $entregada): self
    {
        $this->entregada = $entregada;

        return $this;
    }

    public function getEvidencia(): ?string
    {
        return $this->evidencia;
    }

    public function setEvidencia(string $evidencia): self
    {
        $this->evidencia = $evidencia;

        return $this;
    }

    public function getDistribuidor(): ?TnDistribuidor
    {
        return $this->distribuidor;
    }

    public function setDistribuidor(?TnDistribuidor $distribuidor): self
    {
        $this->distribuidor = $distribuidor;

        return $this;
    }

    public function getMoneda(): ?NmMoneda
    {
        return $this->moneda;
    }

    public function setMoneda(?NmMoneda $moneda): self
    {
        $this->moneda = $moneda;

        return $this;
    }

    public function getDestinatario(): ?TnDestinatario
    {
        return $this->destinatario;
    }

    public function setDestinatario(?TnDestinatario $destinatario): self
    {
        $this->destinatario = $destinatario;

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

    public function __toString()
    {
        return "" . $this->destinatario;
    }

    public function getReporteEnvio(): ?TnReporteEnvio
    {
        return $this->reporteEnvio;
    }

    public function setReporteEnvio(?TnReporteEnvio $reporteEnvio): self
    {
        $this->reporteEnvio = $reporteEnvio;

        return $this;
    }

    public function getHistorialDistribuidor(): ?TnHistorialDistribuidor
    {
        return $this->historialDistribuidor;
    }

    public function setHistorialDistribuidor(?TnHistorialDistribuidor $historialDistribuidor): self
    {
        $this->historialDistribuidor = $historialDistribuidor;

        return $this;
    }

    public function getSubDistribuidor(): ?TnSubDistribuidor
    {
        return $this->subDistribuidor;
    }

    public function setSubDistribuidor(?TnSubDistribuidor $subDistribuidor): self
    {
        $this->subDistribuidor = $subDistribuidor;

        return $this;
    }
}
