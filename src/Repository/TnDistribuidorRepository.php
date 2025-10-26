<?php

namespace App\Repository;

use App\Entity\TnDistribuidor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method TnDistribuidor|null find($id, $lockMode = null, $lockVersion = null)
 * @method TnDistribuidor|null findOneBy(array $criteria, array $orderBy = null)
 * @method TnDistribuidor[]    findAll()
 * @method TnDistribuidor[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TnDistribuidorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TnDistribuidor::class);
    }

    // /**
    //  * @return TnDistribuidor[] Returns an array of TnDistribuidor objects
    //  */

    /*
    public function findOneBySomeField($value): ?TnDistribuidor
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function findByZona($value)
    {
        return $this->createQueryBuilder('dt')
            ->join('dt.zonas', 'zona')
            ->where('zona.id = :val')
            ->andWhere('dt.enabled = true')
            ->setParameter('val', $value)
            ->getQuery()
            ->getResult();
    }

    public function findByDistZona($dist, $value)
    {
        return $this->createQueryBuilder('dt')
            ->join('dt.zonas', 'zona')
            ->where('zona.id = :val')
            ->andWhere('dt.id = :dtrb')
            ->andWhere('dt.enabled = true')
            ->setParameter('dtrb', $dist)
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
