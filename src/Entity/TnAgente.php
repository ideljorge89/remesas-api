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
 * @ORM\Entity(repositoryClass="App\Repository\TnAgenteRepository")
 */
class TnAgente
{
    const TIPO_EXTERNO = 'Externo';
    const TIPO_INTERNO = 'Interno';

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
    private $phone;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $email;

    /**
     * @Assert\NotNull()
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $tipoAgente;

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
     * @ORM\Column(type="string", length=255)
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
     * @ORM\ManyToOne(targetEntity="App\Entity\TnAgencia", inversedBy="agentes")
     */
    private $agencia;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TnFactura", mappedBy="agente")
     */
    private $facturas;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\NmGrupoPagoAgente", inversedBy="agentes")
     * @ORM\JoinTable(name="tn_agente_pago",
     *      joinColumns={@ORM\JoinColumn(name="agente_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="grupo_id", referencedColumnName="id")}
     *      )
     */
    protected $gruposPago;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\NmGrupoPagoTransfAgente", inversedBy="agentes")
     * @ORM\JoinTable(name="tn_agente_pago_transf",
     *      joinColumns={@ORM\JoinColumn(name="agente_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="grupo_id", referencedColumnName="id")}
     *      )
     */
    protected $gruposPagoTransferencias;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\TnUser", mappedBy="agente")
     */
    private $usuario;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TnSaldoAgente", mappedBy="agente")
     */
    private $saldoMonedas;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TnOperacionAgente", mappedBy="agente")
     */
    private $porcentajes;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TnOperacionAgenteTransf", mappedBy="agente")
     */
    private $porcentajesTransferencias;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TnTransferencia", mappedBy="agente")
     */
    private $transferencias;


    public function __construct()
    {
        $this->facturas = new ArrayCollection();
        $this->gruposPago = new ArrayCollection();
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
            $factura->setAgente($this);
        }

        return $this;
    }

    public function removeFactura(TnFactura $factura): self
    {
        if ($this->facturas->contains($factura)) {
            $this->facturas->removeElement($factura);
            // set the owning side to null (unless already changed)
            if ($factura->getAgente() === $this) {
                $factura->setAgente(null);
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

    public function getUsuario(): ?TnUser
    {
        return $this->usuario;
    }

    public function setUsuario(?TnUser $usuario): self
    {
        $this->usuario = $usuario;

        return $this;
    }

    public function getTipoAgente(): ?string
    {
        return $this->tipoAgente;
    }

    public function setTipoAgente(?string $tipoAgente): self
    {
        $this->tipoAgente = $tipoAgente;

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
     * @return Collection|NmGrupoPagoAgente[]
     */
    public function getGruposPago(): Collection
    {
        return $this->gruposPago;
    }

    public function addGruposPago(NmGrupoPagoAgente $gruposPago): self
    {
        if (!$this->gruposPago->contains($gruposPago)) {
            $this->gruposPago[] = $gruposPago;
        }

        return $this;
    }

    public function removeGruposPago(NmGrupoPagoAgente $gruposPago): self
    {
        if ($this->gruposPago->contains($gruposPago)) {
            $this->gruposPago->removeElement($gruposPago);
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
            $saldoMoneda->setAgente($this);
        }

        return $this;
    }

    public function removeSaldoMoneda(TnSaldoAgente $saldoMoneda): self
    {
        if ($this->saldoMonedas->contains($saldoMoneda)) {
            $this->saldoMonedas->removeElement($saldoMoneda);
            // set the owning side to null (unless already changed)
            if ($saldoMoneda->getAgente() === $this) {
                $saldoMoneda->setAgente(null);
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
            $porcentaje->setAgente($this);
        }

        return $this;
    }

    public function removePorcentaje(TnOperacionAgente $porcentaje): self
    {
        if ($this->porcentajes->contains($porcentaje)) {
            $this->porcentajes->removeElement($porcentaje);
            // set the owning side to null (unless already changed)
            if ($porcentaje->getAgente() === $this) {
                $porcentaje->setAgente(null);
            }
        }

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
            $transferencia->setAgente($this);
        }

        return $this;
    }

    public function removeTransferencia(TnTransferencia $transferencia): self
    {
        if ($this->transferencias->contains($transferencia)) {
            $this->transferencias->removeElement($transferencia);
            // set the owning side to null (unless already changed)
            if ($transferencia->getAgente() === $this) {
                $transferencia->setAgente(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|NmGrupoPagoTransfAgente[]
     */
    public function getGruposPagoTransferencias(): Collection
    {
        return $this->gruposPagoTransferencias;
    }

    public function addGruposPagoTransferencia(NmGrupoPagoTransfAgente $gruposPagoTransferencia): self
    {
        if (!$this->gruposPagoTransferencias->contains($gruposPagoTransferencia)) {
            $this->gruposPagoTransferencias[] = $gruposPagoTransferencia;
        }

        return $this;
    }

    public function removeGruposPagoTransferencia(NmGrupoPagoTransfAgente $gruposPagoTransferencia): self
    {
        if ($this->gruposPagoTransferencias->contains($gruposPagoTransferencia)) {
            $this->gruposPagoTransferencias->removeElement($gruposPagoTransferencia);
        }

        return $this;
    }

    /**
     * @return Collection|TnOperacionAgenteTransf[]
     */
    public function getPorcentajesTransferencias(): Collection
    {
        return $this->porcentajesTransferencias;
    }

    public function addPorcentajesTransferencia(TnOperacionAgenteTransf $porcentajesTransferencia): self
    {
        if (!$this->porcentajesTransferencias->contains($porcentajesTransferencia)) {
            $this->porcentajesTransferencias[] = $porcentajesTransferencia;
            $porcentajesTransferencia->setAgente($this);
        }

        return $this;
    }

    public function removePorcentajesTransferencia(TnOperacionAgenteTransf $porcentajesTransferencia): self
    {
        if ($this->porcentajesTransferencias->contains($porcentajesTransferencia)) {
            $this->porcentajesTransferencias->removeElement($porcentajesTransferencia);
            // set the owning side to null (unless already changed)
            if ($porcentajesTransferencia->getAgente() === $this) {
                $porcentajesTransferencia->setAgente(null);
            }
        }

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
