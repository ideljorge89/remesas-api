<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TnReporteEnvioRepository")
 */
class TnReporteEnvio
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
    private $totalRemesas;

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
     * @ORM\ManyToOne(targetEntity="App\Entity\TnDistribuidor", inversedBy="reportesEnvio")
     */
    private $distribuidor;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $token;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TnRemesa", mappedBy="reporteEnvio")
     */
    private $remesas;

    public function __construct()
    {
        $this->remesas = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTotalRemesas(): ?int
    {
        return $this->totalRemesas;
    }

    public function setTotalRemesas(int $totalRemesas): self
    {
        $this->totalRemesas = $totalRemesas;

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

    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(?\DateTimeInterface $created): self
    {
        $this->created = $created;

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
            $remesa->setReporteEnvio($this);
        }

        return $this;
    }

    public function removeRemesa(TnRemesa $remesa): self
    {
        if ($this->remesas->contains($remesa)) {
            $this->remesas->removeElement($remesa);
            // set the owning side to null (unless already changed)
            if ($remesa->getReporteEnvio() === $this) {
                $remesa->setReporteEnvio(null);
            }
        }

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

    public function __toString()
    {
       return $this->created->format(date('d.m.Y H:i:s'));
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
