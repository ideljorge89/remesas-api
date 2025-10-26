<?php

namespace App\Repository;

use App\Entity\NmEstado;
use App\Entity\TnApoderadoFactura;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method TnApoderadoFactura|null find($id, $lockMode = null, $lockVersion = null)
 * @method TnApoderadoFactura|null findOneBy(array $criteria, array $orderBy = null)
 * @method TnApoderadoFactura[]    findAll()
 * @method TnApoderadoFactura[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TnApoderadoFacturaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TnApoderadoFactura::class);
    }

    public function findFacturasApoderadoParams($params)
    {
        $query = $this->createQueryBuilder('apodFactura')
            ->join('apodFactura.factura', 'factura')
            ->join('factura.estado', 'estado')
            ->andWhere('factura.fechaContable >= :fi and factura.fechaContable <= :ff')
            ->andWhere('apodFactura.apoderado = :apod')
            ->setParameter('apod', $params['apoderado'])
            ->setParameter('fi', $params['fi'])
            ->setParameter('ff', $params['ff'])
            ->orderBy('factura.fechaContable', "ASC");

        if (array_key_exists('estados', $params)) {
            $query->andWhere('estado.codigo IN (:estados)')
                ->setParameter('estados', $params['estados']);
        } else {
            $query->andWhere('estado.codigo IN (:estados)')
                ->setParameter('estados', NmEstado::getCodeEstados());
        }

        return $query->getQuery()->getResult();
    }

    public function findCostosAgenciaApoderadoParams($params)
    {
        $query = $this->createQueryBuilder('apodFactura')
            ->join('apodFactura.factura', 'factura')
            ->join('factura.estado', 'estado')
            ->andWhere('factura.fechaContable >= :fi and factura.fechaContable <= :ff')
            ->andWhere('apodFactura.apoderado = :apod')
            ->setParameter('apod', $params['apoderado'])
            ->setParameter('fi', $params['fi'])
            ->setParameter('ff', $params['ff'])
            ->orderBy('factura.fechaContable', "ASC");

        if (array_key_exists('agencias', $params)) {
            $query->join('apodFactura.agencia', 'agencia')
                ->andWhere('agencia.id IN (:ags)')
                ->setParameter('ags', $params['agencias']);
        }

        if (array_key_exists('estados', $params)) {
            $query->andWhere('estado.codigo IN (:estados)')
                ->setParameter('estados', $params['estados']);
        } else {
            $query->andWhere('estado.codigo IN (:estados)')
                ->setParameter('estados', NmEstado::getCodeEstados());
        }

        return $query->getQuery()->getResult();
    }
}
