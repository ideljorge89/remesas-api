<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Gedmo\Loggable()
 * @ORM\Entity(repositoryClass="App\Repository\TnSaldoAgenteRepository")
 */
class TnSaldoAgente
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
     * @ORM\ManyToOne(targetEntity="App\Entity\TnAgente", inversedBy="saldoMonedas")
     */
    private $agente;

    /**
     * @Assert\NotNull()
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\NmGrupoPagoAgente", inversedBy="saldoMonedas")
     */
    private $grupoPagoAgente;

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

    public function getAgente(): ?TnAgente
    {
        return $this->agente;
    }

    public function setAgente(?TnAgente $agente): self
    {
        $this->agente = $agente;

        return $this;
    }

    public function getGrupoPagoAgente(): ?NmGrupoPagoAgente
    {
        return $this->grupoPagoAgente;
    }

    public function setGrupoPagoAgente(?NmGrupoPagoAgente $grupoPagoAgente): self
    {
        $this->grupoPagoAgente = $grupoPagoAgente;

        return $this;
    }
}
