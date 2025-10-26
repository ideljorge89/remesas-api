<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @UniqueEntity({"email"})
 * @Gedmo\Loggable()
 * @ORM\Entity(repositoryClass="App\Repository\TnDistribuidorRepository")
 */
class  TnDistribuidor
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
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=12)
     */
    private $ci;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $datos_licencia;

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
    private $token;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\NmMunicipio", inversedBy="distribuidores")
     * @ORM\JoinColumn(nullable=false)
     * @Assert\NotNull()
     */
    private $municipio;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\NmProvincia", inversedBy="distribuidores")
     * @ORM\JoinColumn(nullable=false)
     * @Assert\NotNull()
     */
    private $provincia;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $country;

    /**
     *
     * @ORM\Column(type="float", nullable=true)
     */
    private $comision;

    /**
     *
     * @ORM\Column(type="float", nullable=true)
     */
    private $cancelado;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TnRemesa", mappedBy="distribuidor")
     */
    private $remesas;

    /**
     * @Assert\Count(min="1")
     * @ORM\ManyToMany(targetEntity="App\Entity\NmMunicipio", inversedBy="jefes")
     * @ORM\JoinTable(name="tn_distribuidor_municipio",
     *      joinColumns={@ORM\JoinColumn(name="distribuidor_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="municipio_id", referencedColumnName="id")}
     *      )
     */
    protected $zonas;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\TnUser", mappedBy="distribuidor")
     */
    private $usuario;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TnReporteEnvio", mappedBy="distribuidor")
     */
    private $reportesEnvio;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TnHistorialDistribuidor", mappedBy="distribuidor")
     */
    private $historialDistribucion;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TnOperacionDist", mappedBy="distribuidor")
     */
    private $operaciones;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TnCredito", mappedBy="distribuidor")
     */
    private $creditos;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TnCierreDistribuidor", mappedBy="distribuidor")
     */
    private $cierres;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TnSubDistribuidor", mappedBy="distribuidor")
     */
    private $subDistribuidores;

    public function __construct()
    {
        $this->remesas = new ArrayCollection();
        $this->zonas = new ArrayCollection();
        $this->reportesEnvio = new ArrayCollection();
        $this->historialDistribucion = new ArrayCollection();
        $this->cierres = new ArrayCollection();
        $this->operaciones = new ArrayCollection();
        $this->creditos = new ArrayCollection();
        $this->subDistribuidores = new ArrayCollection();
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

    public function getDatosLicencia(): ?string
    {
        return $this->datos_licencia;
    }

    public function setDatosLicencia(string $datos_licencia): self
    {
        $this->datos_licencia = $datos_licencia;

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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

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
            $remesa->setDistribuidor($this);
        }

        return $this;
    }

    public function removeRemesa(TnRemesa $remesa): self
    {
        if ($this->remesas->contains($remesa)) {
            $this->remesas->removeElement($remesa);
            // set the owning side to null (unless already changed)
            if ($remesa->getDistribuidor() === $this) {
                $remesa->setDistribuidor(null);
            }
        }

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

    public function getDireccion1(): ?string
    {
        return $this->direccion1;
    }

    public function setDireccion1(string $direccion1): self
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

    /**
     * @return Collection|NmMunicipio[]
     */
    public function getZonas(): Collection
    {
        return $this->zonas;
    }

    public function addZona(NmMunicipio $zona): self
    {
        if (!$this->zonas->contains($zona)) {
            $this->zonas[] = $zona;
        }

        return $this;
    }

    public function removeZona(NmMunicipio $zona): self
    {
        if ($this->zonas->contains($zona)) {
            $this->zonas->removeElement($zona);
        }

        return $this;
    }

    public function __toString()
    {
        return $this->nombre . " " . $this->apellidos . "-" . $this->phone;
    }

    public function showData()
    {
        return $this->nombre . " " . $this->apellidos . "-" . $this->phone;
    }

    public function getUsuario(): ?TnUser
    {
        return $this->usuario;
    }

    public function setUsuario(?TnUser $usuario): self
    {
        $this->usuario = $usuario;

        // set (or unset) the owning side of the relation if necessary
        $newDistribuidor = null === $usuario ? null : $this;
        if ($usuario->getDistribuidor() !== $newDistribuidor) {
            $usuario->setDistribuidor($newDistribuidor);
        }

        return $this;
    }

    /**
     * @return Collection|TnReporteEnvio[]
     */
    public function getReportesEnvio(): Collection
    {
        return $this->reportesEnvio;
    }

    public function addReportesEnvio(TnReporteEnvio $reportesEnvio): self
    {
        if (!$this->reportesEnvio->contains($reportesEnvio)) {
            $this->reportesEnvio[] = $reportesEnvio;
            $reportesEnvio->setDistribuidor($this);
        }

        return $this;
    }

    public function removeReportesEnvio(TnReporteEnvio $reportesEnvio): self
    {
        if ($this->reportesEnvio->contains($reportesEnvio)) {
            $this->reportesEnvio->removeElement($reportesEnvio);
            // set the owning side to null (unless already changed)
            if ($reportesEnvio->getDistribuidor() === $this) {
                $reportesEnvio->setDistribuidor(null);
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

    /**
     * @return Collection|TnHistorial[]
     */
    public function getHistorialDistribucion(): Collection
    {
        return $this->historialDistribucion;
    }

    public function addHistorialDistribucion(TnHistorial $historialDistribucion): self
    {
        if (!$this->historialDistribucion->contains($historialDistribucion)) {
            $this->historialDistribucion[] = $historialDistribucion;
            $historialDistribucion->setDistribuidor($this);
        }

        return $this;
    }

    public function removeHistorialDistribucion(TnHistorial $historialDistribucion): self
    {
        if ($this->historialDistribucion->contains($historialDistribucion)) {
            $this->historialDistribucion->removeElement($historialDistribucion);
            // set the owning side to null (unless already changed)
            if ($historialDistribucion->getDistribuidor() === $this) {
                $historialDistribucion->setDistribuidor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|TnOperacionDist[]
     */
    public function getOperaciones(): Collection
    {
        return $this->operaciones;
    }

    public function addOperacione(TnOperacionDist $operacione): self
    {
        if (!$this->operaciones->contains($operacione)) {
            $this->operaciones[] = $operacione;
            $operacione->setDistribuidor($this);
        }

        return $this;
    }

    public function removeOperacione(TnOperacionDist $operacione): self
    {
        if ($this->operaciones->contains($operacione)) {
            $this->operaciones->removeElement($operacione);
            // set the owning side to null (unless already changed)
            if ($operacione->getDistribuidor() === $this) {
                $operacione->setDistribuidor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|TnCredito[]
     */
    public function getCreditos(): Collection
    {
        return $this->creditos;
    }

    public function addCredito(TnCredito $credito): self
    {
        if (!$this->creditos->contains($credito)) {
            $this->creditos[] = $credito;
            $credito->setDistribuidor($this);
        }

        return $this;
    }

    public function removeCredito(TnCredito $credito): self
    {
        if ($this->creditos->contains($credito)) {
            $this->creditos->removeElement($credito);
            // set the owning side to null (unless already changed)
            if ($credito->getDistribuidor() === $this) {
                $credito->setDistribuidor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|TnCierreDistribuidor[]
     */
    public function getCierres(): Collection
    {
        return $this->cierres;
    }

    public function addCierre(TnCierreDistribuidor $cierre): self
    {
        if (!$this->cierres->contains($cierre)) {
            $this->cierres[] = $cierre;
            $cierre->setDistribuidor($this);
        }

        return $this;
    }

    public function removeCierre(TnCierreDistribuidor $cierre): self
    {
        if ($this->cierres->contains($cierre)) {
            $this->cierres->removeElement($cierre);
            // set the owning side to null (unless already changed)
            if ($cierre->getDistribuidor() === $this) {
                $cierre->setDistribuidor(null);
            }
        }

        return $this;
    }

    public function getComision(): ?float
    {
        return $this->comision;
    }

    public function setComision(?float $comision): self
    {
        $this->comision = $comision;

        return $this;
    }

    public function getCancelado(): ?float
    {
        return $this->cancelado;
    }

    public function setCancelado(?float $cancelado): self
    {
        $this->cancelado = $cancelado;

        return $this;
    }

    /**
     * @return Collection|TnSubDistribuidor[]
     */
    public function getSubDistribuidores(): Collection
    {
        return $this->subDistribuidores;
    }

    public function addSubDistribuidore(TnSubDistribuidor $subDistribuidore): self
    {
        if (!$this->subDistribuidores->contains($subDistribuidore)) {
            $this->subDistribuidores[] = $subDistribuidore;
            $subDistribuidore->setDistribuidor($this);
        }

        return $this;
    }

    public function removeSubDistribuidore(TnSubDistribuidor $subDistribuidore): self
    {
        if ($this->subDistribuidores->contains($subDistribuidore)) {
            $this->subDistribuidores->removeElement($subDistribuidore);
            // set the owning side to null (unless already changed)
            if ($subDistribuidore->getDistribuidor() === $this) {
                $subDistribuidore->setDistribuidor(null);
            }
        }

        return $this;
    }
}
