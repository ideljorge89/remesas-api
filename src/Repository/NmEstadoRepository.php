<?php

namespace App\Repository;

use App\Entity\NmEstado;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method NmEstado|null find($id, $lockMode = null, $lockVersion = null)
 * @method NmEstado|null findOneBy(array $criteria, array $orderBy = null)
 * @method NmEstado[]    findAll()
 * @method NmEstado[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NmEstadoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NmEstado::class);
    }

    // /**
    //  * @return NmEstado[] Returns an array of NmEstado objects
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
    public function findOneBySomeField($value): ?NmEstado
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
