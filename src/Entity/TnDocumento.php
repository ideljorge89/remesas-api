<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TnDocumentoRepository")
 */
class TnDocumento
{
    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_REGISTRADO = 'registrado';

    const TIPO_FACTURA_TE = 'factura-TE';
    const TIPO_FACTURA_CA = 'factura-CA';
    const TIPO_FACTURA_TP = 'factura-TP';
    const TIPO_FACTURA_TM = 'factura-TM';
    const TIPO_FACTURA_CMX = 'factura-CMX';
    const TIPO_FACTURA_VCB = 'factura-VCB';
    const TIPO_DESTINATARIO = 'destinatario';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $url;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $estado;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $totalProcesado;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $totalValido;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $totalNoValido;

    /**
     * @ORM\Column(type="array")
     */
    private $validation;

    /**
     * @Assert\NotNull()
     * @ORM\Column(type="string", length=255)
     */
    private $tipo;

    /**
     * @var \DateTime|null
     * @ORM\Column(name="created",type="datetime",nullable=true)
     * @Gedmo\Timestampable(on="create")
     */
    protected $created;

    /**
     * @var \DateTime|null
     * @ORM\Column(name="updated",type="datetime",nullable=true)
     * @Gedmo\Timestampable(on="update")
     */
    protected $updated;

    /**
     * @Assert\NotNull()
     * @ORM\ManyToOne(targetEntity="App\Entity\TnAgencia", inversedBy="documentos")
     */
    protected $agencia;

    /**
     * @Assert\NotNull()
     * @ORM\ManyToOne(targetEntity="App\Entity\TnEmisor", inversedBy="documentos")
     */
    protected $emisor;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getEstado()
    {
        return $this->estado;
    }

    public function setEstado($estado): self
    {
        $this->estado = $estado;

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

    public function getEmisor(): ?TnEmisor
    {
        return $this->emisor;
    }

    public function setEmisor(?TnEmisor $emisor): self
    {
        $this->emisor = $emisor;

        return $this;
    }

    public static function getAllEstados()
    {
        return [
            "pendiente" => 'pendiente',
            "registrado" => 'registrado'
        ];
    }

    public static function getAllTipos()
    {
        return [
            "Destinatario" => 'destinatario',
            "Factura-Caribe" => 'factura-TE',
            "Factura-Canadá" => 'factura-CA',
            "Factura-Tramipro" => 'factura-TP',
            "Factura-Multiple" => 'factura-TM',
            "Factura-CubaMax" => 'factura-CMX',
            "Factura-VaCuba" => 'factura-VCB'
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

    public function getValidation(): ?array
    {
        return $this->validation;
    }

    public function setValidation(array $validation): self
    {
        $this->validation = $validation;

        return $this;
    }

    public function getTotalProcesado(): ?int
    {
        return $this->totalProcesado;
    }

    public function setTotalProcesado(?int $totalProcesado): self
    {
        $this->totalProcesado = $totalProcesado;

        return $this;
    }

    public function getTotalValido(): ?int
    {
        return $this->totalValido;
    }

    public function setTotalValido(?int $totalValido): self
    {
        $this->totalValido = $totalValido;

        return $this;
    }

    public function getTotalNoValido(): ?int
    {
        return $this->totalNoValido;
    }

    public function setTotalNoValido(?int $totalNoValido): self
    {
        $this->totalNoValido = $totalNoValido;

        return $this;
    }
}
