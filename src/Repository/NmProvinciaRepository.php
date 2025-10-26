<?php

namespace App\Repository;

use App\Entity\NmEstado;
use App\Entity\NmProvincia;
use App\Entity\TnAgencia;
use App\Entity\TnAgente;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method NmProvincia|null find($id, $lockMode = null, $lockVersion = null)
 * @method NmProvincia|null findOneBy(array $criteria, array $orderBy = null)
 * @method NmProvincia[]    findAll()
 * @method NmProvincia[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NmProvinciaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NmProvincia::class);
    }

    public function getProvinciaMunicipioGrouped()
    {

        $prov = $this->findBy(array(), array('id' => 'asc'));

        $list = array();

        foreach ($prov as $p) {
            $mun = $p->getMunicipios();
            foreach ($mun as $m) {
                $list[$p->getName()][$m->getName()] = $m;
            }
        }
        return $list;
    }

    public function getLocalizationsProvincias()
    {
        $data = $this->createQueryBuilder('p')
            ->select('p.latitud, p.longitud, p.id')
            ->where('p.latitud IS NOT NULL AND p.longitud IS NOT NULL');
        return $data->getQuery()->getArrayResult();
    }

    public function getLocalizationsProvinciasRemesas($tnUser = null)
    {
        $query = $this->createQueryBuilder('p')
            ->distinct()
            ->select('p.latitud, p.longitud, p.id, COUNT(p.id) as total')
            ->join('p.municipios', 'municipio')
            ->join('municipio.destinatarios', 'destinatario')
            ->join('destinatario.remesas', 'remesa')
            ->join('remesa.factura', 'factura')
            ->join('factura.estado', 'estado')
            ->andWhere('estado.codigo <> :cod')
            ->groupBy('p.id')
            ->having('total > 0')
            ->setParameter('cod', NmEstado::ESTADO_CANCELADA);

        if ($tnUser instanceof TnAgencia) {
            $query->andWhere('factura.agencia = :agc')
                ->setParameter('agc', $tnUser);
        }

        if ($tnUser instanceof TnAgente) {
            $query->andWhere('factura.agente = :agt')
                ->setParameter('agt', $tnUser);
        }

        return $query->getQuery()->getArrayResult();
    }

    // /**
    //  * @return NmProvincia[] Returns an array of NmProvincia objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('n.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?NmProvincia
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
