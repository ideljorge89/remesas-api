<?php

namespace App\Repository;

use App\Entity\TnReporteEnvio;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method TnReporteEnvio|null find($id, $lockMode = null, $lockVersion = null)
 * @method TnReporteEnvio|null findOneBy(array $criteria, array $orderBy = null)
 * @method TnReporteEnvio[]    findAll()
 * @method TnReporteEnvio[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TnReporteEnvioRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TnReporteEnvio::class);
    }

    public function findLastReporteEnvio($distribuidor)
    {
        $result = $this->createQueryBuilder('t')
            ->where('t.distribuidor = :dist')
            ->setParameter('dist', $distribuidor)
            ->orderBy('t.created', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        return count($result) > 0 ? $result[0] : null;
    }
}
