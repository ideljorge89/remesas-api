<?php

namespace App\Repository;

use App\Entity\NmEstadoTransferencia;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method NmEstadoTransferencia|null find($id, $lockMode = null, $lockVersion = null)
 * @method NmEstadoTransferencia|null findOneBy(array $criteria, array $orderBy = null)
 * @method NmEstadoTransferencia[]    findAll()
 * @method NmEstadoTransferencia[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NmEstadoTransferenciaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NmEstadoTransferencia::class);
    }

    // /**
    //  * @return NmEstadoTransferencia[] Returns an array of NmEstadoTransferencia objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('n.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?NmEstadoTransferencia
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
