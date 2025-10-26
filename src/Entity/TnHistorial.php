<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TnHistorialRepository")
 */
class TnHistorial
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\TnFactura", inversedBy="historial")
     * @Assert\NotNull()
     */
    private $factura;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\NmEstado", inversedBy="historial")
     * @Assert\NotNull()
     */
    private $estado;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $data;

    /**
     * @var \DateTime|null
     * @ORM\Column(name="created",type="datetime",nullable=true)
     * @Gedmo\Timestampable(on="create")
     */
    private $created;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getData(): ?string
    {
        return $this->data;
    }

    public function setData(?string $data): self
    {
        $this->data = $data;

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

    public function getFactura(): ?TnFactura
    {
        return $this->factura;
    }

    public function setFactura(?TnFactura $factura): self
    {
        $this->factura = $factura;

        return $this;
    }

    public function getEstado(): ?NmEstado
    {
        return $this->estado;
    }

    public function setEstado(?NmEstado $estado): self
    {
        $this->estado = $estado;

        return $this;
    }
}
