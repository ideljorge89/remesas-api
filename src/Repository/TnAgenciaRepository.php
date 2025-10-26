<?php

namespace App\Repository;

use App\Entity\TnAgencia;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method TnAgencia|null find($id, $lockMode = null, $lockVersion = null)
 * @method TnAgencia|null findOneBy(array $criteria, array $orderBy = null)
 * @method TnAgencia[]    findAll()
 * @method TnAgencia[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TnAgenciaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TnAgencia::class);
    }


    public function findAgenciaGrupoPago($grupo)
    {
        return $this->createQueryBuilder('agc')
            ->join('agc.gruposPago', 'grupoPago')
            ->andWhere('grupoPago.id = :grupo')
            ->setParameter('grupo', $grupo)
            ->getQuery()
            ->getResult();
    }
}
