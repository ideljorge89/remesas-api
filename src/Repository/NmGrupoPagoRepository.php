<?php

namespace App\Repository;

use App\Entity\NmGrupoPago;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method NmGrupoPago|null find($id, $lockMode = null, $lockVersion = null)
 * @method NmGrupoPago|null findOneBy(array $criteria, array $orderBy = null)
 * @method NmGrupoPago[]    findAll()
 * @method NmGrupoPago[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NmGrupoPagoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NmGrupoPago::class);
    }

    public function grupoPagoAgencia($agencia, $moneda)
    {
        $query = $this->createQueryBuilder('grupo')
            ->join('grupo.agencias', 'agencia')
            ->join('grupo.moneda', 'moneda')
            ->where('agencia.id = :agc')
            ->andWhere('moneda.id = :mon')
            ->setParameter('agc', $agencia)
            ->setParameter('mon', $moneda);

        return $query->getQuery()->getOneOrNullResult();
    }
}
