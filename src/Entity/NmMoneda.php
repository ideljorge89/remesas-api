<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;


/**
 * Moneda
 *
 * @ORM\Table(name="nm_moneda")
 * @UniqueEntity({"codigo"})
 * @UniqueEntity({"simbolo"})
 * @Gedmo\Loggable()
 * @ORM\Entity(repositoryClass="App\Repository\NmMonedaRepository")
 */
class NmMoneda
{

    const CURRENCY_CUC = "CUC";
    const CURRENCY_CUP = "CUP";
    const CURRENCY_USD = "USD";
    const CURRENCY_EUR = "EUR";
    const CURRENCY_CAD = "CAD";

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=50, unique=true)
     */
    protected $codigo;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=3, unique=true)
     */
    protected $simbolo;

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
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $enabled;

    /**
     * @var string
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $aviable;

    /**
     * @var string
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $contable;

    /**
     * @var string
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $comision;

    /**
     * @Gedmo\Versioned()
     *
     * @ORM\Column(type="float")
     */
    private $tasaCambio;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $minimo;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $maximo;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $minimoTransferencia;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $maximoTransferencia;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TnRemesa", mappedBy="moneda")
     */
    private $remesas;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\NmGrupoPago", mappedBy="moneda")
     */
    private $gruposPagoMoneda;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\NmGrupoPagoAgente", mappedBy="moneda")
     */
    private $gruposPagoAgenteMoneda;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\NmGrupoPagoTransf", mappedBy="moneda")
     */
    private $gruposPagoTransf;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\NmGrupoPagoTransfAgente", mappedBy="moneda")
     */
    private $gruposPagoTransfAgente;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TnHistorialDistribuidor", mappedBy="monedaRemesa")
     */
    private $historialesDistribuidor;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TnOperacionDist", mappedBy="moneda")
     */
    private $operacionesDistribuidor;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TnCredito", mappedBy="moneda")
     */
    private $creditos;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TnCierreDistribuidor", mappedBy="moneda")
     */
    private $cierres;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TnTransferencia", mappedBy="moneda")
     */
    private $transferencias;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\NmMunicipio", mappedBy="monedasEntrega")
     */
    protected $municipios;


    public function __construct()
    {
        $this->remesas = new ArrayCollection();
        $this->gruposPagoMoneda = new ArrayCollection();
        $this->gruposPagoAgenteMoneda = new ArrayCollection();
        $this->historialesDistribuidor = new ArrayCollection();
        $this->operacionesDistribuidor = new ArrayCollection();
        $this->creditos = new ArrayCollection();
        $this->cierres = new ArrayCollection();
        $this->gruposPagoTransf = new ArrayCollection();
        $this->gruposPagoTransfAgente = new ArrayCollection();
        $this->transferencias = new ArrayCollection();
        $this->municipios = new ArrayCollection();
    }


    public function __toString()
    {
        return $this->codigo;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCodigo(): ?string
    {
        return $this->codigo;
    }

    public function setCodigo(string $codigo): self
    {
        $this->codigo = $codigo;

        return $this;
    }

    public function getSimbolo(): ?string
    {
        return $this->simbolo;
    }

    public function setSimbolo(string $simbolo): self
    {
        $this->simbolo = $simbolo;

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

    public function getContable(): ?bool
    {
        return $this->contable;
    }

    public function setContable(?bool $contable): self
    {
        $this->contable = $contable;

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
            $remesa->setMoneda($this);
        }

        return $this;
    }

    public function removeRemesa(TnRemesa $remesa): self
    {
        if ($this->remesas->contains($remesa)) {
            $this->remesas->removeElement($remesa);
            // set the owning side to null (unless already changed)
            if ($remesa->getMoneda() === $this) {
                $remesa->setMoneda(null);
            }
        }

        return $this;
    }

    public function getAviable(): ?bool
    {
        return $this->aviable;
    }

    public function setAviable(?bool $aviable): self
    {
        $this->aviable = $aviable;

        return $this;
    }

    public function getTasaCambio(): ?float
    {
        return $this->tasaCambio;
    }

    public function setTasaCambio(float $tasaCambio): self
    {
        $this->tasaCambio = $tasaCambio;

        return $this;
    }

    /**
     * @return Collection|NmGrupoPago[]
     */
    public function getGruposPagoMoneda(): Collection
    {
        return $this->gruposPagoMoneda;
    }

    public function addGruposPagoMoneda(NmGrupoPago $gruposPagoMoneda): self
    {
        if (!$this->gruposPagoMoneda->contains($gruposPagoMoneda)) {
            $this->gruposPagoMoneda[] = $gruposPagoMoneda;
            $gruposPagoMoneda->setMoneda($this);
        }

        return $this;
    }

    public function removeGruposPagoMoneda(NmGrupoPago $gruposPagoMoneda): self
    {
        if ($this->gruposPagoMoneda->contains($gruposPagoMoneda)) {
            $this->gruposPagoMoneda->removeElement($gruposPagoMoneda);
            // set the owning side to null (unless already changed)
            if ($gruposPagoMoneda->getMoneda() === $this) {
                $gruposPagoMoneda->setMoneda(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|NmGrupoPagoAgente[]
     */
    public function getGruposPagoAgenteMoneda(): Collection
    {
        return $this->gruposPagoAgenteMoneda;
    }

    public function addGruposPagoAgenteMoneda(NmGrupoPagoAgente $gruposPagoAgenteMoneda): self
    {
        if (!$this->gruposPagoAgenteMoneda->contains($gruposPagoAgenteMoneda)) {
            $this->gruposPagoAgenteMoneda[] = $gruposPagoAgenteMoneda;
            $gruposPagoAgenteMoneda->setMoneda($this);
        }

        return $this;
    }

    public function removeGruposPagoAgenteMoneda(NmGrupoPagoAgente $gruposPagoAgenteMoneda): self
    {
        if ($this->gruposPagoAgenteMoneda->contains($gruposPagoAgenteMoneda)) {
            $this->gruposPagoAgenteMoneda->removeElement($gruposPagoAgenteMoneda);
            // set the owning side to null (unless already changed)
            if ($gruposPagoAgenteMoneda->getMoneda() === $this) {
                $gruposPagoAgenteMoneda->setMoneda(null);
            }
        }

        return $this;
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

    public function getMaximo(): ?int
    {
        return $this->maximo;
    }

    public function setMaximo(?int $maximo): self
    {
        $this->maximo = $maximo;

        return $this;
    }

    /**
     * @return Collection|TnHistorialDistribuidor[]
     */
    public function getHistorialesDistribuidor(): Collection
    {
        return $this->historialesDistribuidor;
    }

    public function addHistorialesDistribuidor(TnHistorialDistribuidor $historialesDistribuidor): self
    {
        if (!$this->historialesDistribuidor->contains($historialesDistribuidor)) {
            $this->historialesDistribuidor[] = $historialesDistribuidor;
            $historialesDistribuidor->setMonedaRemesa($this);
        }

        return $this;
    }

    public function removeHistorialesDistribuidor(TnHistorialDistribuidor $historialesDistribuidor): self
    {
        if ($this->historialesDistribuidor->contains($historialesDistribuidor)) {
            $this->historialesDistribuidor->removeElement($historialesDistribuidor);
            // set the owning side to null (unless already changed)
            if ($historialesDistribuidor->getMonedaRemesa() === $this) {
                $historialesDistribuidor->setMonedaRemesa(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|TnOperacionDist[]
     */
    public function getOperacionesDistribuidor(): Collection
    {
        return $this->operacionesDistribuidor;
    }

    public function addOperacionesDistribuidor(TnOperacionDist $operacionesDistribuidor): self
    {
        if (!$this->operacionesDistribuidor->contains($operacionesDistribuidor)) {
            $this->operacionesDistribuidor[] = $operacionesDistribuidor;
            $operacionesDistribuidor->setMoneda($this);
        }

        return $this;
    }

    public function removeOperacionesDistribuidor(TnOperacionDist $operacionesDistribuidor): self
    {
        if ($this->operacionesDistribuidor->contains($operacionesDistribuidor)) {
            $this->operacionesDistribuidor->removeElement($operacionesDistribuidor);
            // set the owning side to null (unless already changed)
            if ($operacionesDistribuidor->getMoneda() === $this) {
                $operacionesDistribuidor->setMoneda(null);
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
            $credito->setMoneda($this);
        }

        return $this;
    }

    public function removeCredito(TnCredito $credito): self
    {
        if ($this->creditos->contains($credito)) {
            $this->creditos->removeElement($credito);
            // set the owning side to null (unless already changed)
            if ($credito->getMoneda() === $this) {
                $credito->setMoneda(null);
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
            $cierre->setMoneda($this);
        }

        return $this;
    }

    public function removeCierre(TnCierreDistribuidor $cierre): self
    {
        if ($this->cierres->contains($cierre)) {
            $this->cierres->removeElement($cierre);
            // set the owning side to null (unless already changed)
            if ($cierre->getMoneda() === $this) {
                $cierre->setMoneda(null);
            }
        }

        return $this;
    }

    public function getComision(): ?bool
    {
        return $this->comision;
    }

    public function setComision(?bool $comision): self
    {
        $this->comision = $comision;

        return $this;
    }

    /**
     * @return Collection|NmGrupoPagoTransf[]
     */
    public function getGruposPagoTransf(): Collection
    {
        return $this->gruposPagoTransf;
    }

    public function addGruposPagoTransf(NmGrupoPagoTransf $gruposPagoTransf): self
    {
        if (!$this->gruposPagoTransf->contains($gruposPagoTransf)) {
            $this->gruposPagoTransf[] = $gruposPagoTransf;
            $gruposPagoTransf->setMoneda($this);
        }

        return $this;
    }

    public function removeGruposPagoTransf(NmGrupoPagoTransf $gruposPagoTransf): self
    {
        if ($this->gruposPagoTransf->contains($gruposPagoTransf)) {
            $this->gruposPagoTransf->removeElement($gruposPagoTransf);
            // set the owning side to null (unless already changed)
            if ($gruposPagoTransf->getMoneda() === $this) {
                $gruposPagoTransf->setMoneda(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|NmGrupoPagoTransfAgente[]
     */
    public function getGruposPagoTransfAgente(): Collection
    {
        return $this->gruposPagoTransfAgente;
    }

    public function addGruposPagoTransfAgente(NmGrupoPagoTransfAgente $gruposPagoTransfAgente): self
    {
        if (!$this->gruposPagoTransfAgente->contains($gruposPagoTransfAgente)) {
            $this->gruposPagoTransfAgente[] = $gruposPagoTransfAgente;
            $gruposPagoTransfAgente->setMoneda($this);
        }

        return $this;
    }

    public function removeGruposPagoTransfAgente(NmGrupoPagoTransfAgente $gruposPagoTransfAgente): self
    {
        if ($this->gruposPagoTransfAgente->contains($gruposPagoTransfAgente)) {
            $this->gruposPagoTransfAgente->removeElement($gruposPagoTransfAgente);
            // set the owning side to null (unless already changed)
            if ($gruposPagoTransfAgente->getMoneda() === $this) {
                $gruposPagoTransfAgente->setMoneda(null);
            }
        }

        return $this;
    }

    public function getMinimoTransferencia(): ?int
    {
        return $this->minimoTransferencia;
    }

    public function setMinimoTransferencia(?int $minimoTransferencia): self
    {
        $this->minimoTransferencia = $minimoTransferencia;

        return $this;
    }

    public function getMaximoTransferencia(): ?int
    {
        return $this->maximoTransferencia;
    }

    public function setMaximoTransferencia(?int $maximoTransferencia): self
    {
        $this->maximoTransferencia = $maximoTransferencia;

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
            $transferencia->setMoneda($this);
        }

        return $this;
    }

    public function removeTransferencia(TnTransferencia $transferencia): self
    {
        if ($this->transferencias->contains($transferencia)) {
            $this->transferencias->removeElement($transferencia);
            // set the owning side to null (unless already changed)
            if ($transferencia->getMoneda() === $this) {
                $transferencia->setMoneda(null);
            }
        }

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
            $municipio->addMonedasEntrega($this);
        }

        return $this;
    }

    public function removeMunicipio(NmMunicipio $municipio): self
    {
        if ($this->municipios->contains($municipio)) {
            $this->municipios->removeElement($municipio);
            $municipio->removeMonedasEntrega($this);
        }

        return $this;
    }

}
