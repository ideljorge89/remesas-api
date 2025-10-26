<?php

namespace App\Repository;

use App\Entity\TnDestinatario;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method TnDestinatario|null find($id, $lockMode = null, $lockVersion = null)
 * @method TnDestinatario|null findOneBy(array $criteria, array $orderBy = null)
 * @method TnDestinatario[]    findAll()
 * @method TnDestinatario[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TnDestinatarioRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TnDestinatario::class);
    }

    public function advancedSearchDestinatario($params, $tnUser = null)
    {
        $query = $this->createQueryBuilder('destinatario')
            ->join('destinatario.emisor', 'emisor');

        if ($params['emisor'] != "") {
            $param = explode(" ", $params['emisor']);
            $query->andWhere('emisor.nombre like :emlk or emisor.nombre IN (:ems) or emisor.apellidos IN (:ems) or emisor.apellidos like :emlk')
                ->setParameter('ems', $param)
                ->setParameter('emlk', '%' . $params['emisor'] . '%');
        }

        if ($params['destinatario'] != "") {
            $paramD = explode(" ", $params['destinatario']);
            $query->andWhere('destinatario.nombre like :destlk or destinatario.nombre IN (:dest) or destinatario.apellidos IN (:dest) or destinatario.apellidos like :destlk')
                ->setParameter('dest', $paramD)
                ->setParameter('destlk', '%' . $params['destinatario'] . '%');
        }

        if ($params['phone'] != "") {
            $query->andWhere('destinatario.phone like :telef')
                ->setParameter('telef', '%' . $params['phone'] . '%');
        }

        if ($params['direccion'] != "") {
            $param = explode(" ", $params['direccion']);
            $query
                ->join('destinatario.municipio', 'municipio')
                ->join('destinatario.provincia', 'provincia')
                ->andWhere('destinatario.direccion like :dir or destinatario.direccion IN (:dirin) or municipio.name like :dir or provincia.name like :dir')
                ->setParameter('dirin', $param)
                ->setParameter('dir', '%' . $params['direccion'] . '%');
        }

        if (array_key_exists('usuario', $params)) {
            $query->andWhere('destinatario.usuario IN (:user)')
                ->setParameter('user', $params['usuario']);
        } elseif ($tnUser != null) {
            if ($tnUser->getAgencia()) {
                $dql = "SELECT us.id FROM App:TnAgente ag JOIN ag.usuario us WHERE ag.agencia = :agcs";
                $arrayId = $this->getEntityManager()->createQuery($dql)->setParameter('agcs', $tnUser->getAgencia())->getResult();
                $ids = [$tnUser->getId()];
                foreach ($arrayId as $item) {
                    $ids[] = $item['id'];
                }

                $query->join('destinatario.usuario', 'usuario')
                    ->andWhere('usuario.id IN (:users)')
                    ->setParameter('users', $ids);
            } else {
                $query->andWhere('destinatario.usuario = :user')
                    ->setParameter('user', $tnUser);
            }
        }

        $query->orderBy('destinatario.created', 'DESC');

        return $query->getQuery()->getResult();
    }


    public function searchDestinatarioByParams($params, $tnUser)
    {
        $query = $this->createQueryBuilder('destinatario')
            ->where('destinatario.nombre like :name and destinatario.apellidos like :apell and destinatario.phone like :telef')
            ->andWhere('destinatario.usuario = :user')
            ->andWhere('destinatario.emisor = :emisor')
            ->setParameter('name', '%' . $params['nombre'] . '%')
            ->setParameter('apell', '%' . $params['apellidos'] . '%')
            ->setParameter('telef', '%' . $params['phone'] . '%')
            ->setParameter('emisor', $params['emisor'])
            ->setParameter('user', $tnUser);

        return $query->getQuery()->getResult();
    }

    public function findRemesasDestinatario($params)
    {
        $query = $this->createQueryBuilder('destinatario')
            ->select('destinatario.id, destinatario.nombre, destinatario.apellidos, COUNT(destinatario.id) totalRemesas')
            ->join('destinatario.remesas', 'remesa')
            ->join('remesa.factura', 'factura')
            ->andWhere('factura.created >= :fi and factura.created <= :ff')
            ->setParameter('fi', $params['fi'])
            ->setParameter('ff', $params['ff'])
            ->groupBy('remesa.id')
            ->orderBy('totalRemesas', "DESC");

        return $query->getQuery()->getResult();
    }

    public function findDestinatarioEmisor($emisor)
    {
        $query = $this->createQueryBuilder('destinatario')
            ->select('destinatario.id, destinatario.nombre, destinatario.apellidos, destinatario.phone, destinatario.alias, destinatario.ci, destinatario.direccion, destinatario.direccion1, munp.name municipio, prov.name provincia, destinatario.country')
            ->join('destinatario.municipio', 'munp')
            ->join('destinatario.provincia', 'prov')
            ->where('destinatario.emisor = :em')
            ->andWhere('destinatario.enabled = true')
            ->setParameter('em', $emisor)
            ->setMaxResults(50)
            ->orderBy('destinatario.created', "DESC");

        return $query->getQuery()->getArrayResult();
    }
}
