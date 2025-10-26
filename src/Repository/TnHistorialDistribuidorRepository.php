<?php

namespace App\Repository;

use App\Entity\TnDistribuidor;
use App\Entity\TnHistorialDistribuidor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method TnHistorialDistribuidor|null find($id, $lockMode = null, $lockVersion = null)
 * @method TnHistorialDistribuidor|null findOneBy(array $criteria, array $orderBy = null)
 * @method TnHistorialDistribuidor[]    findAll()
 * @method TnHistorialDistribuidor[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TnHistorialDistribuidorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TnHistorialDistribuidor::class);
    }

    public function findHistorialesDistribuidorEfectivas(TnDistribuidor $tnDistribuidor)
    {
        $query = $this->createQueryBuilder('historial')
            ->where('historial.distribuidor = :dist')
            ->andWhere('historial.estado = :est')
            ->andWhere('historial.cierre IS NULL')
            ->setParameter('dist', $tnDistribuidor)
            ->setParameter('est', TnHistorialDistribuidor::ESTADO_EFECTIVA);

        if ($tnDistribuidor->getLastCierre() != null) {
            $query->andWhere('historial.created > :fecha')
                ->setParameter('fecha', $tnDistribuidor->getLastCierre());
        }

        return $query->getQuery()->getResult();
    }

    public function findHistorialesDistribuidorAll(TnDistribuidor $tnDistribuidor)
    {
        $query = $this->createQueryBuilder('historial')
            ->where('historial.distribuidor = :dist')
            ->setParameter('dist', $tnDistribuidor);

        if ($tnDistribuidor->getLastCierre() != null) {
            $query->andWhere('historial.created > :fecha')
                ->setParameter('fecha', $tnDistribuidor->getLastCierre());
        }

        return $query->getQuery()->getResult();
    }

    public function findHistorialesDistribuidorParams($tnDistribuidor, $moneda, $inicio, $fin)
    {
        $query = $this->createQueryBuilder('historial')
            ->where('historial.distribuidor = :dist')
            ->andWhere('historial.monedaRemesa = :mon')
            ->andWhere('historial.created >= :ini and historial.created <= :fin')
            ->setParameter('dist', $tnDistribuidor)
            ->setParameter('ini', $inicio)
            ->setParameter('fin', $fin)
            ->setParameter('mon', $moneda);

        return $query->getQuery()->getResult();
    }

    public function findHistorialesDistribuidorParamsCanceladas($tnDistribuidor, $moneda, $inicio, $fin)
    {
        $query = $this->createQueryBuilder('historial')
            ->where('historial.distribuidor = :dist')
            ->andWhere('historial.monedaRemesa = :mon')
            ->andWhere('historial.estado = :est')
            ->andWhere('historial.canceladaAt >= :ini and historial.canceladaAt <= :fin')
            ->setParameter('dist', $tnDistribuidor)
            ->setParameter('est', TnHistorialDistribuidor::ESTADO_CANCELADA)
            ->setParameter('ini', $inicio)
            ->setParameter('fin', $fin)
            ->setParameter('mon', $moneda);

        return $query->getQuery()->getResult();
    }


    public function reportHistorialesDistribuidores($params)
    {
        $query = $this->createQueryBuilder('historial')
            ->join('historial.remesa', 'remesa')
            ->join('remesa.factura', 'factura')
            ->where('historial.created >= :fi and historial.created <= :ff')
            ->setParameter('fi', $params['fi'])
            ->setParameter('ff', $params['ff'])
            ->orderBy('historial.created', "ASC");

        if (array_key_exists('distribuidores', $params)) {
            $query->join('historial.distribuidor', 'distribuidor')
                ->andWhere('distribuidor.id IN (:dist)')
                ->setParameter('dist', $params['distribuidores']);
        }

        if (array_key_exists('monedas', $params)) {
            $query->join('historial.monedaRemesa', 'moneda')
                ->andWhere('moneda.id IN (:mon)')
                ->setParameter('mon', $params['monedas']);
        }

        return $query->getQuery()->getResult();
    }
}
