<?php

namespace App\Repository;

use App\Entity\NmEstadoTransferencia;
use App\Entity\TnTransferencia;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method TnTransferencia|null find($id, $lockMode = null, $lockVersion = null)
 * @method TnTransferencia|null findOneBy(array $criteria, array $orderBy = null)
 * @method TnTransferencia[]    findAll()
 * @method TnTransferencia[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TnTransferenciaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TnTransferencia::class);
    }

    public function advancedSearchAgencia($params, $agencia)
    {
        $query = $this->createQueryBuilder('transf')
            ->join('transf.estado', 'estado');

        if ($params['codigo'] != "") {
            $query->andWhere('transf.codigo like :cod or transf.referencia like :cod')
                ->setParameter('cod', '%' . $params['codigo'] . '%');
        }

        if ($params['tarejta'] != "") {
            $query->andWhere('transf.numeroTarjeta like :ntarj')
                ->setParameter('ntarj', '%' . $params['tarejta'] . '%');
        }

        if ($params['titular'] != "") {
            $query->andWhere('transf.titularTarjeta like :ttular')
                ->setParameter('ttular', '%' . $params['titular'] . '%');
        }

        if (array_key_exists('estado', $params)) {
            $query->andWhere('estado.codigo IN (:estados)')
                ->setParameter('estados', $params['estado']);
        }

        if ($params['fechaInicio'] != "" && $params['fechaFin'] == "") {
            $fInicio = new \DateTime(date($params['fechaInicio'] . " 00:00:00"));
            $query->andWhere('transf.created >= :inicio')
                ->setParameter('inicio', $fInicio);
        }

        if ($params['fechaFin'] != "" && $params['fechaInicio'] == "") {
            $fFin = new \DateTime(date($params['fechaFin'] . " 23:59:59"));
            $query->andWhere('transf.created <= :fin')
                ->setParameter('fin', $fFin);
        }

        if ($params['fechaInicio'] != "" && $params['fechaFin'] != "") {

            $fInicio = new \DateTime(date($params['fechaInicio'] . " 00:00:00"));
            $fFin = new \DateTime(date($params['fechaFin'] . " 23:59:59"));
            $query->andWhere('transf.created >= :inicio and transf.created <= :fin')
                ->setParameter('fin', $fFin)
                ->setParameter('inicio', $fInicio);
        }

        if (array_key_exists('agente', $params) && !array_key_exists('agencia', $params)) {
            $query->andWhere('transf.agente IN (:agt)')
                ->setParameter('agt', $params['agente']);
        } else {
            $query->andWhere('transf.agencia = (:agc)')
                ->setParameter('agc', $agencia);
        }

        $query->orderBy('transf.created', 'DESC')->setMaxResults(100);

        return $query->getQuery()->getResult();
    }

    public function advancedSearchAgente($params, $agente)
    {
        $query = $this->createQueryBuilder('transf')
            ->join('transf.estado', 'estado')
            ->where('transf.agente = :agt')
            ->setParameter('agt', $agente);

        if ($params['codigo'] != "") {
            $query->andWhere('transf.codigo like :cod or transf.referencia like :cod')
                ->setParameter('cod', '%' . $params['codigo'] . '%');
        }

        if ($params['tarejta'] != "") {
            $query->andWhere('transf.numeroTarjeta like :ntarj')
                ->setParameter('ntarj', '%' . $params['tarejta'] . '%');
        }

        if ($params['titular'] != "") {
            $query->andWhere('transf.titularTarjeta like :ttular')
                ->setParameter('ttular', '%' . $params['titular'] . '%');
        }

        if (array_key_exists('estado', $params)) {
            $query->andWhere('estado.codigo IN (:estados)')
                ->setParameter('estados', $params['estado']);
        }

        if ($params['fechaInicio'] != "" && $params['fechaFin'] == "") {
            $fInicio = new \DateTime(date($params['fechaInicio'] . " 00:00:00"));
            $query->andWhere('transf.created >= :inicio')
                ->setParameter('inicio', $fInicio);
        }

        if ($params['fechaFin'] != "" && $params['fechaInicio'] == "") {
            $fFin = new \DateTime(date($params['fechaFin'] . " 23:59:59"));
            $query->andWhere('transf.created <= :fin')
                ->setParameter('fin', $fFin);
        }

        if ($params['fechaInicio'] != "" && $params['fechaFin'] != "") {

            $fInicio = new \DateTime(date($params['fechaInicio'] . " 00:00:00"));
            $fFin = new \DateTime(date($params['fechaFin'] . " 23:59:59"));
            $query->andWhere('transf.created >= :inicio and transf.created <= :fin')
                ->setParameter('fin', $fFin)
                ->setParameter('inicio', $fInicio);
        }

        $query->orderBy('transf.created', 'DESC')->setMaxResults(100);

        return $query->getQuery()->getResult();
    }

    public function advancedSearchAdmin($params)
    {
        $query = $this->createQueryBuilder('transf')
            ->join('transf.estado', 'estado');

        if ($params['codigo'] != "") {
            $query->andWhere('transf.codigo like :cod or transf.referencia like :cod')
                ->setParameter('cod', '%' . $params['codigo'] . '%');
        }

        if ($params['tarejta'] != "") {
            $query->andWhere('transf.numeroTarjeta like :ntarj')
                ->setParameter('ntarj', '%' . $params['tarejta'] . '%');
        }

        if ($params['titular'] != "") {
            $query->andWhere('transf.titularTarjeta like :ttular')
                ->setParameter('ttular', '%' . $params['titular'] . '%');
        }

        if (array_key_exists('estado', $params)) {
            $query->andWhere('estado.codigo IN (:estados)')
                ->setParameter('estados', $params['estado']);
        }

        if ($params['fechaInicio'] != "" && $params['fechaFin'] == "") {
            $fInicio = new \DateTime(date($params['fechaInicio'] . " 00:00:00"));
            $query->andWhere('transf.created >= :inicio')
                ->setParameter('inicio', $fInicio);
        }

        if ($params['fechaFin'] != "" && $params['fechaInicio'] == "") {
            $fFin = new \DateTime(date($params['fechaFin'] . " 23:59:59"));
            $query->andWhere('transf.created <= :fin')
                ->setParameter('fin', $fFin);
        }

        if ($params['fechaInicio'] != "" && $params['fechaFin'] != "") {

            $fInicio = new \DateTime(date($params['fechaInicio'] . " 00:00:00"));
            $fFin = new \DateTime(date($params['fechaFin'] . " 23:59:59"));
            $query->andWhere('transf.created >= :inicio and transf.created <= :fin')
                ->setParameter('fin', $fFin)
                ->setParameter('inicio', $fInicio);
        }

        if (array_key_exists('agencia', $params) && !array_key_exists('agente', $params)) {
            $query->andWhere('transf.agencia IN (:ags)')
                ->setParameter('ags', $params['agencia']);
        }

        if (array_key_exists('repartidor', $params)) {
            $query->andWhere('transf.repartidor IN (:rpt)')
                ->setParameter('rpt', $params['repartidor']);
        }

        if (array_key_exists('agente', $params) && !array_key_exists('agencia', $params)) {
            $query->andWhere('transf.agente IN (:agt)')
                ->setParameter('agt', $params['agente']);
        }

        if ($params['nota'] != "") {
            $query->andWhere('transf.notas like :nota')
                ->setParameter('nota', '%' . $params['nota'] . '%');
        }

        $query->orderBy('transf.created', 'DESC')->setMaxResults(100);

        return $query->getQuery()->getResult();
    }

    public function findDestinatarioAgencia($agencia, $destinatario)
    {
        $dql = "SELECT ag.id FROM App:TnAgente ag WHERE ag.agencia = :agcs";
        $arrayId = $this->getEntityManager()->createQuery($dql)->setParameter('agcs', $agencia)->getResult();

        $ids = [];
        foreach ($arrayId as $item) {
            $ids[] = $item['id'];
        }

        $query = $this->createQueryBuilder('tfc')
            ->select('tfc.id, tfc.emisor, tfc.titularTarjeta, tfc.numeroTarjeta')
            ->where('tfc.agencia = :agc or tfc.agente IN (:agts)')
            ->andWhere('tfc.titularTarjeta LIKE :dest')
            ->setParameter('agc', $agencia)
            ->setParameter('agts', $ids)
            ->setParameter('dest', '%' . $destinatario . '%')
            ->setMaxResults(10)
            ->orderBy('tfc.created', "DESC");

        return $query->getQuery()->getArrayResult();
    }

    public function findDestinatarioAgente($agente, $destinatario)
    {

        $query = $this->createQueryBuilder('tfc')
            ->select('tfc.id, tfc.emisor, tfc.titularTarjeta, tfc.numeroTarjeta')
            ->where('tfc.agente = :agt')
            ->andWhere('tfc.titularTarjeta LIKE :dest')
            ->setParameter('agt', $agente)
            ->setParameter('dest', '%' . $destinatario . '%')
            ->setMaxResults(10)
            ->orderBy('tfc.created', "DESC");

        return $query->getQuery()->getArrayResult();
    }

    public function findTransfenciasAgenciaAdminParams($params)
    {
        $query = $this->createQueryBuilder('transf')
            ->join('transf.estado', 'estado')
            ->andWhere('transf.fechaContable >= :fi and transf.fechaContable <= :ff')
            ->setParameter('fi', $params['fi'])
            ->setParameter('ff', $params['ff'])
            ->orderBy('transf.fechaContable', "ASC");

        if (array_key_exists('agencias', $params) && array_key_exists('agentes', $params)) {
            $query->andWhere('transf.agencia IN (:ags) or (transf.agente is not null and transf.agente IN (:agts))')
                ->setParameter('ags', $params['agencias'])
                ->setParameter('agts', $params['agentes']);
        } else {
            if (array_key_exists('agencias', $params)) {
                $query->andWhere('transf.agencia IS NOT NULL and transf.agencia IN (:ags)')
                    ->setParameter('ags', $params['agencias']);
            } else {
                $query->andWhere('transf.agencia IS NOT NULL');
            }
        }

        if (array_key_exists('monedas', $params)) {
            $query->join('transf.moneda', 'moneda')
                ->andWhere('moneda.id IN (:mon)')
                ->setParameter('mon', $params['monedas']);
        }

        if (array_key_exists('estados', $params)) {
            $query->andWhere('estado.codigo IN (:estados)')
                ->setParameter('estados', $params['estados']);
        } else {
            $query->andWhere('estado.codigo IN (:estados)')
                ->setParameter('estados', NmEstadoTransferencia::getEnviadoEstados());
        }

        return $query->getQuery()->getResult();
    }

    public function findTransferenciasAgenciaAgentesParams($params)
    {
        $query = $this->createQueryBuilder('tranf')
            ->join('tranf.estado', 'estado')
            ->andWhere('tranf.fechaContable >= :fi and tranf.fechaContable <= :ff')
            ->setParameter('fi', $params['fi'])
            ->setParameter('ff', $params['ff'])
            ->orderBy('tranf.fechaContable', "ASC");

        if (array_key_exists('agentes', $params)) {
            $query->andWhere('tranf.agente is not null and tranf.agente IN (:agts)')
                ->setParameter('agts', $params['agentes']);
        } else {
            if (array_key_exists('agencias', $params)) {
                $query->andWhere('tranf.agencia IS NOT NULL and tranf.agencia IN (:ags)')
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
                ->setParameter('estados', NmEstadoTransferencia::getReportEstados());
        }

        return $query->getQuery()->getResult();
    }

    public function findTransferenciasAgenciaAgentesCobroParams($params)
    {
        $query = $this->createQueryBuilder('tranf')
            ->join('tranf.estado', 'estado')
            ->andWhere('tranf.fechaContable >= :fi and tranf.fechaContable <= :ff')
            ->setParameter('fi', $params['fi'])
            ->setParameter('ff', $params['ff'])
            ->orderBy('tranf.fechaContable', "ASC");

        if (array_key_exists('agentes', $params)) {
            $query->andWhere('tranf.agente is not null and tranf.agente IN (:agts)')
                ->setParameter('agts', $params['agentes']);
        } else {
            if (array_key_exists('agencias', $params)) {
                $query->join('tranf.agente', 'agente')
                    ->andWhere('tranf.agencia IS NOT NULL and agente.agencia IN (:ags)')
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
                ->setParameter('estados', NmEstadoTransferencia::getReportEstados());
        }

        return $query->getQuery()->getResult();
    }

    public function findTransferenciasAgenteAgentesParams($params)
    {
        $query = $this->createQueryBuilder('tranf')
            ->join('tranf.estado', 'estado')
            ->andWhere('tranf.fechaContable >= :fi and tranf.fechaContable <= :ff')
            ->setParameter('fi', $params['fi'])
            ->setParameter('ff', $params['ff'])
            ->orderBy('tranf.fechaContable', "ASC");

        if (array_key_exists('agentes', $params)) {
            $query->andWhere('tranf.agente is not null and tranf.agente IN (:agts)')
                ->setParameter('agts', $params['agentes']);
        }

        if (array_key_exists('estados', $params)) {
            $query->andWhere('estado.codigo IN (:estados)')
                ->setParameter('estados', $params['estados']);
        } else {
            $query->andWhere('estado.codigo IN (:estados)')
                ->setParameter('estados', NmEstadoTransferencia::getReportEstados());
        }

        return $query->getQuery()->getResult();
    }

    public function findTransfParamsRepartidorCanceladas($params)
    {
        $query = $this->createQueryBuilder('tr')
            ->join('tr.estado', 'estado')
            ->where('(tr.canceladaAt is not null and tr.canceladaAt >= :fi and tr.canceladaAt <= :ff) or (tr.canceladaAt is null and tr.updated >= :fi and tr.updated <= :ff)')
            ->andWhere('tr.repartidor is not null')
            ->andWhere('tr.enviadaAt is not null')
            ->setParameter('fi', $params['fi'])
            ->setParameter('ff', $params['ff'])
            ->orderBy('tr.updated', "ASC");

        if (array_key_exists('repartidores', $params)) {
            $query->join('tr.repartidor', 'repartidor')
                ->andWhere('repartidor.id IN (:reprt)')
                ->setParameter('reprt', $params['repartidores']);
        }

        if (array_key_exists('monedas', $params)) {
            $query->join('tr.moneda', 'moneda')
                ->andWhere('moneda.id IN (:mon)')
                ->setParameter('mon', $params['monedas']);
        }

        if (array_key_exists('estados', $params)) {
            $query->andWhere('estado.codigo IN (:estados)')
                ->setParameter('estados', $params['estados']);
        } else {
            $query->andWhere('estado.codigo IN (:estados)')
                ->setParameter('estados', NmEstadoTransferencia::getEnviadoEstados());
        }

        return $query->getQuery()->getResult();
    }

    public function findTransfAdminParamsRepartidor($params)
    {
        $query = $this->createQueryBuilder('tr')
            ->join('tr.estado', 'estado')
            ->orderBy('tr.created', "ASC");


        if (array_key_exists('repartidores', $params)) {
            $query->join('tr.repartidor', 'repartidor')
                ->andWhere('repartidor.id IN (:reprt)')
                ->setParameter('reprt', $params['repartidores']);
        }

        if (array_key_exists('monedas', $params)) {
            $query->join('tr.moneda', 'moneda')
                ->andWhere('moneda.id IN (:mon)')
                ->setParameter('mon', $params['monedas']);
        }

        if (array_key_exists('estados', $params)) {
            $query->andWhere('estado.codigo IN (:estados)')
                ->andWhere('tr.created >= :fi and tr.created <= :ff')
                ->setParameter('fi', $params['fi'])
                ->setParameter('ff', $params['ff'])
                ->setParameter('estados', $params['estados']);
        } else {
            $query->join('tr.reporteTransferencia', 'reporteTransferencia')
                ->andWhere('reporteTransferencia.created >= :fi and reporteTransferencia.created <= :ff')
                ->andWhere('tr.repartidor is not null')
                ->andWhere('estado.codigo IN (:estados)')
                ->setParameter('fi', $params['fi'])
                ->setParameter('ff', $params['ff'])
                ->setParameter('estados', NmEstadoTransferencia::getEnviadoEstados());
        }

        return $query->getQuery()->getResult();
    }
}
