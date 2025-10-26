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
 * @ORM\Entity(repositoryClass="App\Repository\NmGrupoPagoTransfAgenteRepository")
 */
class NmGrupoPagoTransfAgente
{
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
     * @ORM\ManyToOne(targetEntity="App\Entity\TnUser", inversedBy="gruposPagoTransfAgente")
     * @ORM\JoinColumn(name="usuario_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private $usuario;

    /**
     * @Assert\NotNull()
     * @ORM\ManyToOne(targetEntity="App\Entity\NmMoneda", inversedBy="gruposPagoTransfAgente")
     */
    private $moneda;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\TnAgente", mappedBy="gruposPagoTransferencias")
     */
    protected $agentes;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TnOperacionAgenteTransf", mappedBy="grupoPago")
     */
    private $porcentajes;

    public function __construct()
    {
        $this->agentes = new ArrayCollection();
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

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): self
    {
        $this->token = $token;

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
            $agente->addGruposPagoTransferencia($this);
        }

        return $this;
    }

    public function removeAgente(TnAgente $agente): self
    {
        if ($this->agentes->contains($agente)) {
            $this->agentes->removeElement($agente);
            $agente->removeGruposPagoTransferencia($this);
        }

        return $this;
    }

    /**
     * @return Collection|TnOperacionAgenteTransf[]
     */
    public function getPorcentajes(): Collection
    {
        return $this->porcentajes;
    }

    public function addPorcentaje(TnOperacionAgenteTransf $porcentaje): self
    {
        if (!$this->porcentajes->contains($porcentaje)) {
            $this->porcentajes[] = $porcentaje;
            $porcentaje->setGrupoPago($this);
        }

        return $this;
    }

    public function removePorcentaje(TnOperacionAgenteTransf $porcentaje): self
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

    public function getUsuario(): ?TnUser
    {
        return $this->usuario;
    }

    public function setUsuario(?TnUser $usuario): self
    {
        $this->usuario = $usuario;

        return $this;
    }

    public function __toString()
    {
        return $this->nombre . " - (" . $this->porcentaje . " %)" . ($this->moneda ? ' - ' . $this->moneda->getSimbolo() : '');
    }
}
