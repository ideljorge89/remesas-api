<?php

namespace App\Repository;

use App\Entity\TnListaNegra;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method TnListaNegra|null find($id, $lockMode = null, $lockVersion = null)
 * @method TnListaNegra|null findOneBy(array $criteria, array $orderBy = null)
 * @method TnListaNegra[]    findAll()
 * @method TnListaNegra[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TnListaNegraRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TnListaNegra::class);
    }

    public function findInBlackList($params)
    {
        $query = $this->createQueryBuilder('ln')
            ->select('ln')
            ->where('ln.nombre LIKE :name')
            ->orWhere('ln.apellidos LIKE :apell')
            ->orWhere('ln.phone LIKE :phn')
            ->orWhere('ln.direccion LIKE :dir')
            ->setParameter('name', '%' . $params['apellidos'] . '%')
            ->setParameter('apell', '%' . $params['nombre'] . '%')
            ->setParameter('dir', '%' . $params['direccion'] . '%')
            ->setParameter('phn', '%' . $params['phone'] . '%');

        if ($params['ci'] != 'No definido') {
            $query->orWhere('ln.ci = :cid')
                ->setParameter('cid', $params['ci']);
        }

        return $query->getQuery()->getResult();
    }
}
