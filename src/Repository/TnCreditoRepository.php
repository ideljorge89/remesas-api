<?php

namespace App\Repository;

use App\Entity\TnCredito;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method TnCredito|null find($id, $lockMode = null, $lockVersion = null)
 * @method TnCredito|null findOneBy(array $criteria, array $orderBy = null)
 * @method TnCredito[]    findAll()
 * @method TnCredito[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TnCreditoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TnCredito::class);
    }

    // /**
    //  * @return TnCredito[] Returns an array of TnCredito objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?TnCredito
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
