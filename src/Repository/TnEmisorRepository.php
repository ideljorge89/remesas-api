<?php

namespace App\Repository;

use App\Entity\TnEmisor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method TnEmisor|null find($id, $lockMode = null, $lockVersion = null)
 * @method TnEmisor|null findOneBy(array $criteria, array $orderBy = null)
 * @method TnEmisor[]    findAll()
 * @method TnEmisor[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TnEmisorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TnEmisor::class);
    }

    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('em')
            ->andWhere('em.u = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    public function findEmisoresAgencia($tnUser)
    {
        $dql = "SELECT us.id FROM App:TnAgente ag JOIN ag.usuario us WHERE ag.agencia = :agcs";
        $arrayId = $this->getEntityManager()->createQuery($dql)->setParameter('agcs', $tnUser->getAgencia())->getResult();
        $ids = [$tnUser->getId()];
        foreach ($arrayId as $item) {
            $ids[] = $item['id'];
        }
        $query = $this->createQueryBuilder('em')
            ->join('em.usuario', 'usuario')
            ->where('em.enabled = true')
            ->andWhere('usuario.id IN (:users) or usuario.id = :useragc')
            ->orderBy('em.created', 'DESC')
            ->setParameter('users', $ids)
            ->setParameter('useragc', $tnUser->getId());

        return $query->getQuery()->getResult();
    }
}
