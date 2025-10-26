<?php

namespace App\Repository;

use App\Entity\TnSubDistribuidor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method TnSubDistribuidor|null find($id, $lockMode = null, $lockVersion = null)
 * @method TnSubDistribuidor|null findOneBy(array $criteria, array $orderBy = null)
 * @method TnSubDistribuidor[]    findAll()
 * @method TnSubDistribuidor[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TnSubDistribuidorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TnSubDistribuidor::class);
    }

    // /**
    //  * @return TnSubDistribuidor[] Returns an array of TnSubDistribuidor objects
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
    public function findOneBySomeField($value): ?TnSubDistribuidor
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
