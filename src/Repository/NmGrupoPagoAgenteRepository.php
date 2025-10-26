<?php

namespace App\Repository;

use App\Entity\NmGrupoPagoAgente;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method NmGrupoPagoAgente|null find($id, $lockMode = null, $lockVersion = null)
 * @method NmGrupoPagoAgente|null findOneBy(array $criteria, array $orderBy = null)
 * @method NmGrupoPagoAgente[]    findAll()
 * @method NmGrupoPagoAgente[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NmGrupoPagoAgenteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NmGrupoPagoAgente::class);
    }

    public function grupoPagoAgente($agente, $moneda)
    {
        $query = $this->createQueryBuilder('grupo')
            ->join('grupo.agentes', 'agente')
            ->join('grupo.moneda', 'moneda')
            ->where('agente.id = :agt')
            ->andWhere('moneda.id = :mon')
            ->setParameter('agt', $agente)
            ->setParameter('mon', $moneda);

        return $query->getQuery()->getOneOrNullResult();
    }
}
