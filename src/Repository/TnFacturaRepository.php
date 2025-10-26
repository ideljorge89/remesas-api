<?php

namespace App\Repository;

use App\Entity\NmEstado;
use App\Entity\TnFactura;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method TnFactura|null find($id, $lockMode = null, $lockVersion = null)
 * @method TnFactura|null findOneBy(array $criteria, array $orderBy = null)
 * @method TnFactura[]    findAll()
 * @method TnFactura[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TnFacturaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TnFactura::class);
    }

    public function advancedSearchAdmin($params)
    {
        $query = $this->createQueryBuilder('factura')
            ->join('factura.remesas', 'remesa')
            ->join('factura.estado', 'estado');

        if ($params['orden'] != "") {
            $query->andWhere('factura.no_factura like :ord')
                ->setParameter('ord', '%' . $params['orden'] . '%');
        }

        if ($params['emisor'] != "") {
            $param = explode("/", $params['emisor']);
            if (count($param) == 2) {
                $query->join('factura.emisor', 'emisor')
                    ->andWhere('emisor.nombre like :nomb and emisor.apellidos like :apell')
                    ->setParameter('nomb', '%' . $param[0] . '%')
                    ->setParameter('apell', '%' . $param[1] . '%');
            } else {
                $query->join('factura.emisor', 'emisor')
                    ->andWhere('emisor.nombre like :nomb or emisor.apellidos like :apell')
                    ->setParameter('nomb', '%' . $param[0] . '%')
                    ->setParameter('apell', '%' . $param[0] . '%');
            }
        }

        if ($params['destinatario'] != "") {
            $paramD = explode("/", $params['destinatario']);
            if (count($paramD) == 2) {
                $query->join('remesa.destinatario', 'destinatario')
                    ->andWhere('destinatario.nombre like :name and destinatario.apellidos like :apell')
                    ->setParameter('name', '%' . $paramD[0] . '%')
                    ->setParameter('apell', '%' . $paramD[1] . '%');
            } else {
                $query->join('remesa.destinatario', 'destinatario')
                    ->andWhere('destinatario.nombre like :name or destinatario.apellidos like :apell')
                    ->setParameter('name', '%' . $paramD[0] . '%')
                    ->setParameter('apell', '%' . $paramD[0] . '%');
            }
        }

        if (array_key_exists('estado', $params)) {
            $query->andWhere('estado.codigo IN (:estados)')
                ->setParameter('estados', $params['estado']);
        }

        if ($params['fechaInicio'] != "" && $params['fechaFin'] == "") {
            $fInicio = new \DateTime(date($params['fechaInicio'] . " 00:00:00"));
            $query->andWhere('factura.created >= :inicio')
                ->setParameter('inicio', $fInicio);
        }

        if ($params['fechaFin'] != "" && $params['fechaInicio'] == "") {
            $fFin = new \DateTime(date($params['fechaFin'] . " 23:59:59"));
            $query->andWhere('factura.created <= :fin')
                ->setParameter('fin', $fFin);
        }

        if ($params['fechaInicio'] != "" && $params['fechaFin'] != "") {

            $fInicio = new \DateTime(date($params['fechaInicio'] . " 00:00:00"));
            $fFin = new \DateTime(date($params['fechaFin'] . " 23:59:59"));
            $query->andWhere('factura.created >= :inicio and factura.created <= :fin')
                ->setParameter('fin', $fFin)
                ->setParameter('inicio', $fInicio);
        }

        if (array_key_exists('agencia', $params) && !array_key_exists('agente', $params)) {
            $query->andWhere('factura.agencia IN (:ags)')
                ->setParameter('ags', $params['agencia']);
        }

        if (array_key_exists('agente', $params) && !array_key_exists('agencia', $params)) {
            $query->andWhere('factura.agente IN (:agt)')
                ->setParameter('agt', $params['agente']);
        }

        if (array_key_exists('distribuidor', $params)) {
            $query->andWhere('remesa.distribuidor IN (:dist)')
                ->setParameter('dist', $params['distribuidor']);
        }

        if (array_key_exists('moneda', $params)) {
            $query->join('remesa.moneda', 'moneda')
                ->andWhere('moneda.id IN (:monds)')
                ->setParameter('monds', $params['moneda']);
        }

        if ($params['nota'] != "") {
            $query->andWhere('factura.notas like :nota')
                ->setParameter('nota', '%' . $params['nota'] . '%');
        }

        $query->orderBy('factura.created', 'DESC')->setMaxResults(300);

        return $query->getQuery()->getResult();
    }

    public function advancedSearchAgencia($params, $agencia)
    {
        $query = $this->createQueryBuilder('factura')
            ->join('factura.remesas', 'remesa')
            ->join('factura.estado', 'estado');

        if ($params['orden'] != "") {
            $query->andWhere('factura.no_factura like :ord')
                ->setParameter('ord', '%' . $params['orden'] . '%');
        }

        if ($params['emisor'] != "") {
            $param = explode(" ", $params['emisor']);
            $query->join('factura.emisor', 'emisor')
                ->andWhere('emisor.nombre like :emlk or emisor.nombre IN (:ems) or emisor.apellidos IN (:ems) or emisor.apellidos like :emlk')
                ->setParameter('ems', $param)
                ->setParameter('emlk', '%' . $params['emisor'] . '%');
        }
        if ($params['destinatario'] != "") {
            $paramD = explode(" ", $params['destinatario']);
            $query->join('remesa.destinatario', 'destinatario')
                ->andWhere('destinatario.nombre like :destlk or destinatario.nombre IN (:dest) or destinatario.apellidos IN (:dest) or destinatario.apellidos like :destlk')
                ->setParameter('dest', $paramD)
                ->setParameter('destlk', '%' . $params['destinatario'] . '%');
        }

        if (array_key_exists('estado', $params)) {
            $query->andWhere('estado.codigo IN (:estados)')
                ->setParameter('estados', $params['estado']);
        }

        if ($params['fechaInicio'] != "" && $params['fechaFin'] == "") {
            $fInicio = new \DateTime(date($params['fechaInicio'] . " 00:00:00"));
            $query->andWhere('factura.created >= :inicio')
                ->setParameter('inicio', $fInicio);
        }

        if ($params['fechaFin'] != "" && $params['fechaInicio'] == "") {
            $fFin = new \DateTime(date($params['fechaFin'] . " 23:59:59"));
            $query->andWhere('factura.created <= :fin')
                ->setParameter('fin', $fFin);
        }

        if ($params['fechaInicio'] != "" && $params['fechaFin'] != "") {

            $fInicio = new \DateTime(date($params['fechaInicio'] . " 00:00:00"));
            $fFin = new \DateTime(date($params['fechaFin'] . " 23:59:59"));
            $query->andWhere('factura.created >= :inicio and factura.created <= :fin')
                ->setParameter('fin', $fFin)
                ->setParameter('inicio', $fInicio);
        }

        if (array_key_exists('agente', $params) && !array_key_exists('agencia', $params)) {
            $query->andWhere('factura.agente IN (:agt)')
                ->setParameter('agt', $params['agente']);
        } else {
            $query->andWhere('factura.agencia = (:agc)')
                ->setParameter('agc', $agencia);
        }

        if (array_key_exists('moneda', $params)) {
            $query->join('remesa.moneda', 'moneda')
                ->andWhere('moneda.id IN (:monds)')
                ->setParameter('monds', $params['moneda']);
        }

        $query->orderBy('factura.created', 'DESC')->setMaxResults(300);

        return $query->getQuery()->getResult();
    }

    public function advancedSearchAgente($params, $agente)
    {
        $query = $this->createQueryBuilder('factura')
            ->join('factura.remesas', 'remesa')
            ->join('factura.estado', 'estado')
            ->where('factura.agente = :agt')
            ->setParameter('agt', $agente);

        if ($params['orden'] != "") {
            $query->andWhere('factura.no_factura like :ord')
                ->setParameter('ord', '%' . $params['orden'] . '%');
        }

        if ($params['emisor'] != "") {
            $param = explode(" ", $params['emisor']);
            $query->join('factura.emisor', 'emisor')
                ->andWhere('emisor.nombre like :emlk or emisor.nombre IN (:ems) or emisor.apellidos IN (:ems) or emisor.apellidos like :emlk')
                ->setParameter('ems', $param)
                ->setParameter('emlk', '%' . $params['emisor'] . '%');
        }
        if ($params['destinatario'] != "") {
            $paramD = explode(" ", $params['destinatario']);
            $query->join('remesa.destinatario', 'destinatario')
                ->andWhere('destinatario.nombre like :destlk or destinatario.nombre IN (:dest) or destinatario.apellidos IN (:dest) or destinatario.apellidos like :destlk')
                ->setParameter('dest', $paramD)
                ->setParameter('destlk', '%' . $params['destinatario'] . '%');
        }

        if (array_key_exists('estado', $params)) {
            $query->andWhere('estado.codigo IN (:estados)')
                ->setParameter('estados', $params['estado']);
        }

        if ($params['fechaInicio'] != "" && $params['fechaFin'] == "") {
            $fInicio = new \DateTime(date($params['fechaInicio'] . " 00:00:00"));
            $query->andWhere('factura.created >= :inicio')
                ->setParameter('inicio', $fInicio);
        }

        if ($params['fechaFin'] != "" && $params['fechaInicio'] == "") {
            $fFin = new \DateTime(date($params['fechaFin'] . " 23:59:59"));
            $query->andWhere('factura.created <= :fin')
                ->setParameter('fin', $fFin);
        }

        if ($params['fechaInicio'] != "" && $params['fechaFin'] != "") {

            $fInicio = new \DateTime(date($params['fechaInicio'] . " 00:00:00"));
            $fFin = new \DateTime(date($params['fechaFin'] . " 23:59:59"));
            $query->andWhere('factura.created >= :inicio and factura.created <= :fin')
                ->setParameter('fin', $fFin)
                ->setParameter('inicio', $fInicio);
        }

        if (array_key_exists('moneda', $params)) {
            $query->join('remesa.moneda', 'moneda')
                ->andWhere('moneda.id IN (:monds)')
                ->setParameter('monds', $params['moneda']);
        }

        $query->orderBy('factura.created', 'DESC')->setMaxResults(300);

        return $query->getQuery()->getResult();
    }

    public function findFacturasAgenciaAdminParams($params)
    {
        $query = $this->createQueryBuilder('factura')
            ->join('factura.estado', 'estado')
            ->andWhere('factura.fechaContable >= :fi and factura.fechaContable <= :ff')
            ->setParameter('fi', $params['fi'])
            ->setParameter('ff', $params['ff'])
            ->orderBy('factura.fechaContable', "ASC");

        if (array_key_exists('agencias', $params) && array_key_exists('agentes', $params)) {
            $query->andWhere('factura.agencia IN (:ags) or (factura.agente is not null and factura.agente IN (:agts))')
                ->setParameter('ags', $params['agencias'])
                ->setParameter('agts', $params['agentes']);
        } else {
            if (array_key_exists('agencias', $params)) {
                $query->andWhere('factura.agencia IS NOT NULL and factura.agencia IN (:ags)')
                    ->setParameter('ags', $params['agencias']);
            } else {
                $query->andWhere('factura.agencia IS NOT NULL');
            }
        }

        if (array_key_exists('monedas', $params)) {
            $query->join('factura.remesas', 'remesa')
                ->join('remesa.moneda', 'moneda')
                ->andWhere('moneda.id IN (:mon)')
                ->setParameter('mon', $params['monedas']);
        }

        if (array_key_exists('estados', $params)) {
            $query->andWhere('estado.codigo IN (:estados)')
                ->setParameter('estados', $params['estados']);
        } else {
            $query->andWhere('estado.codigo IN (:estados)')
                ->setParameter('estados', NmEstado::getCodeEstados());
        }

        return $query->getQuery()->getResult();
    }


    /**
     * @param $params
     * Remesas por agencia que están el reporte de Distribucuión
     * @return mixed
     */
    public function findFacturasAgenciaDistribucionAdminParams($params)
    {
        $query = $this->createQueryBuilder('factura')
            ->join('factura.estado', 'estado')
            ->join('factura.remesas', 'remesa')
            ->join('remesa.reporteEnvio', 'reporteEnvio')
            ->where('reporteEnvio.created >= :fi and reporteEnvio.created <= :ff')
            ->andWhere('remesa.distribuidor is not null')
            ->setParameter('fi', $params['fi'])
            ->setParameter('ff', $params['ff'])
            ->orderBy('reporteEnvio.created', "ASC");

        if (array_key_exists('agencias', $params) && array_key_exists('agentes', $params)) {
            $query->andWhere('factura.agencia IN (:ags) or (factura.agente is not null and factura.agente IN (:agts))')
                ->setParameter('ags', $params['agencias'])
                ->setParameter('agts', $params['agentes']);
        } else {
            if (array_key_exists('agencias', $params)) {
                $query->andWhere('factura.agencia IS NOT NULL and factura.agencia IN (:ags)')
                    ->setParameter('ags', $params['agencias']);
            } else {
                $query->andWhere('factura.agencia IS NOT NULL');
            }
        }

        if (array_key_exists('monedas', $params)) {
            $query->join('remesa.moneda', 'moneda')
                ->andWhere('moneda.id IN (:mon)')
                ->setParameter('mon', $params['monedas']);
        }

        if (array_key_exists('estados', $params)) {
            $query->andWhere('estado.codigo IN (:estados)')
                ->setParameter('estados', $params['estados']);
        } else {
            $query->andWhere('estado.codigo IN (:estados)')
                ->setParameter('estados', NmEstado::getCodeEstados());
        }

        return $query->getQuery()->getResult();
    }

    public function findFacturasAgenciaAgentesParams($params)
    {
        $query = $this->createQueryBuilder('factura')
            ->join('factura.estado', 'estado')
            ->andWhere('factura.fechaContable >= :fi and factura.fechaContable <= :ff')
            ->setParameter('fi', $params['fi'])
            ->setParameter('ff', $params['ff'])
            ->orderBy('factura.fechaContable', "ASC");

        if (array_key_exists('agentes', $params)) {
            $query->andWhere('factura.agente is not null and factura.agente IN (:agts)')
                ->setParameter('agts', $params['agentes']);
        } else {
            if (array_key_exists('agencias', $params)) {
                $query->andWhere('factura.agencia IS NOT NULL and factura.agencia IN (:ags)')
                    ->setParameter('ags', $params['agencias']);
            } else {
                $query->andWhere('factura.agencia IS NOT NULL');
            }
        }

        if (array_key_exists('estados', $params)) {
            $query->andWhere('estado.codigo IN (:estados)')
                ->setParameter('estados', $params['estados']);
        } else {
            $query->andWhere('estado.codigo IN (:estados)')
                ->setParameter('estados', NmEstado::getCodeEstados());
        }

        return $query->getQuery()->getResult();
    }

    public function findFacturasAgenteAdminParams($params, $agencia = null)
    {
        $query = $this->createQueryBuilder('factura')
            ->join('factura.estado', 'estado')
            ->andWhere('factura.fechaContable >= :fi and factura.fechaContable <= :ff')
            ->setParameter('fi', $params['fi'])
            ->setParameter('ff', $params['ff'])
            ->orderBy('factura.fechaContable', "ASC");

        if (array_key_exists('estados', $params)) {
            $query->andWhere('estado.codigo IN (:estados)')
                ->setParameter('estados', $params['estados']);
        } else {
            $query->andWhere('estado.codigo IN (:estados)')
                ->setParameter('estados', NmEstado::getCodeEstados());
        }

        if ($agencia != null) {
            if (array_key_exists('agentes', $params)) {
                $query->join('factura.agente', 'agente')
                    ->andWhere('factura.agente IN (:agts) and agente.agencia = :agc')
                    ->setParameter('agts', $params['agentes'])
                    ->setParameter('agc', $agencia);
            } else {
                $query->join('factura.agencia', 'agencia')
                    ->andWhere('factura.agencia = :aga')
                    ->setParameter('aga', $agencia);
            }
        } else {
            if (array_key_exists('agentes', $params)) {
                $query->join('factura.agente', 'agente')
                    ->andWhere('factura.agente IN (:agts)')
                    ->setParameter('agts', $params['agentes']);
            } else {
                $query->join('factura.agente', 'agente')
                    ->andWhere('factura.agente IS NOT NULL');
            }
        }

        return $query->getQuery()->getResult();
    }

    public function findUtilidadRemesaParams($params, $type, $object = null)
    {
        $query = $this->createQueryBuilder('factura')
            ->join('factura.estado', 'estado')
            ->andWhere('factura.fechaContable >= :fi and factura.fechaContable <= :ff')
            ->setParameter('fi', $params['fi'])
            ->setParameter('ff', $params['ff'])
            ->orderBy('factura.fechaContable', "ASC");

        if (array_key_exists('estados', $params)) {
            $query->andWhere('estado.codigo IN (:estados)')
                ->setParameter('estados', $params['estados']);
        } else {
            $query->andWhere('estado.codigo IN (:estados)')
                ->setParameter('estados', NmEstado::getCodeEstados());
        }

        if ($type == "Agencia") {
            if (array_key_exists('agentes', $params) && $object != null) {
                $query->join('factura.agente', 'agente')
                    ->andWhere('factura.agente IN (:agts) and factura.agencia = :agc')
                    ->setParameter('agts', $params['agentes'])
                    ->setParameter('agc', $object);
            } else {
                $query->andWhere('factura.agencia = :aga')
                    ->setParameter('aga', $object);
            }
        } elseif ($type == "Agente") {
            $query->join('factura.agente', 'agente')
                ->andWhere('factura.agente = :agt')
                ->setParameter('agt', $object);
        }

        return $query->getQuery()->getResult();
    }


    public function findFacturasInArrayCodigo($params)
    {
        $query = $this->createQueryBuilder('factura')
            ->where('factura.no_factura IN (:facts)')
            ->setParameter('facts', $params);

        return $query->getQuery()->getResult();
    }


    public function findFacturasGreatThan($date)
    {
        $query = $this->createQueryBuilder('factura')
            ->where('factura.created >= :fecha')
            ->setParameter('fecha', $date);

        return $query->getQuery()->getResult();
    }

    public function findFacturasApoderadoParams($params)
    {
        $query = $this->createQueryBuilder('factura')
            ->join('factura.estado', 'estado')
            ->andWhere('factura.fechaContable >= :fi and factura.fechaContable <= :ff')
            ->setParameter('fi', $params['fi'])
            ->setParameter('ff', $params['ff'])
            ->orderBy('factura.fechaContable', "ASC");

        if (array_key_exists('agencias', $params)) {
            $query->andWhere('factura.agencia IN (:ags) or (factura.agente is not null and factura.agente IN (:agts))')
                ->setParameter('ags', $params['agencias'])
                ->setParameter('agts', $params['agentes']);;
        }

        if (array_key_exists('estados', $params)) {
            $query->andWhere('estado.codigo IN (:estados)')
                ->setParameter('estados', $params['estados']);
        } else {
            $query->andWhere('estado.codigo IN (:estados)')
                ->setParameter('estados', NmEstado::getCodeEstados());
        }

        return $query->getQuery()->getResult();
    }

    public function findFacturasCuadreAgencia($params)
    {
        $query = $this->createQueryBuilder('factura')
            ->join('factura.estado', 'estado')
            ->andWhere('factura.fechaContable >= :fi and factura.fechaContable <= :ff')
            ->setParameter('fi', $params['fi'])
            ->setParameter('ff', $params['ff'])
            ->orderBy('factura.fechaContable', "ASC");

        if (array_key_exists('agencias', $params) && count($params['agencias']) > 0) {
            $query->andWhere('factura.agencia IN (:ags) or (factura.agente is not null and factura.agente IN (:agts))')
                ->setParameter('ags', $params['agencias'])
                ->setParameter('agts', $params['agentes']);
        }

        if (array_key_exists('monedas', $params) && count($params['monedas']) > 0) {
            $query->andWhere('factura.moneda IN (:mons)')
                ->setParameter('mons', $params['monedas']);
        }

        if (array_key_exists('grupoPago', $params) && count($params['grupoPago']) > 0) {
            $query->join('factura.agencia','agencia')
                ->join('agencia.gruposPago','grupoPago')
                ->andWhere('grupoPago.id IN (:grupos)')
                ->setParameter('grupos', $params['grupoPago']);
        }

        if (array_key_exists('estados', $params) && count($params['estados']) > 0) {
            $query->andWhere('estado.codigo IN (:estados)')
                ->setParameter('estados', $params['estados']);
        } else {
            $query->andWhere('estado.codigo IN (:estados)')
                ->setParameter('estados', NmEstado::getCodeEstados());
        }

        if (array_key_exists('facturas', $params) && count($params['facturas']) > 0) {
            $query->andWhere('factura.no_factura IN (:codes)')
                ->setParameter('codes', $params['facturas']);
        }

        return $query->getQuery()->getResult();
    }

    public function findToCheckData($limit)
    {
        $query = $this->createQueryBuilder('factura')
            ->where('factura.id > :lmt')
            ->setParameter('lmt', $limit);

        return $query->getQuery()->getResult();
    }

    public function getFacturasPendienteAprobacion()
    {
        $query = $this->createQueryBuilder('f')
            ->join('f.estado', 'estado')
            ->where("estado.codigo = :f_est")
            ->addOrderBy('f.no_factura', 'ASC')
            ->setParameter('f_est', NmEstado::ESTADO_PENDIENTE);

        return $query->getQuery()->getResult();
    }

    public function findPanelFacturasAgenciaParams($params, $distribucion = null)
    {
        $query = $this->createQueryBuilder('factura')
            ->select('COUNT(factura.id) total')
            ->join('factura.estado', 'estado')
            ->join('factura.remesas', 'remesa')
            ->andWhere('factura.fechaContable >= :fi and factura.fechaContable <= :ff')
            ->setParameter('fi', $params['fi'])
            ->setParameter('ff', $params['ff'])
            ->orderBy('factura.fechaContable', "ASC");

        if (array_key_exists('agencias', $params)) {
            $query->andWhere('factura.agencia IS NOT NULL and factura.agencia IN (:ags)')
                ->setParameter('ags', $params['agencias']);
        } else {
            $query->andWhere('factura.agencia IS NOT NULL');
        }

        if (array_key_exists('monedas', $params)) {
            $query->join('remesa.moneda', 'moneda')
                ->andWhere('moneda.id IN (:mon)')
                ->setParameter('mon', $params['monedas']);
        }

        if ($distribucion != null) {
            $query->andWhere('remesa.reporteEnvio IS NULL');
        }

        $query->andWhere('estado.codigo IN (:estados)')
            ->setParameter('estados', NmEstado::getCodeEstados());

        return $query->getQuery()->getSingleScalarResult();
    }
}
