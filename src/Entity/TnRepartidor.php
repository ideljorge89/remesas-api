<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * @ORM\Entity(repositoryClass="App\Repository\TnRepartidorRepository")
 */
class TnRepartidor
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
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $observacion;

    /**
     * @ORM\Column(type="integer")
     */
    private $comision;

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
     * @ORM\OneToMany(targetEntity="App\Entity\TnTransferencia", mappedBy="repartidor")
     */
    private $transferencias;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TnReporteTransferencia", mappedBy="repartidor")
     */
    private $reportesEnvio;

    public function __construct()
    {
        $this->transferencias = new ArrayCollection();
        $this->reportesEnvio = new ArrayCollection();
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

    public function getObservacion(): ?string
    {
        return $this->observacion;
    }

    public function setObservacion(?string $observacion): self
    {
        $this->observacion = $observacion;

        return $this;
    }

    public function getComision(): ?int
    {
        return $this->comision;
    }

    public function setComision(int $comision): self
    {
        $this->comision = $comision;

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
            $transferencia->setRepartidor($this);
        }

        return $this;
    }

    public function removeTransferencia(TnTransferencia $transferencia): self
    {
        if ($this->transferencias->contains($transferencia)) {
            $this->transferencias->removeElement($transferencia);
            // set the owning side to null (unless already changed)
            if ($transferencia->getRepartidor() === $this) {
                $transferencia->setRepartidor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|TnReporteTransferencia[]
     */
    public function getReportesEnvio(): Collection
    {
        return $this->reportesEnvio;
    }

    public function addReportesEnvio(TnReporteTransferencia $reportesEnvio): self
    {
        if (!$this->reportesEnvio->contains($reportesEnvio)) {
            $this->reportesEnvio[] = $reportesEnvio;
            $reportesEnvio->setRepartidor($this);
        }

        return $this;
    }

    public function removeReportesEnvio(TnReporteTransferencia $reportesEnvio): self
    {
        if ($this->reportesEnvio->contains($reportesEnvio)) {
            $this->reportesEnvio->removeElement($reportesEnvio);
            // set the owning side to null (unless already changed)
            if ($reportesEnvio->getRepartidor() === $this) {
                $reportesEnvio->setRepartidor(null);
            }
        }

        return $this;
    }

    public function __toString()
    {
        return $this->nombre;
    }
}
