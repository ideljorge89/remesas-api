<?php

namespace App\Model\Api;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use App\Validator\Constraints\MinimalProperties;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * @ApiResource(attributes={
 *     "security"="is_granted('ROLE_AGENCIA')",
 *     "validation_groups"={"register"}},
 *     itemOperations={},
 *     collectionOperations={
 *     "search_transferencias_update"={"security"="is_granted('ROLE_AGENCIA')","method"="POST","route_name"="search_transferencias_update"}
 * })
 */
class TransferenciaUpdate
{
    /**
     * @var string
     * @Assert\NotBlank(groups={"register"})
     */
    protected $Referencia;

    /**
     * @return string
     */
    public function getReferencia(): string
    {
        return $this->Referencia;
    }

    /**
     * @param string $Referencia
     * @return TransferenciaUpdate
     */
    public function setReferencia(string $Referencia): TransferenciaUpdate
    {
        $this->Referencia = $Referencia;
        return $this;
    }
}
