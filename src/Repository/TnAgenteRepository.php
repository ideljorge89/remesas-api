<?php

namespace App\Repository;

use App\Entity\TnAgente;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method TnAgente|null find($id, $lockMode = null, $lockVersion = null)
 * @method TnAgente|null findOneBy(array $criteria, array $orderBy = null)
 * @method TnAgente[]    findAll()
 * @method TnAgente[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TnAgenteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TnAgente::class);
    }

    // /**
    //  * @return TnAgente[] Returns an array of TnAgente objects
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


    public function findAgentesAgenciaIds($agencia)
    {
        $dql = "SELECT ag.id FROM App:TnAgente ag WHERE ag.agencia = :agn";
        $arrayId = $this->getEntityManager()->createQuery($dql)->setParameter('agn', $agencia)->getResult();
        return $arrayId;
    }
}
