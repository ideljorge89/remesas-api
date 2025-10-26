<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Gedmo\Loggable()
 * @ORM\Entity(repositoryClass="App\Repository\NmGrupoPagoAgenteRepository")
 */
class NmGrupoPagoAgente
{
    const TIPO_FIJA = 'fija';
    const TIPO_PORCIENTO = 'porciento';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @Gedmo\Versioned()
     * @ORM\Column(type="string", length=255)
     */
    private $nombre;

    /**
     * @Assert\Length(
     *   min = 1,
     *   max = 5,
     * )
     * @Gedmo\Versioned()
     * @ORM\Column(type="float", nullable=true)
     */
    private $porcentaje;

    /**
     * @Assert\Length(
     *   min = 1,
     *   max = 5,
     * )
     * @Gedmo\Versioned()
     * @ORM\Column(type="float", nullable=true)
     */
    private $utilidad;

    /**
     * @Gedmo\Versioned()
     * @ORM\Column(type="integer", nullable=true)
     */
    private $minimo;

    /**
     * @Gedmo\Versioned()
     * @ORM\Column(type="integer", nullable=true)
     */
    private $utilidadFija;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $tipoUtilidad;

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
     * @var string
     *
     * @ORM\Column(name="enabled", type="boolean", nullable=true)
     */
    protected $enabled;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $token;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\TnUser", inversedBy="gruposPagoAgente")
     * @ORM\JoinColumn(name="usuario_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private $usuario;

    /**
     * @Assert\NotNull()
     * @ORM\ManyToOne(targetEntity="App\Entity\NmMoneda", inversedBy="gruposPagoAgenteMoneda")
     */
    private $moneda;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\TnAgente", mappedBy="gruposPago")
     */
    protected $agentes;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TnSaldoAgente", mappedBy="grupoPagoAgente")
     */
    private $saldoMonedas;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TnOperacionAgente", mappedBy="grupoPagoAgente")
     */
    private $porcentajes;


    public function __construct()
    {
        $this->agentes = new ArrayCollection();
        $this->saldoMonedas = new ArrayCollection();
        $this->porcentajes = new ArrayCollection();
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): self
    {
        $this->nombre = $nombre;

        return $this;
    }

    public function getPorcentaje(): ?float
    {
        return $this->porcentaje;
    }

    public function setPorcentaje(float $porcentaje): self
    {
        $this->porcentaje = $porcentaje;

        return $this;
    }

    public function getUtilidad(): ?float
    {
        return $this->utilidad;
    }

    public function setUtilidad(float $utilidad): self
    {
        $this->utilidad = $utilidad;

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

    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(?bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function __toString()
    {
        return $this->nombre." - ".($this->utilidad ? $this->utilidad. " USD" : $this->porcentaje. " %" ). ($this->moneda ? ' - ' . $this->moneda->getSimbolo() : '');
    }

    public function getUsuario(): ?TnUser
    {
        return $this->usuario;
    }

    public function setUsuario(?TnUser $usuario): self
    {
        $this->usuario = $usuario;

        return $this;
    }

    public function getMinimo(): ?int
    {
        return $this->minimo;
    }

    public function setMinimo(?int $minimo): self
    {
        $this->minimo = $minimo;

        return $this;
    }

    public function getUtilidadFija(): ?int
    {
        return $this->utilidadFija;
    }

    public function setUtilidadFija(?int $utilidadFija): self
    {
        $this->utilidadFija = $utilidadFija;

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

    public function getTipoUtilidad(): ?string
    {
        return $this->tipoUtilidad;
    }

    public function setTipoUtilidad(?string $tipoUtilidad): self
    {
        $this->tipoUtilidad = $tipoUtilidad;

        return $this;
    }

    public static function getAllTipos()
    {
        return [
            "Fija" => 'fija',
            "Porciento" => 'porciento'
        ];
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

    /**
     * @return Collection|TnAgente[]
     */
    public function getAgentes(): Collection
    {
        return $this->agentes;
    }

    public function addAgente(TnAgente $agente): self
    {
        if (!$this->agentes->contains($agente)) {
            $this->agentes[] = $agente;
            $agente->addGruposPago($this);
        }

        return $this;
    }

    public function removeAgente(TnAgente $agente): self
    {
        if ($this->agentes->contains($agente)) {
            $this->agentes->removeElement($agente);
            $agente->removeGruposPago($this);
        }

        return $this;
    }

    /**
     * @return Collection|TnSaldoAgente[]
     */
    public function getSaldoMonedas(): Collection
    {
        return $this->saldoMonedas;
    }

    public function addSaldoMoneda(TnSaldoAgente $saldoMoneda): self
    {
        if (!$this->saldoMonedas->contains($saldoMoneda)) {
            $this->saldoMonedas[] = $saldoMoneda;
            $saldoMoneda->setGrupoPagoAgente($this);
        }

        return $this;
    }

    public function removeSaldoMoneda(TnSaldoAgente $saldoMoneda): self
    {
        if ($this->saldoMonedas->contains($saldoMoneda)) {
            $this->saldoMonedas->removeElement($saldoMoneda);
            // set the owning side to null (unless already changed)
            if ($saldoMoneda->getGrupoPagoAgente() === $this) {
                $saldoMoneda->setGrupoPagoAgente(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|TnOperacionAgente[]
     */
    public function getPorcentajes(): Collection
    {
        return $this->porcentajes;
    }

    public function addPorcentaje(TnOperacionAgente $porcentaje): self
    {
        if (!$this->porcentajes->contains($porcentaje)) {
            $this->porcentajes[] = $porcentaje;
            $porcentaje->setGrupoPagoAgente($this);
        }

        return $this;
    }

    public function removePorcentaje(TnOperacionAgente $porcentaje): self
    {
        if ($this->porcentajes->contains($porcentaje)) {
            $this->porcentajes->removeElement($porcentaje);
            // set the owning side to null (unless already changed)
            if ($porcentaje->getGrupoPagoAgente() === $this) {
                $porcentaje->setGrupoPagoAgente(null);
            }
        }

        return $this;
    }
}
