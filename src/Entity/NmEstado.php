<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @UniqueEntity("codigo")
 * @ORM\Entity(repositoryClass="App\Repository\NmEstadoRepository")
 */
class NmEstado
{
    const ESTADO_PENDIENTE = '01';
    const ESTADO_APROBADA = '02';
    const ESTADO_DISTRIBUCION = '03';
    const ESTADO_ENTREGADA = '04';
    const ESTADO_CANCELADA = '05';

    const ESTADO_CANCELADA_ID = 5;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     */
    private $nombre;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     */
    private $codigo;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updated;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TnFactura", mappedBy="estado")
     */
    private $facturas;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TnHistorial", mappedBy="estado", cascade={"persist","remove"})
     */
    private $historial;

    public function __construct()
    {
        $this->facturas = new ArrayCollection();
        $this->historial = new ArrayCollection();
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

    static public function getAllEstados()
    {
        return array(
            'Aprobada' => '02',
            'Distribución' => '03',
            'Entregada' => '04',
            'Cancelada' => '05',
        );
    }

    static public function getEstados()
    {
        return array(
            'Pendiente' => '01',
            'Aprobada' => '02',
            'Distribución' => '03',
            'Entregada' => '04',
            'Cancelada' => '05'
        );
    }

    static public function getCodeEstados()
    {
        return array(
            '01',
            '02',
            '03',
            '04',
        );
    }

    static public function getCodeDistribuidor()
    {
        return array(
            '02',
            '03',
            '04',
        );
    }

    static public function getReportDistribuidor()
    {
        return array(
            'Pendiente' => 'P',
            'Entregada' => 'E',
            'Cancelada' => 'C',
        );
    }

    static public function getAllBtnEstados()
    {
        return array(
            '01' => 'Si, ponla pendiente!',
            '02' => 'Si, apruébala!',
            '03' => 'Si, distrubye!',
            '04' => 'Si, entrégala!',
            '05' => 'Si, cancélala!'
        );
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
            $factura->setEstado($this);
        }

        return $this;
    }

    public function removeFactura(TnFactura $factura): self
    {
        if ($this->facturas->contains($factura)) {
            $this->facturas->removeElement($factura);
            // set the owning side to null (unless already changed)
            if ($factura->getEstado() === $this) {
                $factura->setEstado(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|TnHistorial[]
     */
    public function getHistorial(): Collection
    {
        return $this->historial;
    }

    public function addHistorial(TnHistorial $historial): self
    {
        if (!$this->historial->contains($historial)) {
            $this->historial[] = $historial;
            $historial->setEstado($this);
        }

        return $this;
    }

    public function removeHistorial(TnHistorial $historial): self
    {
        if ($this->historial->contains($historial)) {
            $this->historial->removeElement($historial);
            // set the owning side to null (unless already changed)
            if ($historial->getEstado() === $this) {
                $historial->setEstado(null);
            }
        }

        return $this;
    }

    public function __toString()
    {
        return $this->nombre;
    }
}
