<?php

namespace App\Repository;

use App\Entity\TnOperacionAgente;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method TnOperacionAgente|null find($id, $lockMode = null, $lockVersion = null)
 * @method TnOperacionAgente|null findOneBy(array $criteria, array $orderBy = null)
 * @method TnOperacionAgente[]    findAll()
 * @method TnOperacionAgente[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TnOperacionAgenteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TnOperacionAgente::class);
    }

    // /**
    //  * @return TnOperacionAgente[] Returns an array of TnOperacionAgente objects
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
    public function findOneBySomeField($value): ?TnOperacionAgente
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
