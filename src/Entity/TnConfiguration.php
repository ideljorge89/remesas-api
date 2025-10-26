<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TnConfiguration
 *
 * @ORM\Table(name="tn_configuration")
 * @ORM\Entity(repositoryClass="App\Repository\TnConfigurationRepository")
 */
class TnConfiguration
{
    const FACTURA_CODE= 'factura_code';
    const TRANFERENCIA_CODE= 'transferencia_code';

    const CAMBIO_CUC_USD= 'currency_rate_CUC_USD';
    const CAMBIO_CUP_USD= 'currency_rate_CUP_USD';
    const DIAS_ENTREGA= 'dias_entrega';
    const DEFAULT_LANGUAJE= 'es';
    const PORCENTAJE = 'percent';
    const SALDO_USD = 'saldo_usd';
    const SALDO_CUP = 'saldo_cup';
    const SALDO_EUR = 'saldo_eur';
    const SALDO_CAD = 'saldo_cad';

    const LAST_CHECK_FACTURA = 'last_check_factura';
    const STATUS_NEW_FACTURA = 'status_new_factura';

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="attr", type="string", length=255, unique=true)
     */
    private $attr;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="text", nullable=true)
     */
    private $value;

    /**
     * @var boolean
     *
     * @ORM\Column(name="locked", type="boolean", nullable=true)
     */
    private $locked;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set attr
     *
     * @param string $attr
     * @return TnConfiguration
     */
    public function setAttr($attr)
    {
        $this->attr = $attr;

        return $this;
    }

    /**
     * Get attr
     *
     * @return string 
     */
    public function getAttr()
    {
        return $this->attr;
    }

    /**
     * Set value
     *
     * @param string $value
     * @return TnConfiguration
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string 
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set locked
     *
     * @param boolean $locked
     * @return TnConfiguration
     */
    public function setLocked($locked)
    {
        $this->locked = $locked;

        return $this;
    }

    /**
     * Get locked
     *
     * @return boolean 
     */
    public function getLocked()
    {
        return $this->locked;
    }
}
