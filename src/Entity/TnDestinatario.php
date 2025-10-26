<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * @ORM\Entity(repositoryClass="App\Repository\TnDestinatarioRepository")
 */
class TnDestinatario
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $nombre;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $apellidos;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $phone;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $alias;

    /**
     * @ORM\Column(type="string", length=12, nullable=true)
     */
    private $ci;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $direccion;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $direccion1;

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
    private $nombre_alternativo;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $apellidos_alternativo;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $country;

    /**
     * @ORM\Column(type="string", length=12, nullable=true)
     */
    private $ci_alternativo;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $phone_alternativo;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $token;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\NmMunicipio", inversedBy="destinatarios")
     * @ORM\JoinColumn(nullable=false)
     * @Assert\NotNull()
     */
    private $municipio;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\NmProvincia", inversedBy="destinatarios")
     * @ORM\JoinColumn(nullable=false)
     * @Assert\NotNull()
     */
    private $provincia;

    /**
     * @Assert\NotNull()
     * @ORM\ManyToOne(targetEntity="App\Entity\TnEmisor", inversedBy="destinatarios")
     */
    private $emisor;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TnRemesa", mappedBy="destinatario")
     */
    private $remesas;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\TnUser", inversedBy="destinatarios")
     * @ORM\JoinColumn(name="usuario_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private $usuario;


    public function __construct()
    {
        $this->remesas = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCi(): ?string
    {
        return $this->ci;
    }

    public function setCi(string $ci): self
    {
        $this->ci = $ci;

        return $this;
    }

    public function getNombreAlternativo(): ?string
    {
        return $this->nombre_alternativo;
    }

    public function setNombreAlternativo(?string $nombre_alternativo): self
    {
        $this->nombre_alternativo = $nombre_alternativo;

        return $this;
    }

    public function getApellidosAlternativo(): ?string
    {
        return $this->apellidos_alternativo;
    }

    public function setApellidosAlternativo(?string $apellidos_alternativo): self
    {
        $this->apellidos_alternativo = $apellidos_alternativo;

        return $this;
    }

    public function getCiAlternativo(): ?string
    {
        return $this->ci_alternativo;
    }

    public function setCiAlternativo(?string $ci_alternativo): self
    {
        $this->ci_alternativo = $ci_alternativo;

        return $this;
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

    public function getApellidos(): ?string
    {
        return $this->apellidos;
    }

    public function setApellidos(string $apellidos): self
    {
        $this->apellidos = $apellidos;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function setAlias(string $alias): self
    {
        $this->alias = $alias;

        return $this;
    }

    public function getMunicipio(): ?NmMunicipio
    {
        return $this->municipio;
    }

    public function setMunicipio(?NmMunicipio $municipio): self
    {
        $this->municipio = $municipio;

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

    public function getEmisor(): ?TnEmisor
    {
        return $this->emisor;
    }

    public function setEmisor(?TnEmisor $emisor): self
    {
        $this->emisor = $emisor;

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
            $remesa->setDestinatario($this);
        }

        return $this;
    }

    public function removeRemesa(TnRemesa $remesa): self
    {
        if ($this->remesas->contains($remesa)) {
            $this->remesas->removeElement($remesa);
            // set the owning side to null (unless already changed)
            if ($remesa->getDestinatario() === $this) {
                $remesa->setDestinatario(null);
            }
        }

        return $this;
    }

    public function getDireccion(): ?string
    {
        return $this->direccion;
    }

    public function setDireccion(string $direccion): self
    {
        $this->direccion = $direccion;

        return $this;
    }

    public function getDireccion1(): ?string
    {
        return $this->direccion1;
    }

    public function setDireccion1(?string $direccion1): self
    {
        $this->direccion1 = $direccion1;

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

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function __toString()
    {
       return $this->nombre." ".$this->apellidos;
    }

    public function getPhoneAlternativo(): ?string
    {
        return $this->phone_alternativo;
    }

    public function setPhoneAlternativo(string $phone_alternativo): self
    {
        $this->phone_alternativo = $phone_alternativo;

        return $this;
    }

    public function getDireccionEntrega(){
        return $this->direccion." ".$this->direccion1.". ".$this->municipio.", ".$this->provincia;
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

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): self
    {
        $this->token = $token;

        return $this;
    }
}
