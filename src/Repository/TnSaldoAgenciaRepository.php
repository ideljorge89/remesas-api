<?php

namespace App\Repository;

use App\Entity\TnSaldoAgencia;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method TnSaldoAgencia|null find($id, $lockMode = null, $lockVersion = null)
 * @method TnSaldoAgencia|null findOneBy(array $criteria, array $orderBy = null)
 * @method TnSaldoAgencia[]    findAll()
 * @method TnSaldoAgencia[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TnSaldoAgenciaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TnSaldoAgencia::class);
    }

    // /**
    //  * @return TnSaldoAgencia[] Returns an array of TnSaldoAgencia objects
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
    public function findOneBySomeField($value): ?TnSaldoAgencia
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
