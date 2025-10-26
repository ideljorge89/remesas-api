<?php

namespace App\Repository;

use App\Entity\NmMunicipio;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method NmMunicipio|null find($id, $lockMode = null, $lockVersion = null)
 * @method NmMunicipio|null findOneBy(array $criteria, array $orderBy = null)
 * @method NmMunicipio[]    findAll()
 * @method NmMunicipio[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NmMunicipioRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NmMunicipio::class);
    }

    public function findMunicipioByData($value)
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.name IN (:val)')
            ->setParameter('val', $value)
            ->getQuery()
            ->getResult();
    }

    public function findMunicipioUpdateCodigo($value, $prov)
    {
        return $this->createQueryBuilder('n')
            ->join('n.provincia', 'p')
            ->andWhere('n.name LIKE :val')
            ->andWhere('p.name LIKE :prov')
            ->setParameter('val', '%' . $value . "%")
            ->setParameter('prov', '%' . $prov . "%")
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findMunicipioByDataArray($value)
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.name IN (:val)')
            ->setParameter('val', explode(' ', $value))
            ->getQuery()
            ->getResult();
    }

    public function findAllMunicipiosAsArray()
    {
        return $this->createQueryBuilder('m')
            ->select('m.name')
            ->getQuery()
            ->getArrayResult();
    }
}
