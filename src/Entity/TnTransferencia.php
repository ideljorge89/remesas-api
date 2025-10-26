<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Gedmo\Loggable()
 * @ORM\Entity(repositoryClass="App\Repository\TnTransferenciaRepository")
 */
class TnTransferencia
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=10)
     */
    private $codigo;

    /**
     * @Gedmo\Versioned()
     * @ORM\Column(type="string", length=19)
     */
    private $numeroTarjeta;

    /**
     * @Gedmo\Versioned()
     *
     * @ORM\Column(type="float")
     */
    private $monto;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $titularTarjeta;

    /**
     * @Assert\NotBlank()
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $phone;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $emisor;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $notas;

    /**
     * @Gedmo\Versioned()
     *
     * @ORM\Column(type="float")
     */
    private $totalCobrar;

    /**
     * @Gedmo\Versioned()
     *
     * @ORM\Column(type="float")
     */
    private $importe;

    /**
     * @ORM\Column(type="boolean")
     */
    private $auth;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $referencia;

    /**
     * @Gedmo\Versioned()
     * @ORM\Column(type="integer", nullable=true)
     */
    private $comision;

    /**
     * @var string
     *
     * @ORM\Column(name="retenida", type="boolean", nullable=true)
     */
    protected $retenida;

    /**
     * @Gedmo\Versioned()
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\TnAgente", inversedBy="transferencias")
     */
    private $agente;

    /**
     * @Gedmo\Versioned()
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\TnAgencia", inversedBy="transferencias")
     */
    private $agencia;

    /**
     * @Gedmo\Versioned()
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\NmMoneda", inversedBy="transferencias")
     */
    private $moneda;

    /**
     * @Gedmo\Versioned()
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\NmEstadoTransferencia", inversedBy="transferencias")
     */
    private $estado;

    /**
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\TnReporteTransferencia", inversedBy="transferencias")
     */
    private $reporteTransferencia;

    /**
     * @var \DateTime|null
     * @ORM\Column(name="created",type="datetime",nullable=true)
     * @Gedmo\Timestampable(on="create")
     */
    private $created;

    /**
     * @var \DateTime|null
     * @ORM\Column(name="fecha_contable",type="datetime",nullable=true)
     * @Gedmo\Timestampable(on="create")
     */
    private $fechaContable;

    /**
     * @var \DateTime|null
     * @ORM\Column(name="updated",type="datetime",nullable=true)
     * @Gedmo\Timestampable(on="update")
     */
    private $updated;

    /**
     * @var \DateTime $asignadaAt
     *
     * @ORM\Column(name="asignada_at",type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="change", field="estado.codigo", value="02")
     */
    private $asignadaAt;

    /**
     * @var \DateTime $enviadaAt
     *
     * @ORM\Column(name="enviada_at",type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="change", field="estado.codigo", value="03")
     */
    private $enviadaAt;

    /**
     * @var \DateTime $entregadaAt
     *
     * @ORM\Column(name="entregada_at",type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="change", field="estado.codigo", value="04")
     */
    private $entregadaAt;

    /**
     * @var \DateTime $canceladaAt
     *
     * @ORM\Column(name="cancelada_at",type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="change", field="estado.codigo", value="05")
     */
    private $canceladaAt;

    /**
     *
     * @ORM\Column(type="float", nullable=true)
     */
    private $porcentajeOpera;

    /**
     *
     * @ORM\Column(type="float", nullable=true)
     */
    private $porcentajeOperaAgente;

    /**
     * @Gedmo\Versioned()
     *
     * @ORM\Column(type="float", nullable=true)
     */
    private $porcentajeAsignado;

    /**
     * @Gedmo\Versioned()
     *
     * @ORM\Column(type="float", nullable=true)
     */
    private $porcentajeAsignadoAgente;

    /**
     * @Gedmo\Versioned()
     *
     * @ORM\Column(type="float", nullable=true)
     */
    private $totalPagar;

    /**
     * @Gedmo\Versioned()
     *
     * @ORM\Column(type="float", nullable=true)
     */
    private $totalPagarAgente;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $token;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $evidencia;

    /**
     * @Gedmo\Versioned()
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\TnRepartidor", inversedBy="transferencias")
     */
    private $repartidor;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumeroTarjeta(): ?string
    {
        return $this->numeroTarjeta;
    }

    public function setNumeroTarjeta(string $numeroTarjeta): self
    {
        $this->numeroTarjeta = $numeroTarjeta;

        return $this;
    }

    public function getTitularTarjeta(): ?string
    {
        return $this->titularTarjeta;
    }

    public function setTitularTarjeta(string $titularTarjeta): self
    {
        $this->titularTarjeta = $titularTarjeta;

        return $this;
    }

    public function getCodigo(): ?string
    {
        return $this->codigo;
    }

    public function setCodigo(string $codigo): self
    {
        $this->codigo = $codigo;

        return $this;
    }

    public function getMonto(): ?float
    {
        return $this->monto;
    }

    public function setMonto(float $monto): self
    {
        $this->monto = $monto;

        return $this;
    }

    public function getNotas(): ?string
    {
        return $this->notas;
    }

    public function setNotas(?string $notas): self
    {
        $this->notas = $notas;

        return $this;
    }

    public function getImporte(): ?float
    {
        return $this->importe;
    }

    public function setImporte(float $importe): self
    {
        $this->importe = $importe;

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

    public function getFechaContable(): ?\DateTimeInterface
    {
        return $this->fechaContable;
    }

    public function setFechaContable(?\DateTimeInterface $fechaContable): self
    {
        $this->fechaContable = $fechaContable;

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

    public function getEnviadaAt(): ?\DateTimeInterface
    {
        return $this->enviadaAt;
    }

    public function setEnviadaAt(?\DateTimeInterface $enviadaAt): self
    {
        $this->enviadaAt = $enviadaAt;

        return $this;
    }

    public function getCanceladaAt(): ?\DateTimeInterface
    {
        return $this->canceladaAt;
    }

    public function setCanceladaAt(?\DateTimeInterface $canceladaAt): self
    {
        $this->canceladaAt = $canceladaAt;

        return $this;
    }

    public function getPorcentajeOpera(): ?float
    {
        return $this->porcentajeOpera;
    }

    public function setPorcentajeOpera(?float $porcentajeOpera): self
    {
        $this->porcentajeOpera = $porcentajeOpera;

        return $this;
    }

    public function getPorcentajeOperaAgente(): ?float
    {
        return $this->porcentajeOperaAgente;
    }

    public function setPorcentajeOperaAgente(?float $porcentajeOperaAgente): self
    {
        $this->porcentajeOperaAgente = $porcentajeOperaAgente;

        return $this;
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

    public function getPorcentajeAsignadoAgente(): ?float
    {
        return $this->porcentajeAsignadoAgente;
    }

    public function setPorcentajeAsignadoAgente(?float $porcentajeAsignadoAgente): self
    {
        $this->porcentajeAsignadoAgente = $porcentajeAsignadoAgente;

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

    public function getTotalPagarAgente(): ?float
    {
        return $this->totalPagarAgente;
    }

    public function setTotalPagarAgente(?float $totalPagarAgente): self
    {
        $this->totalPagarAgente = $totalPagarAgente;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getAgente(): ?TnAgente
    {
        return $this->agente;
    }

    public function setAgente(?TnAgente $agente): self
    {
        $this->agente = $agente;

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

    public function getEstado(): ?NmEstadoTransferencia
    {
        return $this->estado;
    }

    public function setEstado(?NmEstadoTransferencia $estado): self
    {
        $this->estado = $estado;

        return $this;
    }

    public function getAuth(): ?bool
    {
        return $this->auth;
    }

    public function setAuth(bool $auth): self
    {
        $this->auth = $auth;

        return $this;
    }

    public function getTotalCobrar(): ?float
    {
        return $this->totalCobrar;
    }

    public function setTotalCobrar(float $totalCobrar): self
    {
        $this->totalCobrar = $totalCobrar;

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

    public function getReporteTransferencia(): ?TnReporteTransferencia
    {
        return $this->reporteTransferencia;
    }

    public function setReporteTransferencia(?TnReporteTransferencia $reporteTransferencia): self
    {
        $this->reporteTransferencia = $reporteTransferencia;

        return $this;
    }

    public function getEmisor(): ?string
    {
        return $this->emisor;
    }

    public function setEmisor(?string $emisor): self
    {
        $this->emisor = $emisor;

        return $this;
    }

    public function getEntregadaAt(): ?\DateTimeInterface
    {
        return $this->entregadaAt;
    }

    public function setEntregadaAt(?\DateTimeInterface $entregadaAt): self
    {
        $this->entregadaAt = $entregadaAt;

        return $this;
    }

    public function getComision(): ?int
    {
        return $this->comision;
    }

    public function setComision(?int $comision): self
    {
        $this->comision = $comision;

        return $this;
    }

    public function getEvidencia(): ?string
    {
        return $this->evidencia;
    }

    public function setEvidencia(?string $evidencia): self
    {
        $this->evidencia = $evidencia;

        return $this;
    }

    public function getRepartidor(): ?TnRepartidor
    {
        return $this->repartidor;
    }

    public function setRepartidor(?TnRepartidor $repartidor): self
    {
        $this->repartidor = $repartidor;

        return $this;
    }

    public function getAsignadaAt(): ?\DateTimeInterface
    {
        return $this->asignadaAt;
    }

    public function setAsignadaAt(?\DateTimeInterface $asignadaAt): self
    {
        $this->asignadaAt = $asignadaAt;

        return $this;
    }

    public function getReferencia(): ?string
    {
        return $this->referencia;
    }

    public function setReferencia(?string $referencia): self
    {
        $this->referencia = $referencia;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getRetenida(): ?bool
    {
        return $this->retenida;
    }

    public function setRetenida(?bool $retenida): self
    {
        $this->retenida = $retenida;

        return $this;
    }
}
