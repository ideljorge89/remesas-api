<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TnSubDistribuidorRepository")
 */
class TnSubDistribuidor
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
     * @ORM\Column(type="string", length=255)
     */
    private $phone;

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
     * @ORM\ManyToOne(targetEntity="App\Entity\TnDistribuidor", inversedBy="subDistribuidores")
     */
    private $distribuidor;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TnRemesa", mappedBy="subDistribuidor")
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

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): self
    {
        $this->nombre = $nombre;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): self
    {
        $this->phone = $phone;

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
            $remesa->setSubDistribuidor($this);
        }

        return $this;
    }

    public function removeRemesa(TnRemesa $remesa): self
    {
        if ($this->remesas->contains($remesa)) {
            $this->remesas->removeElement($remesa);
            // set the owning side to null (unless already changed)
            if ($remesa->getSubDistribuidor() === $this) {
                $remesa->setSubDistribuidor(null);
            }
        }

        return $this;
    }
}
