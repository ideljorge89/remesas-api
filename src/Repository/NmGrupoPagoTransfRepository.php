<?php

namespace App\Repository;

use App\Entity\NmGrupoPagoTransf;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method NmGrupoPagoTransf|null find($id, $lockMode = null, $lockVersion = null)
 * @method NmGrupoPagoTransf|null findOneBy(array $criteria, array $orderBy = null)
 * @method NmGrupoPagoTransf[]    findAll()
 * @method NmGrupoPagoTransf[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NmGrupoPagoTransfRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NmGrupoPagoTransf::class);
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
