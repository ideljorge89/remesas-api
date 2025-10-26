<?php

namespace App\Repository;

use App\Entity\TnDocumento;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method TnDocumento|null find($id, $lockMode = null, $lockVersion = null)
 * @method TnDocumento|null findOneBy(array $criteria, array $orderBy = null)
 * @method TnDocumento[]    findAll()
 * @method TnDocumento[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TnDocumentoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TnDocumento::class);
    }

    // /**
    //  * @return TnDocumento[] Returns an array of TnDocumento objects
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
    public function findOneBySomeField($value): ?TnDocumento
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
