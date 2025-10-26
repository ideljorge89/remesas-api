<?php

namespace App\Repository;

use App\Entity\TnOperacionAgenciaTransf;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method TnOperacionAgenciaTransf|null find($id, $lockMode = null, $lockVersion = null)
 * @method TnOperacionAgenciaTransf|null findOneBy(array $criteria, array $orderBy = null)
 * @method TnOperacionAgenciaTransf[]    findAll()
 * @method TnOperacionAgenciaTransf[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TnOperacionAgenciaTransfRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TnOperacionAgenciaTransf::class);
    }

    // /**
    //  * @return TnOperacionAgenciaTransf[] Returns an array of TnOperacionAgenciaTransf objects
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
    public function findOneBySomeField($value): ?TnOperacionAgenciaTransf
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
