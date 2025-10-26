<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TnReporteTransferenciaRepository")
 */
class TnReporteTransferencia
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $totalTransferencias;

    /**
     * @ORM\Column(type="integer")
     */
    private $importe;

    /**
     * @ORM\Column(type="datetime")
     */
    private $lastDate;

    /**
     * @var \DateTime|null
     * @ORM\Column(name="created",type="datetime",nullable=true)
     * @Gedmo\Timestampable(on="create")
     */
    private $created;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $token;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TnTransferencia", mappedBy="reporteTransferencia")
     */
    private $transferencias;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\TnRepartidor", inversedBy="reportesEnvio")
     */
    private $repartidor;

    public function __construct()
    {
        $this->transferencias = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTotalTransferencias(): ?int
    {
        return $this->totalTransferencias;
    }

    public function setTotalTransferencias(int $totalTransferencias): self
    {
        $this->totalTransferencias = $totalTransferencias;

        return $this;
    }

    public function getImporte(): ?int
    {
        return $this->importe;
    }

    public function setImporte(int $importe): self
    {
        $this->importe = $importe;

        return $this;
    }

    public function getLastDate(): ?\DateTimeInterface
    {
        return $this->lastDate;
    }

    public function setLastDate(\DateTimeInterface $lastDate): self
    {
        $this->lastDate = $lastDate;

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
            $transferencia->setReporteTransferencia($this);
        }

        return $this;
    }

    public function removeTransferencia(TnTransferencia $transferencia): self
    {
        if ($this->transferencias->contains($transferencia)) {
            $this->transferencias->removeElement($transferencia);
            // set the owning side to null (unless already changed)
            if ($transferencia->getReporteTransferencia() === $this) {
                $transferencia->setReporteTransferencia(null);
            }
        }

        return $this;
    }

    public function __toString()
    {
        return $this->created->format(date('d.m.Y H:i:s'));
    }

    public function getRepartidor(): ?TnRepartidor
    {
        return $this->repartidor;
    }

    public function setRepartidor(?TnRepartidor $repartidor): self
    {
        $this->repartidor = $repartidor;

        return $this;
    }
}
