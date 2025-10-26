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
 * @ORM\Entity(repositoryClass="App\Repository\NmEstadoTransferenciaRepository")
 */
class NmEstadoTransferencia
{

    const ESTADO_PENDIENTE = '01';
    const ESTADO_ASINGADA = '02';
    const ESTADO_ENVIADO = '03';
    const ESTADO_ENTREGADA = '04';
    const ESTADO_CANCELADA = '05';

    const ESTADO_CANCELADA_ID = 3;

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
     * @ORM\OneToMany(targetEntity="App\Entity\TnTransferencia", mappedBy="estado")
     */
    private $transferencias;

    public function __construct()
    {
        $this->transferencias = new ArrayCollection();
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
            $transferencia->setEstado($this);
        }

        return $this;
    }

    public function removeTransferencia(TnTransferencia $transferencia): self
    {
        if ($this->transferencias->contains($transferencia)) {
            $this->transferencias->removeElement($transferencia);
            // set the owning side to null (unless already changed)
            if ($transferencia->getEstado() === $this) {
                $transferencia->setEstado(null);
            }
        }

        return $this;
    }

    static public function getReportEstados()
    {
        return array(
            '01',
            '02',
            '03'
        );
    }

    static public function getEnviadoEstados()
    {
        return array(
            '03'
        );
    }

    static public function getRepartidoresEstados()
    {
        return array(
            'Pendiente' => '01',
            'Asignada' => '02',
            'Enviada' => '03',
            'Cancelada' => '05'
        );
    }

    static public function getEstados()
    {
        return array(
            'Pendiente' => '01',
            'Asignada' => '02',
            'Enviada' => '03',
            'Cancelada' => '05',
        );
    }

    public function __toString()
    {
        return $this->nombre;
    }
}
