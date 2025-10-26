<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Gedmo\Loggable()
 * @ORM\Entity(repositoryClass="App\Repository\TnSaldoAgenciaRepository")
 */
class TnSaldoAgencia
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Assert\NotNull()
     * @Gedmo\Versioned()
     * @ORM\Column(type="float")
     */
    private $saldo;

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
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\TnAgencia", inversedBy="saldoMonedas")
     */
    private $agencia;

    /**
     * @Assert\NotNull()
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\NmGrupoPago", inversedBy="saldoMonedas")
     */
    private $grupoPago;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSaldo(): ?float
    {
        return $this->saldo;
    }

    public function setSaldo(float $saldo): self
    {
        $this->saldo = $saldo;

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

    public function getAgencia(): ?TnAgencia
    {
        return $this->agencia;
    }

    public function setAgencia(?TnAgencia $agencia): self
    {
        $this->agencia = $agencia;

        return $this;
    }

    public function getGrupoPago(): ?NmGrupoPago
    {
        return $this->grupoPago;
    }

    public function setGrupoPago(?NmGrupoPago $grupoPago): self
    {
        $this->grupoPago = $grupoPago;

        return $this;
    }
}
