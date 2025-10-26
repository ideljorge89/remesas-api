<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @UniqueEntity({"nombre"})
 * @Gedmo\Loggable()
 * @ORM\Entity(repositoryClass="App\Repository\NmGrupoPagoRepository")
 */
class NmGrupoPago
{
    const TIPO_FIJA = 'fija';
    const TIPO_PORCIENTO = 'porciento';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Gedmo\Versioned()
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $nombre;

    /**
     * @Assert\Length(
     *   min = 1,
     *   max = 5,
     * )
     * @Gedmo\Versioned()
     *
     * @ORM\Column(type="float")
     */
    private $porcentaje;

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
     * @ORM\ManyToMany(targetEntity="App\Entity\TnAgencia", mappedBy="gruposPago")
     */
    protected $agencias;

    /**
     * @Assert\NotNull()
     * @ORM\ManyToOne(targetEntity="App\Entity\NmMoneda", inversedBy="gruposPagoMoneda")
     */
    private $moneda;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TnSaldoAgencia", mappedBy="grupoPago")
     */
    private $saldoMonedas;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TnOperacionAgencia", mappedBy="grupoPago")
     */
    private $porcentajes;


    public function __construct()
    {
        $this->agencias = new ArrayCollection();
        $this->saldoMonedas = new ArrayCollection();
        $this->porcentajes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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
        return $this->nombre . " - (" . $this->porcentaje . " %)" . ($this->moneda ? ' - ' . $this->moneda->getSimbolo() : '');
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

    public static function getAllTipos()
    {
        return [
            "Fija" => 'fija',
            "Porciento" => 'porciento'
        ];
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

    /**
     * @return Collection|TnAgencia[]
     */
    public function getAgencias(): Collection
    {
        return $this->agencias;
    }

    public function addAgencia(TnAgencia $agencia): self
    {
        if (!$this->agencias->contains($agencia)) {
            $this->agencias[] = $agencia;
            $agencia->addGruposPago($this);
        }

        return $this;
    }

    public function removeAgencia(TnAgencia $agencia): self
    {
        if ($this->agencias->contains($agencia)) {
            $this->agencias->removeElement($agencia);
            $agencia->removeGruposPago($this);
        }

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

    /**
     * @return Collection|TnSaldoAgencia[]
     */
    public function getSaldoMonedas(): Collection
    {
        return $this->saldoMonedas;
    }

    public function addSaldoMoneda(TnSaldoAgencia $saldoMoneda): self
    {
        if (!$this->saldoMonedas->contains($saldoMoneda)) {
            $this->saldoMonedas[] = $saldoMoneda;
            $saldoMoneda->setGrupoPago($this);
        }

        return $this;
    }

    public function removeSaldoMoneda(TnSaldoAgencia $saldoMoneda): self
    {
        if ($this->saldoMonedas->contains($saldoMoneda)) {
            $this->saldoMonedas->removeElement($saldoMoneda);
            // set the owning side to null (unless already changed)
            if ($saldoMoneda->getGrupoPago() === $this) {
                $saldoMoneda->setGrupoPago(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|TnOperacionAgencia[]
     */
    public function getPorcentajes(): Collection
    {
        return $this->porcentajes;
    }

    public function addPorcentaje(TnOperacionAgencia $porcentaje): self
    {
        if (!$this->porcentajes->contains($porcentaje)) {
            $this->porcentajes[] = $porcentaje;
            $porcentaje->setGrupoPago($this);
        }

        return $this;
    }

    public function removePorcentaje(TnOperacionAgencia $porcentaje): self
    {
        if ($this->porcentajes->contains($porcentaje)) {
            $this->porcentajes->removeElement($porcentaje);
            // set the owning side to null (unless already changed)
            if ($porcentaje->getGrupoPago() === $this) {
                $porcentaje->setGrupoPago(null);
            }
        }

        return $this;
    }
}
