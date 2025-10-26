<?php

namespace App\Repository;

use App\Entity\TnCierreDistribuidor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method TnCierreDistribuidor|null find($id, $lockMode = null, $lockVersion = null)
 * @method TnCierreDistribuidor|null findOneBy(array $criteria, array $orderBy = null)
 * @method TnCierreDistribuidor[]    findAll()
 * @method TnCierreDistribuidor[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TnCierreDistribuidorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TnCierreDistribuidor::class);
    }

    public function findCierresDistribuidorParams($params)
    {
        $query = $this->createQueryBuilder('cierre')
            ->where('cierre.created >= :fi and cierre.created <= :ff')
            ->setParameter('fi', $params['fi'])
            ->setParameter('ff', $params['ff'])
            ->orderBy('cierre.created', "ASC");

        if (array_key_exists('distribuidores', $params)) {
            $query->join('cierre.distribuidor', 'distribuidor')
                ->andWhere('distribuidor.id IN (:dist)')
                ->setParameter('dist', $params['distribuidores']);
        }

        if (array_key_exists('monedas', $params)) {
            $query->join('cierre.moneda', 'moneda')
                ->andWhere('moneda.id IN (:mon)')
                ->setParameter('mon', $params['monedas']);
        }

        return $query->getQuery()->getResult();
    }
}
