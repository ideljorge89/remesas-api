<?php

namespace App\Repository;

use App\Entity\NmGrupoPagoTransfAgente;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method NmGrupoPagoTransfAgente|null find($id, $lockMode = null, $lockVersion = null)
 * @method NmGrupoPagoTransfAgente|null findOneBy(array $criteria, array $orderBy = null)
 * @method NmGrupoPagoTransfAgente[]    findAll()
 * @method NmGrupoPagoTransfAgente[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NmGrupoPagoTransfAgenteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NmGrupoPagoTransfAgente::class);
    }

    public function grupoPagoAgente($agente, $moneda)
    {
        $query = $this->createQueryBuilder('grupo')
            ->join('grupo.agentes', 'agente')
            ->join('grupo.moneda', 'moneda')
            ->where('agente.id = :agt')
            ->andWhere('moneda.id = :mon')
            ->setParameter('agt', $agente)
            ->setParameter('mon', $moneda)
            ->setMaxResults(1);

        $result = $query->getQuery()->getResult();

        return count($result) > 0 ? $result[0] : null;
    }
}
