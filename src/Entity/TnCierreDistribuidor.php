<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;


/**
 * @ORM\Entity(repositoryClass="App\Repository\TnCierreDistribuidorRepository")
 */
class TnCierreDistribuidor
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     *
     * @ORM\Column(type="float")
     */
    private $saldoInicial;

    /**
     *
     * @ORM\Column(type="float")
     */
    private $recibido;

    /**
     *
     * @ORM\Column(type="float")
     */
    private $entregado;

    /**
     *
     * @ORM\Column(type="float", nullable=true)
     */
    private $envios;

    /**
     *
     * @ORM\Column(type="float")
     */
    private $comision;

    /**
     *
     * @ORM\Column(type="float")
     */
    private $cancelado;

    /**
     *
     * @ORM\Column(type="float")
     */
    private $transferido;

    /**
     *
     * @ORM\Column(type="float")
     */
    private $gastos;

    /**
     *
     * @ORM\Column(type="float")
     */
    private $credito;

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
     * @ORM\OneToMany(targetEntity="App\Entity\TnHistorialDistribuidor", mappedBy="cierre")
     */
    private $historiales;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\TnDistribuidor", inversedBy="cierres")
     */
    private $distribuidor;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\NmMoneda", inversedBy="cierres")
     */
    private $moneda;

    public function __construct()
    {
        $this->historiales = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSaldoInicial(): ?float
    {
        return $this->saldoInicial;
    }

    public function setSaldoInicial(float $saldoInicial): self
    {
        $this->saldoInicial = $saldoInicial;

        return $this;
    }

    public function getRecibido(): ?float
    {
        return $this->recibido;
    }

    public function setRecibido(float $recibido): self
    {
        $this->recibido = $recibido;

        return $this;
    }

    public function getEntregado(): ?float
    {
        return $this->entregado;
    }

    public function setEntregado(float $entregado): self
    {
        $this->entregado = $entregado;

        return $this;
    }

    public function getComision(): ?float
    {
        return $this->comision;
    }

    public function setComision(float $comision): self
    {
        $this->comision = $comision;

        return $this;
    }

    public function getCancelado(): ?float
    {
        return $this->cancelado;
    }

    public function setCancelado(float $cancelado): self
    {
        $this->cancelado = $cancelado;

        return $this;
    }

    public function getTransferido(): ?float
    {
        return $this->transferido;
    }

    public function setTransferido(float $transferido): self
    {
        $this->transferido = $transferido;

        return $this;
    }

    public function getGastos(): ?float
    {
        return $this->gastos;
    }

    public function setGastos(float $gastos): self
    {
        $this->gastos = $gastos;

        return $this;
    }

    public function getCredito(): ?float
    {
        return $this->credito;
    }

    public function setCredito(float $credito): self
    {
        $this->credito = $credito;

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

    /**
     * @return Collection|TnHistorialDistribuidor[]
     */
    public function getHistoriales(): Collection
    {
        return $this->historiales;
    }

    public function addHistoriale(TnHistorialDistribuidor $historiale): self
    {
        if (!$this->historiales->contains($historiale)) {
            $this->historiales[] = $historiale;
            $historiale->setCierre($this);
        }

        return $this;
    }

    public function removeHistoriale(TnHistorialDistribuidor $historiale): self
    {
        if ($this->historiales->contains($historiale)) {
            $this->historiales->removeElement($historiale);
            // set the owning side to null (unless already changed)
            if ($historiale->getCierre() === $this) {
                $historiale->setCierre(null);
            }
        }

        return $this;
    }

    public function getDistribuidor(): ?TnDistribuidor
    {
        return $this->distribuidor;
    }

    public function setDistribuidor(?TnDistribuidor $distribuidor): self
    {
        $this->distribuidor = $distribuidor;

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

    public function getEnvios(): ?float
    {
        return $this->envios;
    }

    public function setEnvios(?float $envios): self
    {
        $this->envios = $envios;

        return $this;
    }
}
