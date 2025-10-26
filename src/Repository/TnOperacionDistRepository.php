<?php

namespace App\Repository;

use App\Entity\NmMoneda;
use App\Entity\TnDistribuidor;
use App\Entity\TnOperacionDist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method TnOperacionDist|null find($id, $lockMode = null, $lockVersion = null)
 * @method TnOperacionDist|null findOneBy(array $criteria, array $orderBy = null)
 * @method TnOperacionDist[]    findAll()
 * @method TnOperacionDist[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TnOperacionDistRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TnOperacionDist::class);
    }

    public function findOperacionesDistribuidor(TnDistribuidor $tnDistribuidor, NmMoneda $nmMoneda = null)
    {
        $query = $this->createQueryBuilder('o')
            ->where('o.distribuidor = :dist')
            ->setParameter('dist', $tnDistribuidor)
            ->orderBy('o.created', "DESC");

        if ($nmMoneda != null) {
            $query->andWhere('o.moneda = :mon')
                ->setParameter('mon', $nmMoneda);
        }

        return $query->getQuery()->getResult();
    }

    public function findOperacionesDistribuidorParams($tnDistribuidor, $nmMoneda, $inicio, $fin, $tipo)
    {
        $query = $this->createQueryBuilder('o')
            ->select('SUM(o.importe) total')
            ->where('o.distribuidor = :dist')
            ->andWhere('o.moneda = :mon')
            ->andWhere('o.created >= :ini and o.created <= :fin')
            ->andWhere('o.tipo = :tipoOper')
            ->setParameter('dist', $tnDistribuidor)
            ->setParameter('ini', $inicio)
            ->setParameter('fin', $fin)
            ->setParameter('tipoOper', $tipo)
            ->setParameter('mon', $nmMoneda);

        return $query->getQuery()->getSingleScalarResult();
    }
}
