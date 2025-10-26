<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="tn_factura")
 *
 * @Gedmo\Loggable()
 * @ORM\Entity(repositoryClass="App\Repository\TnFacturaRepository")
 */
class TnFactura
{

    const TIPO_PORCIENTO = 'porciento';
    const TIPO_UTILIDAD = 'utilidad';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=10)
     */
    private $no_factura;

    /**
     * @ORM\Column(type="float")
     */
    private $total;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $notas;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $referencia;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $referenciaOld;

    /**
     * @Gedmo\Versioned()
     *
     * @ORM\Column(type="float")
     */
    private $importe;

    /**
     * @Gedmo\Versioned()
     * @ORM\Column(type="string", length=10)
     */
    private $moneda;

    /**
     * @Gedmo\Versioned()
     * @ORM\Column(type="float")
     */
    private $tasa;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $fecha_entrega;

    /**
     * @ORM\Column(type="boolean")
     */
    private $btnEmisor;

    /**
     * @ORM\Column(type="boolean")
     */
    private $auth;
    
    /**
     * @ORM\Column(type="boolean")
     */
    private $sospechosa;

    /**
     * @var string
     *
     * @ORM\Column(name="retenida", type="boolean", nullable=true)
     */
    protected $retenida;

    /**
     * @Gedmo\Versioned()
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\TnEmisor", inversedBy="facturas")
     */
    private $emisor;

    /**
     * @Gedmo\Versioned()
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\TnAgente", inversedBy="facturas")
     */
    private $agente;

    /**
     * @Gedmo\Versioned()
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\TnAgencia", inversedBy="facturas")
     */
    private $agencia;

    /**
     * @Gedmo\Versioned()
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\NmEstado", inversedBy="facturas")
     */
    private $estado;

    /**
     * @Assert\Count(
     *   max = "1",
     *   maxMessage = "No debe registrar más de 1 remesa en la factura, revise.",
     * )
     * @ORM\OneToMany(targetEntity="App\Entity\TnRemesa", mappedBy="factura", cascade={"persist","remove"})
     */
    private $remesas;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TnHistorial", mappedBy="factura", cascade={"persist","remove"})
     */
    private $historial;

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
     * @var \DateTime $aprobadoAt
     *
     * @ORM\Column(name="aprobado_at",type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="change", field="estado.codigo", value="02")
     */
    private $aprobadaAt;

    /**
     * @var \DateTime $distribuidaAt
     *
     * @ORM\Column(name="distribuida_at",type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="change", field="estado.codigo", value="03")
     */
    private $distribuidaAt;

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
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $utilidadFija;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $utilidadFijaAgente;

    /**
     * @Gedmo\Versioned()
     *
     * @ORM\Column(type="float", nullable=true)
     */
    private $porcentajeAsignadoAgente;

    /**
     * @Gedmo\Versioned()
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $tipoPorcentaje;

    /**
     * @Gedmo\Versioned()
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $tipoPorcentajeAgente;

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
     * @ORM\OneToOne(targetEntity="App\Entity\TnApoderadoFactura", mappedBy="factura")
     */
    private $apoderadoFactura;

    public function __construct()
    {
        $this->remesas = new ArrayCollection();
        $this->historial = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNoFactura(): ?string
    {
        return $this->no_factura;
    }

    public function setNoFactura(string $no_factura): self
    {
        $this->no_factura = $no_factura;

        return $this;
    }

    public function getFechaEntrega(): ?\DateTimeInterface
    {
        return $this->fecha_entrega;
    }

    public function setFechaEntrega(?\DateTimeInterface $fecha_entrega): self
    {
        $this->fecha_entrega = $fecha_entrega;

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

    public function getEmisor(): ?TnEmisor
    {
        return $this->emisor;
    }

    public function setEmisor(?TnEmisor $emisor): self
    {
        $this->emisor = $emisor;

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

    public function getEstado(): ?NmEstado
    {
        return $this->estado;
    }

    public function setEstado(?NmEstado $estado): self
    {
        $this->estado = $estado;

        return $this;
    }

    /**
     * @return Collection|TnRemesa[]
     */
    public function getRemesas(): Collection
    {
        return $this->remesas;
    }

    public function addRemesa(TnRemesa $remesa): self
    {
        if (!$this->remesas->contains($remesa)) {
            $this->remesas[] = $remesa;
            $remesa->setFactura($this);
        }

        return $this;
    }

    public function removeRemesa(TnRemesa $remesa): self
    {
        if ($this->remesas->contains($remesa)) {
            $this->remesas->removeElement($remesa);
            // set the owning side to null (unless already changed)
            if ($remesa->getFactura() === $this) {
                $remesa->setFactura(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|TnHistorial[]
     */
    public function getHistorial(): Collection
    {
        return $this->historial;
    }

    public function addHistorial(TnHistorial $historial): self
    {
        if (!$this->historial->contains($historial)) {
            $this->historial[] = $historial;
            $historial->setFactura($this);
        }

        return $this;
    }

    public function removeHistorial(TnHistorial $historial): self
    {
        if ($this->historial->contains($historial)) {
            $this->historial->removeElement($historial);
            // set the owning side to null (unless already changed)
            if ($historial->getFactura() === $this) {
                $historial->setFactura(null);
            }
        }

        return $this;
    }

    public function getTotal(): ?float
    {
        return $this->total;
    }

    public function setTotal(float $total): self
    {
        $this->total = $total;

        return $this;
    }

    public function __toString()
    {
        return "" . $this->no_factura;
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

    public function getImporte(): ?float
    {
        return $this->importe;
    }

    public function setImporte(float $importe): self
    {
        $this->importe = $importe;

        return $this;
    }

    public function getPorcentajeOpera(): ?float
    {
        return $this->porcentajeOpera;
    }

    public function setPorcentajeOpera(float $porcentajeOpera): self
    {
        $this->porcentajeOpera = $porcentajeOpera;

        return $this;
    }

    public function getPorcentajeAsignado(): ?float
    {
        return $this->porcentajeAsignado;
    }

    public function setPorcentajeAsignado(float $porcentajeAsignado): self
    {
        $this->porcentajeAsignado = $porcentajeAsignado;

        return $this;
    }

    public function getTotalPagar(): ?float
    {
        return $this->totalPagar;
    }

    public function setTotalPagar(float $totalPagar): self
    {
        $this->totalPagar = $totalPagar;

        return $this;
    }

    public function getTipoPorcentaje(): ?string
    {
        return $this->tipoPorcentaje;
    }

    public function setTipoPorcentaje(string $tipoPorcentaje): self
    {
        $this->tipoPorcentaje = $tipoPorcentaje;

        return $this;
    }

    public function getBtnEmisor(): ?bool
    {
        return $this->btnEmisor;
    }

    public function setBtnEmisor(bool $btnEmisor): self
    {
        $this->btnEmisor = $btnEmisor;

        return $this;
    }

    public function getAprobadaAt(): ?\DateTimeInterface
    {
        return $this->aprobadaAt;
    }

    public function setAprobadaAt(?\DateTimeInterface $aprobadaAt): self
    {
        $this->aprobadaAt = $aprobadaAt;

        return $this;
    }

    public function getDistribuidaAt(): ?\DateTimeInterface
    {
        return $this->distribuidaAt;
    }

    public function setDistribuidaAt(?\DateTimeInterface $distribuidaAt): self
    {
        $this->distribuidaAt = $distribuidaAt;

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

    public function getPorcentajeAsignadoAgente(): ?float
    {
        return $this->porcentajeAsignadoAgente;
    }

    public function setPorcentajeAsignadoAgente(float $porcentajeAsignadoAgente): self
    {
        $this->porcentajeAsignadoAgente = $porcentajeAsignadoAgente;

        return $this;
    }

    public function getTipoPorcentajeAgente(): ?string
    {
        return $this->tipoPorcentajeAgente;
    }

    public function setTipoPorcentajeAgente(string $tipoPorcentajeAgente): self
    {
        $this->tipoPorcentajeAgente = $tipoPorcentajeAgente;

        return $this;
    }

    public function getTotalPagarAgente(): ?float
    {
        return $this->totalPagarAgente;
    }

    public function setTotalPagarAgente(float $totalPagarAgente): self
    {
        $this->totalPagarAgente = $totalPagarAgente;

        return $this;
    }

    public function getPorcentajeOperaAgente(): ?float
    {
        return $this->porcentajeOperaAgente;
    }

    public function setPorcentajeOperaAgente(float $porcentajeOperaAgente): self
    {
        $this->porcentajeOperaAgente = $porcentajeOperaAgente;

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

    public function getUtilidadFija(): ?bool
    {
        return $this->utilidadFija;
    }

    public function setUtilidadFija(bool $utilidadFija): self
    {
        $this->utilidadFija = $utilidadFija;

        return $this;
    }

    public function getUtilidadFijaAgente(): ?bool
    {
        return $this->utilidadFijaAgente;
    }

    public function setUtilidadFijaAgente(bool $utilidadFijaAgente): self
    {
        $this->utilidadFijaAgente = $utilidadFijaAgente;

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

    public function getFechaContable(): ?\DateTimeInterface
    {
        return $this->fechaContable;
    }

    public function setFechaContable(?\DateTimeInterface $fechaContable): self
    {
        $this->fechaContable = $fechaContable;

        return $this;
    }

    public function getMoneda(): ?string
    {
        return $this->moneda;
    }

    public function setMoneda(string $moneda): self
    {
        $this->moneda = $moneda;

        return $this;
    }

    public function getTasa(): ?float
    {
        return $this->tasa;
    }

    public function setTasa(float $tasa): self
    {
        $this->tasa = $tasa;

        return $this;
    }

    public function getApoderadoFactura(): ?TnApoderadoFactura
    {
        return $this->apoderadoFactura;
    }

    public function setApoderadoFactura(?TnApoderadoFactura $apoderadoFactura): self
    {
        $this->apoderadoFactura = $apoderadoFactura;

        // set (or unset) the owning side of the relation if necessary
        $newFactura = null === $apoderadoFactura ? null : $this;
        if ($apoderadoFactura->getFactura() !== $newFactura) {
            $apoderadoFactura->setFactura($newFactura);
        }

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

    public function getSospechosa(): ?bool
    {
        return $this->sospechosa;
    }

    public function setSospechosa(bool $sospechosa): self
    {
        $this->sospechosa = $sospechosa;

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

    public function getReferenciaOld(): ?string
    {
        return $this->referenciaOld;
    }

    public function setReferenciaOld(?string $referenciaOld): self
    {
        $this->referenciaOld = $referenciaOld;

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
