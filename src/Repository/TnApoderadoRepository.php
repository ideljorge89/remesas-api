<?php

namespace App\Repository;

use App\Entity\TnApoderado;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method TnApoderado|null find($id, $lockMode = null, $lockVersion = null)
 * @method TnApoderado|null findOneBy(array $criteria, array $orderBy = null)
 * @method TnApoderado[]    findAll()
 * @method TnApoderado[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TnApoderadoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TnApoderado::class);
    }

    // /**
    //  * @return TnApoderado[] Returns an array of TnApoderado objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    public function findOneByAgency($agencia, $id = null): ?TnApoderado
    {
        $query = $this->createQueryBuilder('t')
            ->where('t.agencia = :agc')
            ->setParameter('agc', $agencia);

        if ($id != null) {
            $query->andWhere('t.id <> :idp')
                ->setParameter('idp', $id);
        }
        return $query->getQuery()->getOneOrNullResult();
    }

    public function findOneBySubordinada($agencia, $id = null): ?TnApoderado
    {
        $query = $this->createQueryBuilder('t')
            ->join('t.subordinadas', 'agencia')
            ->where('agencia.id = :agc')
            ->setParameter('agc', $agencia);

        if ($id != null) {
            $query->andWhere('t.id <> :idp')
                ->setParameter('idp', $id);
        }
        return $query->getQuery()->getOneOrNullResult();
    }
}
