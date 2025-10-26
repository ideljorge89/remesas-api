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
 * @UniqueEntity({"email"})
 * @ORM\Entity(repositoryClass="App\Repository\TnAgenciaRepository")
 */
class TnAgencia
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
     * @ORM\Column(type="string", length=255)
     */
    private $phone;

    /**
     * @Assert\Email()
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=255)
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
     * @ORM\Column(type="string", length=255)
     */
    private $city;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $state;

    /**
     * @Assert\Length(
     *   min = 5,
     *   max = 5,
     * )
     * @ORM\Column(type="integer", length=255)
     */
    private $zip;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $country;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $token;


    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $fichero;

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
     * @var string
     *
     * @ORM\Column(name="retenida", type="boolean", nullable=true)
     */
    protected $retenida;

    /**
     * @var string
     *
     * @ORM\Column(name="unlimited", type="boolean", nullable=true)
     */
    protected $unlimited;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TnAgente", mappedBy="agencia", orphanRemoval=true, cascade={"persist","remove"})
     */
    private $agentes;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\TnUser", mappedBy="agencia")
     */
    private $usuario;

    /**
     * @Assert\Count(min="1")
     * @ORM\ManyToMany(targetEntity="App\Entity\NmGrupoPago", inversedBy="agencias")
     * @ORM\JoinTable(name="tn_agencia_pago",
     *      joinColumns={@ORM\JoinColumn(name="agencia_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="grupo_id", referencedColumnName="id")}
     *      )
     */
    protected $gruposPago;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\NmGrupoPagoTransf", inversedBy="agencias")
     * @ORM\JoinTable(name="tn_agencia_pago_transf",
     *      joinColumns={@ORM\JoinColumn(name="agencia_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="grupo_id", referencedColumnName="id")}
     *      )
     */
    protected $gruposPagoTransferencias;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TnFactura", mappedBy="agencia")
     */
    private $facturas;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TnDocumento", mappedBy="agencia")
     */
    private $documentos;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TnApoderadoFactura", mappedBy="agencia")
     */
    private $apoderadoFacturas;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\TnApoderado", mappedBy="agencia")
     */
    private $apoderado;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\TnApoderado", mappedBy="subordinadas", cascade={"persist", "remove"})
     */
    protected $agenciaApodederados;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TnSaldoAgencia", mappedBy="agencia")
     */
    private $saldoMonedas;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TnOperacionAgencia", mappedBy="agencia")
     */
    private $porcentajes;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TnOperacionAgenciaTransf", mappedBy="agencia")
     */
    private $porcentajesTransferencias;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TnTransferencia", mappedBy="agencia")
     */
    private $transferencias;

    public function __construct()
    {
        $this->agentes = new ArrayCollection();
        $this->facturas = new ArrayCollection();
        $this->documentos = new ArrayCollection();
        $this->gruposPago = new ArrayCollection();
        $this->agenciaApodederados = new ArrayCollection();
        $this->apoderadoFacturas = new ArrayCollection();
        $this->saldoMonedas = new ArrayCollection();
        $this->porcentajes = new ArrayCollection();
        $this->transferencias = new ArrayCollection();
        $this->gruposPagoTransferencias = new ArrayCollection();
        $this->porcentajesTransferencias = new ArrayCollection();
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

    public function getZip(): ?int
    {
        return $this->zip;
    }

    public function setZip(int $zip): self
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
            $agente->setAgencia($this);
        }

        return $this;
    }

    public function removeAgente(TnAgente $agente): self
    {
        if ($this->agentes->contains($agente)) {
            $this->agentes->removeElement($agente);
            // set the owning side to null (unless already changed)
            if ($agente->getAgencia() === $this) {
                $agente->setAgencia(null);
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
        return $this->nombre;
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
            $factura->setAgencia($this);
        }

        return $this;
    }

    public function removeFactura(TnFactura $factura): self
    {
        if ($this->facturas->contains($factura)) {
            $this->facturas->removeElement($factura);
            // set the owning side to null (unless already changed)
            if ($factura->getAgencia() === $this) {
                $factura->setAgencia(null);
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

        // set (or unset) the owning side of the relation if necessary
        $newAgencia = null === $usuario ? null : $this;
        if ($usuario->getAgencia() !== $newAgencia) {
            $usuario->setAgencia($newAgencia);
        }

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
            $documento->setAgencia($this);
        }

        return $this;
    }

    public function removeDocumento(TnDocumento $documento): self
    {
        if ($this->documentos->contains($documento)) {
            $this->documentos->removeElement($documento);
            // set the owning side to null (unless already changed)
            if ($documento->getAgencia() === $this) {
                $documento->setAgencia(null);
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
     * @return Collection|NmGrupoPago[]
     */
    public function getGruposPago(): Collection
    {
        return $this->gruposPago;
    }

    public function addGruposPago(NmGrupoPago $gruposPago): self
    {
        if (!$this->gruposPago->contains($gruposPago)) {
            $this->gruposPago[] = $gruposPago;
        }

        return $this;
    }

    public function removeGruposPago(NmGrupoPago $gruposPago): self
    {
        if ($this->gruposPago->contains($gruposPago)) {
            $this->gruposPago->removeElement($gruposPago);
        }

        return $this;
    }

    public function getApoderado(): ?TnApoderado
    {
        return $this->apoderado;
    }

    public function setApoderado(?TnApoderado $apoderado): self
    {
        $this->apoderado = $apoderado;

        // set (or unset) the owning side of the relation if necessary
        $newAgencia = null === $apoderado ? null : $this;
        if ($apoderado->getAgencia() !== $newAgencia) {
            $apoderado->setAgencia($newAgencia);
        }

        return $this;
    }

    /**
     * @return Collection|TnApoderado[]
     */
    public function getAgenciaApodederados(): Collection
    {
        return $this->agenciaApodederados;
    }

    public function addAgenciaApodederado(TnApoderado $agenciaApodederado): self
    {
        if (!$this->agenciaApodederados->contains($agenciaApodederado)) {
            $this->agenciaApodederados[] = $agenciaApodederado;
        }

        return $this;
    }

    public function removeAgenciaApodederado(TnApoderado $agenciaApodederado): self
    {
        if ($this->agenciaApodederados->contains($agenciaApodederado)) {
            $this->agenciaApodederados->removeElement($agenciaApodederado);
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
            $apoderadoFactura->setAgencia($this);
        }

        return $this;
    }

    public function removeApoderadoFactura(TnApoderadoFactura $apoderadoFactura): self
    {
        if ($this->apoderadoFacturas->contains($apoderadoFactura)) {
            $this->apoderadoFacturas->removeElement($apoderadoFactura);
            // set the owning side to null (unless already changed)
            if ($apoderadoFactura->getAgencia() === $this) {
                $apoderadoFactura->setAgencia(null);
            }
        }

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
            $saldoMoneda->setAgencia($this);
        }

        return $this;
    }

    public function removeSaldoMoneda(TnSaldoAgencia $saldoMoneda): self
    {
        if ($this->saldoMonedas->contains($saldoMoneda)) {
            $this->saldoMonedas->removeElement($saldoMoneda);
            // set the owning side to null (unless already changed)
            if ($saldoMoneda->getAgencia() === $this) {
                $saldoMoneda->setAgencia(null);
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
            $porcentaje->setAgencia($this);
        }

        return $this;
    }

    public function removePorcentaje(TnOperacionAgencia $porcentaje): self
    {
        if ($this->porcentajes->contains($porcentaje)) {
            $this->porcentajes->removeElement($porcentaje);
            // set the owning side to null (unless already changed)
            if ($porcentaje->getAgencia() === $this) {
                $porcentaje->setAgencia(null);
            }
        }

        return $this;
    }

    public function getFichero(): ?string
    {
        return $this->fichero;
    }

    public function setFichero(?string $fichero): self
    {
        $this->fichero = $fichero;

        return $this;
    }

    /**
     * @return Collection|TnTransferencia[]
     */
    public function getTransferencias(): Collection
    {
        return $this->transferencias;
    }

    public function addTransferencia(TnTransferencia $transferencia): self
    {
        if (!$this->transferencias->contains($transferencia)) {
            $this->transferencias[] = $transferencia;
            $transferencia->setAgencia($this);
        }

        return $this;
    }

    public function removeTransferencia(TnTransferencia $transferencia): self
    {
        if ($this->transferencias->contains($transferencia)) {
            $this->transferencias->removeElement($transferencia);
            // set the owning side to null (unless already changed)
            if ($transferencia->getAgencia() === $this) {
                $transferencia->setAgencia(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|NmGrupoPagoTransf[]
     */
    public function getGruposPagoTransferencias(): Collection
    {
        return $this->gruposPagoTransferencias;
    }

    public function addGruposPagoTransferencia(NmGrupoPagoTransf $gruposPagoTransferencia): self
    {
        if (!$this->gruposPagoTransferencias->contains($gruposPagoTransferencia)) {
            $this->gruposPagoTransferencias[] = $gruposPagoTransferencia;
        }

        return $this;
    }

    public function removeGruposPagoTransferencia(NmGrupoPagoTransf $gruposPagoTransferencia): self
    {
        if ($this->gruposPagoTransferencias->contains($gruposPagoTransferencia)) {
            $this->gruposPagoTransferencias->removeElement($gruposPagoTransferencia);
        }

        return $this;
    }

    /**
     * @return Collection|TnOperacionAgenciaTransf[]
     */
    public function getPorcentajesTransferencias(): Collection
    {
        return $this->porcentajesTransferencias;
    }

    public function addPorcentajesTransferencia(TnOperacionAgenciaTransf $porcentajesTransferencia): self
    {
        if (!$this->porcentajesTransferencias->contains($porcentajesTransferencia)) {
            $this->porcentajesTransferencias[] = $porcentajesTransferencia;
            $porcentajesTransferencia->setAgencia($this);
        }

        return $this;
    }

    public function removePorcentajesTransferencia(TnOperacionAgenciaTransf $porcentajesTransferencia): self
    {
        if ($this->porcentajesTransferencias->contains($porcentajesTransferencia)) {
            $this->porcentajesTransferencias->removeElement($porcentajesTransferencia);
            // set the owning side to null (unless already changed)
            if ($porcentajesTransferencia->getAgencia() === $this) {
                $porcentajesTransferencia->setAgencia(null);
            }
        }

        return $this;
    }

    public function getUnlimited(): ?bool
    {
        return $this->unlimited;
    }

    public function setUnlimited(?bool $unlimited): self
    {
        $this->unlimited = $unlimited;

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
