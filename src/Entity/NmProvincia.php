<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\NmProvinciaRepository")
 * @Gedmo\Loggable()
 * @UniqueEntity(fields={"name"})
 */
class NmProvincia
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=50,unique=true, nullable=false)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $referencia;

    /**
     * @ORM\Column(type="string", length=5,unique=true, nullable=false)
     */
    private $acronimo;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $latitud;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $longitud;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $mapZoom;

    /**
     * @Gedmo\Versioned()
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $codigo;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\NmMunicipio", mappedBy="provincia", orphanRemoval=true, cascade={"persist","remove"})
     */
    private $municipios;


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
     * @ORM\OneToMany(targetEntity="App\Entity\TnDistribuidor", mappedBy="provincia", orphanRemoval=true, cascade={"persist","remove"})
     */
    private $distribuidores;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TnDestinatario", mappedBy="provincia", orphanRemoval=true, cascade={"persist","remove"})
     */
    private $destinatarios;

    public function __construct()
    {
        $this->municipios = new ArrayCollection();
        $this->distribuidores = new ArrayCollection();
        $this->destinatarios = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection|NmMunicipio[]
     */
    public function getMunicipios(): Collection
    {
        return $this->municipios;
    }

    public function addMunicipio(NmMunicipio $municipio): self
    {
        if (!$this->municipios->contains($municipio)) {
            $this->municipios[] = $municipio;
            $municipio->setProvincia($this);
        }

        return $this;
    }

    public function removeMunicipio(NmMunicipio $municipio): self
    {
        if ($this->municipios->contains($municipio)) {
            $this->municipios->removeElement($municipio);
            // set the owning side to null (unless already changed)
            if ($municipio->getProvincia() === $this) {
                $municipio->setProvincia(null);
            }
        }

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
        return $this->getName();
    }

    public function getAcronimo(): ?string
    {
        return $this->acronimo;
    }

    public function setAcronimo(string $acronimo): self
    {
        $this->acronimo = $acronimo;

        return $this;
    }

    public function getLatitud(): ?float
    {
        return $this->latitud;
    }

    public function setLatitud(?float $latitud): self
    {
        $this->latitud = $latitud;

        return $this;
    }

    public function getLongitud(): ?float
    {
        return $this->longitud;
    }

    public function setLongitud(?float $longitud): self
    {
        $this->longitud = $longitud;

        return $this;
    }

    public function getMapZoom(): ?int
    {
        return $this->mapZoom;
    }

    public function setMapZoom(?int $mapZoom): self
    {
        $this->mapZoom = $mapZoom;

        return $this;
    }

    /**
     * @return Collection|TnDistribuidor[]
     */
    public function getDistribuidores(): Collection
    {
        return $this->distribuidores;
    }

    public function addDistribuidore(TnDistribuidor $distribuidore): self
    {
        if (!$this->distribuidores->contains($distribuidore)) {
            $this->distribuidores[] = $distribuidore;
            $distribuidore->setProvincia($this);
        }

        return $this;
    }

    public function removeDistribuidore(TnDistribuidor $distribuidore): self
    {
        if ($this->distribuidores->contains($distribuidore)) {
            $this->distribuidores->removeElement($distribuidore);
            // set the owning side to null (unless already changed)
            if ($distribuidore->getProvincia() === $this) {
                $distribuidore->setProvincia(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|TnDestinatario[]
     */
    public function getDestinatarios(): Collection
    {
        return $this->destinatarios;
    }

    public function addDestinatario(TnDestinatario $destinatario): self
    {
        if (!$this->destinatarios->contains($destinatario)) {
            $this->destinatarios[] = $destinatario;
            $destinatario->setProvincia($this);
        }

        return $this;
    }

    public function removeDestinatario(TnDestinatario $destinatario): self
    {
        if ($this->destinatarios->contains($destinatario)) {
            $this->destinatarios->removeElement($destinatario);
            // set the owning side to null (unless already changed)
            if ($destinatario->getProvincia() === $this) {
                $destinatario->setProvincia(null);
            }
        }

        return $this;
    }

    public function getCodigo(): ?string
    {
        return $this->codigo;
    }

    public function setCodigo(?string $codigo): self
    {
        $this->codigo = $codigo;

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
}
