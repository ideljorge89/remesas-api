<?php

namespace App\Repository;

use App\Entity\TnRepartidor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method TnRepartidor|null find($id, $lockMode = null, $lockVersion = null)
 * @method TnRepartidor|null findOneBy(array $criteria, array $orderBy = null)
 * @method TnRepartidor[]    findAll()
 * @method TnRepartidor[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TnRepartidorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TnRepartidor::class);
    }

    // /**
    //  * @return TnRepartidor[] Returns an array of TnRepartidor objects
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
    public function findOneBySomeField($value): ?TnRepartidor
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
