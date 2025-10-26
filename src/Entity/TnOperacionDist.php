<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TnOperacionDistRepository")
 */
class TnOperacionDist
{
    const OPERACION_RECIBIDO = 'recibido';
    const OPERACION_TRANSFERIDO = 'transferido';
    const OPERACION_GASTOS = 'gasto';
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", columnDefinition="ENUM('recibido','transferido','gasto')")
     */
    private $tipo;

    /**
     * @Assert\NotNull()
     * @Assert\NotBlank()
     * @ORM\Column(type="float")
     */
    private $importe;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $notas;

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
     * @Assert\NotNull()
     * @ORM\ManyToOne(targetEntity="App\Entity\NmMoneda", inversedBy="operacionesDistribuidor")
     */
    private $moneda;

    /**
     * @Assert\NotNull()
     * @ORM\ManyToOne(targetEntity="App\Entity\TnDistribuidor", inversedBy="operaciones")
     */
    private $distribuidor;

    public function getId(): ?int
    {
        return $this->id;
    }

    public static function getTipos()
    {
        return [
            "Recibido" => 'recibido',
            "Transferido" => 'transferido',
            "Gasto" => 'gasto'
        ];
    }

    public function getTipo(): ?string
    {
        return $this->tipo;
    }

    public function setTipo(string $tipo): self
    {
        $this->tipo = $tipo;

        return $this;
    }

    public function getImporte(): ?float
    {
        return $this->importe;
    }

    public function setImporte(float $importe): self
    {
        $this->importe = $importe;

        return $this;
    }

    public function getNotas(): ?string
    {
        return $this->notas;
    }

    public function setNotas(?string $notas): self
    {
        $this->notas = $notas;

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

    public function getMoneda(): ?NmMoneda
    {
        return $this->moneda;
    }

    public function setMoneda(?NmMoneda $moneda): self
    {
        $this->moneda = $moneda;

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
}
