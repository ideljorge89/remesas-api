<?php

namespace App\Repository;

use App\Entity\TnReporteTransferencia;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method TnReporteTransferencia|null find($id, $lockMode = null, $lockVersion = null)
 * @method TnReporteTransferencia|null findOneBy(array $criteria, array $orderBy = null)
 * @method TnReporteTransferencia[]    findAll()
 * @method TnReporteTransferencia[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TnReporteTransferenciaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TnReporteTransferencia::class);
    }

    public function findLastReporteEnvioTransferencia()
    {
        $result = $this->createQueryBuilder('t')
            ->orderBy('t.created', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        return count($result) > 0 ? $result[0] : null;
    }

    public function findLastReporteEnvioTransferenciaRepartidor($repartidor)
    {
        $result = $this->createQueryBuilder('t')
            ->where('t.repartidor = :rept')
            ->setParameter('rept', $repartidor)
            ->orderBy('t.created', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        return count($result) > 0 ? $result[0] : null;
    }
}
