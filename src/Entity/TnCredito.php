<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @Gedmo\Loggable()
 * @ORM\Entity(repositoryClass="App\Repository\TnCreditoRepository")
 */
class TnCredito
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Gedmo\Versioned()
     *
     * @ORM\Column(type="float")
     */
    private $credito;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $lastCredito;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastCierre;

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
     * @ORM\ManyToOne(targetEntity="App\Entity\TnDistribuidor", inversedBy="creditos")
     */
    private $distribuidor;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\NmMoneda", inversedBy="creditos")
     */
    private $moneda;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getLastCredito(): ?float
    {
        return $this->lastCredito;
    }

    public function setLastCredito(?float $lastCredito): self
    {
        $this->lastCredito = $lastCredito;

        return $this;
    }

    public function getLastCierre(): ?\DateTimeInterface
    {
        return $this->lastCierre;
    }

    public function setLastCierre(?\DateTimeInterface $lastCierre): self
    {
        $this->lastCierre = $lastCierre;

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

    public function getMoneda(): ?NmMoneda
    {
        return $this->moneda;
    }

    public function setMoneda(?NmMoneda $moneda): self
    {
        $this->moneda = $moneda;

        return $this;
    }
}
