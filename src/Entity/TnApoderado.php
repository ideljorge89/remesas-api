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
 * @ORM\Entity(repositoryClass="App\Repository\TnApoderadoRepository")
 */
class TnApoderado
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $nombre;

    /**
     * @Assert\NotNull()
     * @ORM\OneToOne(targetEntity="App\Entity\TnAgencia", inversedBy="apoderado")
     */
    private $agencia;

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
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $token;

    /**
     * @Assert\Count(
     *   min = "2",
     *   minMessage = "Debe registrar al menos 2 agencias, revise.",
     * )
     * @ORM\ManyToMany(targetEntity="App\Entity\TnAgencia", inversedBy="agenciaApodederados")
     * @ORM\JoinTable(name="tn_apoderado_agencia",
     *      joinColumns={@ORM\JoinColumn(name="apoderado_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="agencia_id", referencedColumnName="id")}
     *      )
     */
    protected $subordinadas;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TnApoderadoFactura", mappedBy="apoderado", cascade={"persist","remove"})
     */
    private $apoderadoFacturas;

    public function __construct()
    {
        $this->subordinadas = new ArrayCollection();
        $this->apoderadoFacturas = new ArrayCollection();
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

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): self
    {
        $this->token = $token;

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

    /**
     * @return Collection|TnAgencia[]
     */
    public function getSubordinadas(): Collection
    {
        return $this->subordinadas;
    }

    public function addSubordinada(TnAgencia $subordinada): self
    {
        if (!$this->subordinadas->contains($subordinada)) {
            $this->subordinadas[] = $subordinada;
            $subordinada->addAgenciaApodederado($this);
        }

        return $this;
    }

    public function removeSubordinada(TnAgencia $subordinada): self
    {
        if ($this->subordinadas->contains($subordinada)) {
            $this->subordinadas->removeElement($subordinada);
            $subordinada->removeAgenciaApodederado($this);
        }

        return $this;
    }

    /**
     * @return Collection|TnApoderadoFactura[]
     */
    public function getApoderadoFacturas(): Collection
    {
        return $this->apoderadoFacturas;
    }

    public function addApoderadoFactura(TnApoderadoFactura $apoderadoFactura): self
    {
        if (!$this->apoderadoFacturas->contains($apoderadoFactura)) {
            $this->apoderadoFacturas[] = $apoderadoFactura;
            $apoderadoFactura->setApoderado($this);
        }

        return $this;
    }

    public function removeApoderadoFactura(TnApoderadoFactura $apoderadoFactura): self
    {
        if ($this->apoderadoFacturas->contains($apoderadoFactura)) {
            $this->apoderadoFacturas->removeElement($apoderadoFactura);
            // set the owning side to null (unless already changed)
            if ($apoderadoFactura->getApoderado() === $this) {
                $apoderadoFactura->setApoderado(null);
            }
        }

        return $this;
    }
}
