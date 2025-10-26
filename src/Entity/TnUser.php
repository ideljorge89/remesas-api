<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TnUserRepository")
 * @ORM\Table(name="tn_user")
 */
class TnUser extends BaseUser
{

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;


    /**
     * @ORM\Column(name="avatar", type="text", nullable=true)
     */
    private $avatar;


    /**
     * @var string
     *
     * @ORM\Column(name="avatar_file_path", type="string", length=255, nullable=true)
     */
    private $filePath;

    /**
     * @ORM\Column(name="joined_at", type="datetime", nullable=true)
     */
    private $joinedAt;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $created;

    /**
     * @ORM\Column(name="deletedAt", type="datetime", nullable=true)
     */
    private $deletedAt;

    /**
     * Date/Time of the last activity
     *
     * @var \Datetime
     * @ORM\Column(name="last_activity_at", type="datetime", nullable=true)
     */
    protected $lastActivityAt;

    /**
     * @Assert\Length(
     *   min = 1,
     *   max = 5,
     * )
     * @ORM\Column(type="float", nullable=true)
     */
    private $porcentaje;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $auth;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $token;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_admin", type="boolean", nullable=true)
     */
    private $isAdmin;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\TnAgencia", inversedBy="usuario")
     */
    private $agencia;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\TnAgente", inversedBy="usuario")
     */
    private $agente;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\TnDistribuidor", inversedBy="usuario")
     */
    private $distribuidor;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\NmGrupoPagoAgente", mappedBy="usuario")
     */
    private $gruposPagoAgente;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\NmGrupoPagoTransfAgente", mappedBy="usuario")
     */
    private $gruposPagoTransfAgente;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TnEmisor", mappedBy="usuario")
     */
    private $emisores;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TnDestinatario", mappedBy="usuario")
     */
    private $destinatarios;

    public function __construct()
    {
        parent::__construct();
        $this->gruposPagoAgente = new ArrayCollection();
        $this->emisores = new ArrayCollection();
        $this->destinatarios = new ArrayCollection();
        $this->gruposPagoTransfAgente = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): self
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function getfilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(?string $filePath): self
    {
        $this->filePath = $filePath;

        return $this;
    }

    public function getJoinedAt(): ?\DateTimeInterface
    {
        return $this->joinedAt;
    }

    public function setJoinedAt(?\DateTimeInterface $joinedAt): self
    {
        $this->joinedAt = $joinedAt;

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

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeInterface $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    public function getLastActivityAt(): ?\DateTimeInterface
    {
        return $this->lastActivityAt;
    }

    public function setLastActivityAt(?\DateTimeInterface $lastActivityAt): self
    {
        $this->lastActivityAt = $lastActivityAt;

        return $this;
    }

    public function getIsAdmin(): ?bool
    {
        return $this->isAdmin;
    }

    public function setIsAdmin(?bool $isAdmin): self
    {
        $this->isAdmin = $isAdmin;

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

    public function getAgencia(): ?TnAgencia
    {
        return $this->agencia;
    }

    public function setAgencia(?TnAgencia $agencia): self
    {
        $this->agencia = $agencia;

        return $this;
    }

    /**
     * @return Collection|NmGrupoPagoAgente[]
     */
    public function getGruposPagoAgente(): Collection
    {
        return $this->gruposPagoAgente;
    }

    public function addGruposPagoAgente(NmGrupoPagoAgente $gruposPagoAgente): self
    {
        if (!$this->gruposPagoAgente->contains($gruposPagoAgente)) {
            $this->gruposPagoAgente[] = $gruposPagoAgente;
            $gruposPagoAgente->setUsuario($this);
        }

        return $this;
    }

    public function removeGruposPagoAgente(NmGrupoPagoAgente $gruposPagoAgente): self
    {
        if ($this->gruposPagoAgente->contains($gruposPagoAgente)) {
            $this->gruposPagoAgente->removeElement($gruposPagoAgente);
            // set the owning side to null (unless already changed)
            if ($gruposPagoAgente->getUsuario() === $this) {
                $gruposPagoAgente->setUsuario(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|TnEmisor[]
     */
    public function getEmisores(): Collection
    {
        return $this->emisores;
    }

    public function addEmisore(TnEmisor $emisore): self
    {
        if (!$this->emisores->contains($emisore)) {
            $this->emisores[] = $emisore;
            $emisore->setUsuario($this);
        }

        return $this;
    }

    public function removeEmisore(TnEmisor $emisore): self
    {
        if ($this->emisores->contains($emisore)) {
            $this->emisores->removeElement($emisore);
            // set the owning side to null (unless already changed)
            if ($emisore->getUsuario() === $this) {
                $emisore->setUsuario(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|TnDestinatario[]
     */
    public function getDestinatarios(): Collection
    {
        return $this->destinatarios;
    }

    public function addDestinatario(TnDestinatario $destinatario): self
    {
        if (!$this->destinatarios->contains($destinatario)) {
            $this->destinatarios[] = $destinatario;
            $destinatario->setUsuario($this);
        }

        return $this;
    }

    public function removeDestinatario(TnDestinatario $destinatario): self
    {
        if ($this->destinatarios->contains($destinatario)) {
            $this->destinatarios->removeElement($destinatario);
            // set the owning side to null (unless already changed)
            if ($destinatario->getUsuario() === $this) {
                $destinatario->setUsuario(null);
            }
        }

        return $this;
    }

    public function getPorcentaje(): ?float
    {
        return $this->porcentaje;
    }

    public function setPorcentaje(float $porcentaje): self
    {
        $this->porcentaje = $porcentaje;

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

    public function getAuth(): ?bool
    {
        return $this->auth;
    }

    public function setAuth(bool $auth): self
    {
        $this->auth = $auth;

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
     * @return Collection|NmGrupoPagoTransfAgente[]
     */
    public function getGruposPagoTransfAgente(): Collection
    {
        return $this->gruposPagoTransfAgente;
    }

    public function addGruposPagoTransfAgente(NmGrupoPagoTransfAgente $gruposPagoTransfAgente): self
    {
        if (!$this->gruposPagoTransfAgente->contains($gruposPagoTransfAgente)) {
            $this->gruposPagoTransfAgente[] = $gruposPagoTransfAgente;
            $gruposPagoTransfAgente->setUsuario($this);
        }

        return $this;
    }

    public function removeGruposPagoTransfAgente(NmGrupoPagoTransfAgente $gruposPagoTransfAgente): self
    {
        if ($this->gruposPagoTransfAgente->contains($gruposPagoTransfAgente)) {
            $this->gruposPagoTransfAgente->removeElement($gruposPagoTransfAgente);
            // set the owning side to null (unless already changed)
            if ($gruposPagoTransfAgente->getUsuario() === $this) {
                $gruposPagoTransfAgente->setUsuario(null);
            }
        }

        return $this;
    }

}
