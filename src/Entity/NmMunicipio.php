<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\NmMunicipioRepository")
 * @Gedmo\Loggable()
 * @UniqueEntity(fields={"name","provincia"})
 */
class NmMunicipio
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=50, nullable=false)
     * @Assert\Length(min=4,max=50)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $referencia;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\NmProvincia", inversedBy="municipios")
     * @ORM\JoinColumn(nullable=false)
     * @Assert\NotNull()
     */
    private $provincia;

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
     * @Gedmo\Versioned()
     * @ORM\Column(type="integer", nullable=true)
     */
    private $tasaFija;

    /**
     * @Gedmo\Versioned()
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $codigo;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TnDistribuidor", mappedBy="municipio", orphanRemoval=true, cascade={"persist","remove"})
     */
    private $distribuidores;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TnDestinatario", mappedBy="municipio", orphanRemoval=true, cascade={"persist","remove"})
     */
    private $destinatarios;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\TnDistribuidor", mappedBy="zonas")
     */
    protected $jefes;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\NmMoneda", inversedBy="municipios")
     * @ORM\JoinTable(name="tn_municipio_moneda",
     *      joinColumns={@ORM\JoinColumn(name="municipio_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="moneda_id", referencedColumnName="id")}
     *      )
     */
    protected $monedasEntrega;

    public function __construct()
    {
        $this->distribuidores = new ArrayCollection();
        $this->destinatarios = new ArrayCollection();
        $this->jefes = new ArrayCollection();
        $this->monedasEntrega = new ArrayCollection();
    }


    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getName();
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

    public function getProvincia(): ?NmProvincia
    {
        return $this->provincia;
    }

    public function setProvincia(?NmProvincia $provincia): self
    {
        $this->provincia = $provincia;

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
            $distribuidore->setMunicipio($this);
        }

        return $this;
    }

    public function removeDistribuidore(TnDistribuidor $distribuidore): self
    {
        if ($this->distribuidores->contains($distribuidore)) {
            $this->distribuidores->removeElement($distribuidore);
            // set the owning side to null (unless already changed)
            if ($distribuidore->getMunicipio() === $this) {
                $distribuidore->setMunicipio(null);
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
            $destinatario->setMunicipio($this);
        }

        return $this;
    }

    public function removeDestinatario(TnDestinatario $destinatario): self
    {
        if ($this->destinatarios->contains($destinatario)) {
            $this->destinatarios->removeElement($destinatario);
            // set the owning side to null (unless already changed)
            if ($destinatario->getMunicipio() === $this) {
                $destinatario->setMunicipio(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|TnDistribuidor[]
     */
    public function getJefes(): Collection
    {
        return $this->jefes;
    }

    public function addJefe(TnDistribuidor $jefe): self
    {
        if (!$this->jefes->contains($jefe)) {
            $this->jefes[] = $jefe;
            $jefe->addZona($this);
        }

        return $this;
    }

    public function removeJefe(TnDistribuidor $jefe): self
    {
        if ($this->jefes->contains($jefe)) {
            $this->jefes->removeElement($jefe);
            $jefe->removeZona($this);
        }

        return $this;
    }

    public function getTasaFija(): ?int
    {
        return $this->tasaFija;
    }

    public function setTasaFija(?int $tasaFija): self
    {
        $this->tasaFija = $tasaFija;

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

    /**
     * @return Collection|NmMoneda[]
     */
    public function getMonedasEntrega(): Collection
    {
        return $this->monedasEntrega;
    }

    public function addMonedasEntrega(NmMoneda $monedasEntrega): self
    {
        if (!$this->monedasEntrega->contains($monedasEntrega)) {
            $this->monedasEntrega[] = $monedasEntrega;
        }

        return $this;
    }

    public function removeMonedasEntrega(NmMoneda $monedasEntrega): self
    {
        if ($this->monedasEntrega->contains($monedasEntrega)) {
            $this->monedasEntrega->removeElement($monedasEntrega);
        }

        return $this;
    }
}
