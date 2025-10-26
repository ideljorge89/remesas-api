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
 *     "search_remesas_update"={"security"="is_granted('ROLE_AGENCIA')","method"="POST","route_name"="search_remesas_update"}
 * })
 */
class RemesaUpdate
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
     * @return RemesaUpdate
     */
    public function setReferencia(string $Referencia): RemesaUpdate
    {
        $this->Referencia = $Referencia;
        return $this;
    }
}
