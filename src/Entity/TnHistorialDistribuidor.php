<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TnHistorialDistribuidorRepository")
 */
class TnHistorialDistribuidor
{
    const ESTADO_EFECTIVA = 'efectiva';
    const ESTADO_CANCELADA = 'cancelada';

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
    private $importeRemesa;

    /**
     *
     * @ORM\Column(type="float", nullable=true)
     */
    private $tasaDistrucion;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $estado;

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
     * @var \DateTime $distribuidaAt
     *
     * @ORM\Column(name="cancelada_at",type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="change", field="estado", value="cancelada")
     */
    private $canceladaAt;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\TnDistribuidor", inversedBy="historialDistribucion")
     */
    private $distribuidor;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\TnRemesa", mappedBy="historialDistribuidor")
     */
    private $remesa;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\NmMoneda", inversedBy="historialesDistribuidor")
     */
    private $monedaRemesa;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\TnCierreDistribuidor", inversedBy="historiales")
     */
    private $cierre;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getImporteRemesa(): ?float
    {
        return $this->importeRemesa;
    }

    public function setImporteRemesa(float $importeRemesa): self
    {
        $this->importeRemesa = $importeRemesa;

        return $this;
    }

    public function getTasaDistrucion(): ?float
    {
        return $this->tasaDistrucion;
    }

    public function setTasaDistrucion(float $tasaDistrucion): self
    {
        $this->tasaDistrucion = $tasaDistrucion;

        return $this;
    }

    public function getEstado(): ?string
    {
        return $this->estado;
    }

    public function setEstado(string $estado): self
    {
        $this->estado = $estado;

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

    public function getDistribuidor(): ?TnDistribuidor
    {
        return $this->distribuidor;
    }

    public function setDistribuidor(?TnDistribuidor $distribuidor): self
    {
        $this->distribuidor = $distribuidor;

        return $this;
    }

    public function getRemesa(): ?TnRemesa
    {
        return $this->remesa;
    }

    public function setRemesa(?TnRemesa $remesa): self
    {
        $this->remesa = $remesa;

        // set (or unset) the owning side of the relation if necessary
        $newHistorialDistribuidor = null === $remesa ? null : $this;
        if ($remesa->getHistorialDistribuidor() !== $newHistorialDistribuidor) {
            $remesa->setHistorialDistribuidor($newHistorialDistribuidor);
        }

        return $this;
    }

    public function getMonedaRemesa(): ?NmMoneda
    {
        return $this->monedaRemesa;
    }

    public function setMonedaRemesa(?NmMoneda $monedaRemesa): self
    {
        $this->monedaRemesa = $monedaRemesa;

        return $this;
    }

    public function getCierre(): ?TnCierreDistribuidor
    {
        return $this->cierre;
    }

    public function setCierre(?TnCierreDistribuidor $cierre): self
    {
        $this->cierre = $cierre;

        return $this;
    }

    public function getCanceladaAt(): ?\DateTimeInterface
    {
        return $this->canceladaAt;
    }

    public function setCanceladaAt(?\DateTimeInterface $canceladaAt): self
    {
        $this->canceladaAt = $canceladaAt;

        return $this;
    }
}
