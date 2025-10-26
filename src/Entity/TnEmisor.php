<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Gedmo\Loggable()
 * @ORM\Entity(repositoryClass="App\Repository\TnEmisorRepository")
 */
class TnEmisor
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
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $seudonimo;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $phone;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $dir_line1;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $dir_line2;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $dir_line3;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $city;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $state;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $zip;

    /**
     * @Assert\NotBlank()
     * @Assert\NotNull()
     * @ORM\Column(type="string", length=255)
     */
    private $country;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $token;

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
     * @ORM\OneToMany(targetEntity="App\Entity\TnDestinatario", mappedBy="emisor", cascade={"persist","remove"})
     */
    private $destinatarios;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TnFactura", mappedBy="emisor", cascade={"persist","remove"})
     */
    private $facturas;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\TnUser", inversedBy="emisores")
     * @ORM\JoinColumn(name="usuario_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private $usuario;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TnDocumento", mappedBy="emisor")
     */
    private $documentos;

    public function __construct()
    {
        $this->destinatarios = new ArrayCollection();
        $this->facturas = new ArrayCollection();
        $this->documentos = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): self
    {
        $this->nombre = $nombre;

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

    public function getDirLine1(): ?string
    {
        return $this->dir_line1;
    }

    public function setDirLine1(string $dir_line1): self
    {
        $this->dir_line1 = $dir_line1;

        return $this;
    }

    public function getDirLine2(): ?string
    {
        return $this->dir_line2;
    }

    public function setDirLine2(?string $dir_line2): self
    {
        $this->dir_line2 = $dir_line2;

        return $this;
    }

    public function getDirLine3(): ?string
    {
        return $this->dir_line3;
    }

    public function setDirLine3(?string $dir_line3): self
    {
        $this->dir_line3 = $dir_line3;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(string $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function getZip(): ?string
    {
        return $this->zip;
    }

    public function setZip(string $zip): self
    {
        $this->zip = $zip;

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
            $destinatario->setEmisor($this);
        }

        return $this;
    }

    public function removeDestinatario(TnDestinatario $destinatario): self
    {
        if ($this->destinatarios->contains($destinatario)) {
            $this->destinatarios->removeElement($destinatario);
            // set the owning side to null (unless already changed)
            if ($destinatario->getEmisor() === $this) {
                $destinatario->setEmisor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|TnFactura[]
     */
    public function getFacturas(): Collection
    {
        return $this->facturas;
    }

    public function addFactura(TnFactura $factura): self
    {
        if (!$this->facturas->contains($factura)) {
            $this->facturas[] = $factura;
            $factura->setEmisor($this);
        }

        return $this;
    }

    public function removeFactura(TnFactura $factura): self
    {
        if ($this->facturas->contains($factura)) {
            $this->facturas->removeElement($factura);
            // set the owning side to null (unless already changed)
            if ($factura->getEmisor() === $this) {
                $factura->setEmisor(null);
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
        return $this->nombre . " " . $this->apellidos . " - " . $this->phone;
    }

    public function showData()
    {
        return $this->nombre . " " . $this->apellidos;
    }

    public function descFactura()
    {
        if (!is_null($this->seudonimo) && $this->seudonimo != '') {
            return $this->seudonimo;
        } else {
            return $this->nombre . " " . $this->apellidos;
        }
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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return Collection|TnDocumento[]
     */
    public function getDocumentos(): Collection
    {
        return $this->documentos;
    }

    public function addDocumento(TnDocumento $documento): self
    {
        if (!$this->documentos->contains($documento)) {
            $this->documentos[] = $documento;
            $documento->setEmisor($this);
        }

        return $this;
    }

    public function removeDocumento(TnDocumento $documento): self
    {
        if ($this->documentos->contains($documento)) {
            $this->documentos->removeElement($documento);
            // set the owning side to null (unless already changed)
            if ($documento->getEmisor() === $this) {
                $documento->setEmisor(null);
            }
        }

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

    public function getSeudonimo(): ?string
    {
        return $this->seudonimo;
    }

    public function setSeudonimo(?string $seudonimo): self
    {
        $this->seudonimo = $seudonimo;

        return $this;
    }
}
