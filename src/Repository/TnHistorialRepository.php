<?php

namespace App\Repository;

use App\Entity\TnHistorial;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method TnHistorial|null find($id, $lockMode = null, $lockVersion = null)
 * @method TnHistorial|null findOneBy(array $criteria, array $orderBy = null)
 * @method TnHistorial[]    findAll()
 * @method TnHistorial[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TnHistorialRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TnHistorial::class);
    }

    // /**
    //  * @return TnHistorial[] Returns an array of TnHistorial objects
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
    public function findOneBySomeField($value): ?TnHistorial
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
