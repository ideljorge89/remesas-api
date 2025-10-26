<?php

namespace App\Repository;

use App\Entity\TnSaldoAgente;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method TnSaldoAgente|null find($id, $lockMode = null, $lockVersion = null)
 * @method TnSaldoAgente|null findOneBy(array $criteria, array $orderBy = null)
 * @method TnSaldoAgente[]    findAll()
 * @method TnSaldoAgente[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TnSaldoAgenteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TnSaldoAgente::class);
    }

    // /**
    //  * @return TnSaldoAgente[] Returns an array of TnSaldoAgente objects
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
    public function findOneBySomeField($value): ?TnSaldoAgente
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
