<?php

namespace App\Repository;

use App\Entity\NmEstado;
use App\Entity\TnAgencia;
use App\Entity\TnAgente;
use App\Entity\TnDistribuidor;
use App\Entity\TnRemesa;
use App\Entity\TnReporteEnvio;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method TnRemesa|null find($id, $lockMode = null, $lockVersion = null)
 * @method TnRemesa|null findOneBy(array $criteria, array $orderBy = null)
 * @method TnRemesa[]    findAll()
 * @method TnRemesa[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TnRemesaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TnRemesa::class);
    }

    public function findRemesasDistribuidorParams($params, TnDistribuidor $distribuidor)
    {
        $query = $this->createQueryBuilder('tr')
            ->join('tr.factura', 'factura')
            ->join('factura.estado', 'estado')
            ->join('tr.reporteEnvio', 'reporteEnvio')
            ->where('reporteEnvio.created >= :fi and reporteEnvio.created <= :ff')
            ->andWhere('tr.distribuidor = :dist')
            ->setParameter('dist', $distribuidor)
            ->setParameter('fi', $params['fi'])
            ->setParameter('ff', $params['ff'])
            ->orderBy('tr.created', "ASC");

        if (array_key_exists('zonas', $params)) {
            $query->join('tr.destinatario', 'destinatario')
                ->join('destinatario.municipio', 'municipio')
                ->andWhere('municipio.id IN (:zonas)')
                ->setParameter('zonas', $params['zonas']);
        }

        if (array_key_exists('estados', $params)) {
            if (in_array('04', $params['estados'])) {
                $query->andWhere('estado.codigo IN (:estados) or tr.entregada = true')
                    ->setParameter('estados', $params['estados']);
            } else {
                $query->andWhere('estado.codigo IN (:estados) and tr.entregada = false')
                    ->setParameter('estados', $params['estados']);
            }
        } else {
            $query->andWhere('estado.codigo IN (:estados)')
                ->setParameter('estados', NmEstado::getCodeDistribuidor());
        }

        return $query->getQuery()->getResult();
    }

    public function findRemesasAdminParams($params)
    {
        $query = $this->createQueryBuilder('tr')
            ->join('tr.factura', 'factura')
            ->join('factura.estado', 'estado')
            ->join('factura.remesas', 'remesa')
            ->andWhere('factura.created >= :fi and factura.created <= :ff')
            ->andWhere('remesa.distribuidor is not null')
            ->setParameter('fi', $params['fi'])
            ->setParameter('ff', $params['ff'])
            ->orderBy('tr.created', "ASC");

        if (array_key_exists('distribuidores', $params)) {
            $query->join('tr.distribuidor', 'distribuidor')
                ->andWhere('distribuidor.id IN (:dist)')
                ->setParameter('dist', $params['distribuidores']);
        }

        if (array_key_exists('estados', $params)) {
            if (in_array('04', $params['estados'])) {
                $query->andWhere('estado.codigo IN (:estados) or tr.entregada = true')
                    ->setParameter('estados', $params['estados']);
            } else {
                $query->andWhere('estado.codigo IN (:estados) and tr.entregada = false')
                    ->setParameter('estados', $params['estados']);
            }
        } else {
            $query->andWhere('estado.codigo IN (:estados)')
                ->setParameter('estados', NmEstado::getCodeEstados());
        }

        return $query->getQuery()->getResult();
    }

    public function findRemesasAdminParamsDistribuidor($params)
    {
        $query = $this->createQueryBuilder('tr')
            ->join('tr.factura', 'factura')
            ->join('factura.estado', 'estado')
            ->join('tr.reporteEnvio', 'reporteEnvio')
            ->where('reporteEnvio.created >= :fi and reporteEnvio.created <= :ff')
            ->andWhere('tr.distribuidor is not null')
            ->setParameter('fi', $params['fi'])
            ->setParameter('ff', $params['ff'])
            ->orderBy('tr.created', "ASC");

        if (array_key_exists('distribuidores', $params)) {
            $query->join('tr.distribuidor', 'distribuidor')
                ->andWhere('distribuidor.id IN (:dist)')
                ->setParameter('dist', $params['distribuidores']);
        }

        if (array_key_exists('monedas', $params)) {
            $query->join('tr.moneda', 'moneda')
                ->andWhere('moneda.id IN (:mon)')
                ->setParameter('mon', $params['monedas']);
        }

        if (array_key_exists('estados', $params)) {
            if (in_array('04', $params['estados'])) {
                $query->andWhere('estado.codigo IN (:estados) or tr.entregada = true')
                    ->setParameter('estados', $params['estados']);
            } else {
                $query->andWhere('estado.codigo IN (:estados) and tr.entregada = false')
                    ->setParameter('estados', $params['estados']);
            }
        } else {
            $query->andWhere('estado.codigo IN (:estados)')
                ->setParameter('estados', NmEstado::getCodeDistribuidor());
        }

        return $query->getQuery()->getResult();
    }

    public function findRemesasAdminParamsDistribuidorCanceladas($params)
    {
        $query = $this->createQueryBuilder('tr')
            ->join('tr.factura', 'factura')
            ->join('factura.estado', 'estado')
            ->where('(factura.canceladaAt is not null and factura.canceladaAt >= :fi and factura.canceladaAt <= :ff) or (factura.canceladaAt is null and factura.updated >= :fi and factura.updated <= :ff)')
            ->andWhere('tr.distribuidor is not null')
            ->andWhere('factura.aprobadaAt is not null or factura.distribuidaAt is not null')
            ->setParameter('fi', $params['fi'])
            ->setParameter('ff', $params['ff'])
            ->orderBy('factura.updated', "ASC");

        if (array_key_exists('distribuidores', $params)) {
            $query->join('tr.distribuidor', 'distribuidor')
                ->andWhere('distribuidor.id IN (:dist)')
                ->setParameter('dist', $params['distribuidores']);
        }

        if (array_key_exists('monedas', $params)) {
            $query->join('tr.moneda', 'moneda')
                ->andWhere('moneda.id IN (:mon)')
                ->setParameter('mon', $params['monedas']);
        }

        if (array_key_exists('estados', $params)) {
            if (in_array('04', $params['estados'])) {
                $query->andWhere('estado.codigo IN (:estados) or tr.entregada = true')
                    ->setParameter('estados', $params['estados']);
            } else {
                $query->andWhere('estado.codigo IN (:estados) and tr.entregada = false')
                    ->setParameter('estados', $params['estados']);
            }
        } else {
            $query->andWhere('estado.codigo IN (:estados)')
                ->setParameter('estados', NmEstado::getCodeDistribuidor());
        }

        return $query->getQuery()->getResult();
    }

    public function findRemesasDestinatario($params)
    {
        $query = $this->createQueryBuilder('remesa')
            ->distinct()
            ->join('remesa.factura', 'factura')
            ->andWhere('factura.created >= :fi and factura.created <= :ff')
            ->setParameter('fi', $params['fi'])
            ->setParameter('ff', $params['ff']);

        if (array_key_exists('agencias', $params) && array_key_exists('agentes', $params)) {
            $query->andWhere('factura.agencia IN (:ags) or (factura.agente is not null and factura.agente IN (:agts))')
                ->setParameter('ags', $params['agencias'])
                ->setParameter('agts', $params['agentes']);
        }

        return $query->getQuery()->getResult();
    }

    public function totalRemesas($tnUser = null, $moneda = null)
    {
        $query = $this->createQueryBuilder('remesa')
            ->distinct()
            ->select('COUNT(remesa.id), SUM(remesa.importe_entregar)')
            ->join('remesa.factura', 'factura')
            ->join('factura.estado', 'estado')
            ->andWhere('estado.codigo <> :cod')
            ->setParameter('cod', NmEstado::ESTADO_CANCELADA);

        if ($tnUser instanceof TnAgencia) {
            $query->andWhere('factura.agencia = :agc')
                ->setParameter('agc', $tnUser);
        }

        if ($moneda) {
            $query->join('remesa.moneda', 'moneda')
                ->andWhere('moneda.simbolo = :mon')
                ->setParameter('mon', $moneda);
        }

        if ($tnUser instanceof TnAgente) {
            $query->andWhere('factura.agente = :agt')
                ->setParameter('agt', $tnUser);
        }

        return $query->getQuery()->getArrayResult();
    }

    public function totalRemesasToday($tnUser = null, $moneda = null)
    {
        $params['fi'] = new \DateTime(date("Y/m/d 00:00:00"));
        $params['ff'] = new \DateTime(date("Y/m/d 23:59:59"));

        $query = $this->createQueryBuilder('remesa')
            ->distinct()
            ->select('COUNT(remesa.id), SUM(remesa.importe_entregar)')
            ->join('remesa.factura', 'factura')
            ->andWhere('factura.created >= :fi and factura.created <= :ff')
            ->join('factura.estado', 'estado')
            ->andWhere('estado.codigo <> :cod')
            ->setParameter('fi', $params['fi'])
            ->setParameter('ff', $params['ff'])
            ->setParameter('cod', NmEstado::ESTADO_CANCELADA);

        if ($tnUser instanceof TnAgencia) {
            $query->andWhere('factura.agencia = :agc')
                ->setParameter('agc', $tnUser);
        }

        if ($moneda) {
            $query->join('remesa.moneda', 'moneda')
                ->andWhere('moneda.simbolo = :mon')
                ->setParameter('mon', $moneda);
        }

        if ($tnUser instanceof TnAgente) {
            $query->andWhere('factura.agente = :agt')
                ->setParameter('agt', $tnUser);
        }

        return $query->getQuery()->getArrayResult();
    }

    public function totalRemesasProvincia($provincia, $tnUser = null, $moneda)
    {
        $query = $this->createQueryBuilder('remesa')
            ->distinct()
            ->select('COUNT(remesa.id), SUM(remesa.importe_entregar)')
            ->join('remesa.factura', 'factura')
            ->join('remesa.destinatario', 'destinatario')
            ->join('factura.estado', 'estado')
            ->andWhere('estado.codigo <> :cod')
            ->andWhere('destinatario.provincia = :prov')
            ->setParameter('cod', NmEstado::ESTADO_CANCELADA)
            ->setParameter('prov', $provincia);

        if ($tnUser instanceof TnAgencia) {
            $query->andWhere('factura.agencia = :agc')
                ->setParameter('agc', $tnUser);
        }

        if ($moneda) {
            $query->join('remesa.moneda', 'moneda')
                ->andWhere('moneda.simbolo = :mon')
                ->setParameter('mon', $moneda);
        }

        if ($tnUser instanceof TnAgente) {
            $query->andWhere('factura.agente = :agt')
                ->setParameter('agt', $tnUser);
        }

        return $query->getQuery()->getArrayResult();
    }


    public function advancedSearchDistribuidor($params, $distribuidor)
    {
        $query = $this->createQueryBuilder('remesa')
            ->join('remesa.factura', 'factura')
            ->join('factura.estado', 'estado')
            ->join('remesa.destinatario', 'destinatario')
            ->join('remesa.distribuidor', 'distribuidor')
            ->where('distribuidor.id = :dist')
            ->setParameter('dist', $distribuidor);

        if ($params['orden'] != "") {
            $query->andWhere('factura.no_factura like :ord')
                ->setParameter('ord', '%' . $params['orden'] . '%');
        }

        if ($params['destinatario'] != "") {
            $paramD = explode("/", $params['destinatario']);
            if (count($paramD) == 2) {
                $query->andWhere('destinatario.nombre like :name and destinatario.apellidos like :apell')
                    ->setParameter('name', '%' . $paramD[0] . '%')
                    ->setParameter('apell', '%' . $paramD[1] . '%');
            } else {
                $query->andWhere('destinatario.nombre like :name or destinatario.apellidos like :apell')
                    ->setParameter('name', '%' . $paramD[0] . '%')
                    ->setParameter('apell', '%' . $paramD[0] . '%');
            }
        }

        if ($params['direccion'] != "") {
            $query->andWhere('destinatario.direccion like :dir')
                ->setParameter('dir', '%' . $params['direccion'] . '%');
        }

        if (array_key_exists('estado', $params)) {
            $states = [];
            if (in_array('pendiente', $params['estado'])) {
                $states[] = '02';
                $states[] = '03';
            } elseif (in_array('entregada', $params['estado'])) {
                $states[] = '04';
            } elseif (in_array('cancelada', $params['estado'])) {
                $states[] = '05';
            }
            $query->andWhere('estado.codigo IN (:estados)')
                ->setParameter('estados', $states);
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

        if (array_key_exists('municipio', $params)) {
            $query->andWhere('destinatario.municipio IN (:muncs)')
                ->setParameter('muncs', $params['municipio']);
        }

        if (array_key_exists('provincia', $params)) {
            $query->andWhere('destinatario.provincia IN (:provs)')
                ->setParameter('provs', $params['provincia']);
        }

        $query->orderBy('remesa.created', 'DESC');

        return $query->getQuery()->getResult();
    }

    public function findRemesasDestinatarioCreateRemesa($params)
    {
        $query = $this->createQueryBuilder('remesa')
            ->distinct()
            ->join('remesa.factura', 'factura')
            ->join('factura.estado', 'estado')
            ->andWhere('factura.created >= :fi and factura.created <= :ff')
            ->andWhere('estado.codigo <> :cod')
            ->setParameter('fi', $params['fi'])
            ->setParameter('cod', NmEstado::ESTADO_CANCELADA)
            ->setParameter('ff', $params['ff']);

        if (array_key_exists('agencia', $params)) {
            $query->andWhere('factura.agencia = :agc')
                ->setParameter('agc', $params['agencia']);
        }

        if (array_key_exists('agente', $params)) {
            $query->andWhere('factura.agente is not null and factura.agente = :agt')
                ->setParameter('agt', $params['agente']);
        }

        if (array_key_exists('maximo', $params)) {
            $query->andWhere('factura.total = :max')
                ->setParameter('max', $params['maximo']);
        }

        return $query->getQuery()->getResult();
    }

    public function getRemesasPendientesEnvioDistribuidor(TnDistribuidor $tnDistribuidor, TnReporteEnvio $reporte = null)
    {
        if ($reporte != null) {
            $lastDate = $reporte->getLastDate();
            $result = $this->createQueryBuilder('tr')
                ->join('tr.factura', 'factura')
                ->join('factura.estado', 'estado')
                ->where('estado.codigo IN (:estados)')
                ->andWhere('tr.distribuidor = :dist')
                ->andWhere('tr.entregada = false')
                ->andWhere('factura.aprobadaAt > :aprov or factura.distribuidaAt > :aprov')
                ->andWhere('tr.reporteEnvio IS NULL')
                ->setParameter('dist', $tnDistribuidor)
                ->setParameter('aprov', $lastDate)
                ->setParameter('estados', [NmEstado::ESTADO_APROBADA, NmEstado::ESTADO_DISTRIBUCION])
                ->orderBy('tr.created', "ASC")
                ->getQuery()->getResult();
        } else {
            $result = $this->createQueryBuilder('tr')
                ->join('tr.factura', 'factura')
                ->join('factura.estado', 'estado')
                ->where('estado.codigo IN (:estados)')
                ->andWhere('tr.distribuidor = :dist')
                ->andWhere('tr.entregada = false')
                ->andWhere('tr.reporteEnvio IS NULL')
                ->setParameter('dist', $tnDistribuidor)
                ->setParameter('estados', [NmEstado::ESTADO_APROBADA, NmEstado::ESTADO_DISTRIBUCION])
                ->orderBy('tr.created', "ASC")
                ->getQuery()->getResult();
        }

        return $result;
    }

    public function findPanelDistribuidoresInRango($params, $inRange = '')
    {
        $query = $this->createQueryBuilder('tr')
            ->select('COUNT(tr.id)')
            ->join('tr.factura', 'factura')
            ->join('factura.estado', 'estado')
            ->join('tr.reporteEnvio', 'reporteEnvio')
            ->where('reporteEnvio.created >= :fi and reporteEnvio.created <= :ff')
            ->andWhere('tr.distribuidor is not null')
            ->setParameter('fi', $params['fi'])
            ->setParameter('ff', $params['ff'])
            ->orderBy('tr.created', "ASC");

        if (array_key_exists('distribuidores', $params)) {
            $query->join('tr.distribuidor', 'distribuidor')
                ->andWhere('distribuidor.id IN (:dist)')
                ->setParameter('dist', $params['distribuidores']);
        }

        if (array_key_exists('monedas', $params)) {
            $query->join('tr.moneda', 'moneda')
                ->andWhere('moneda.id IN (:mon)')
                ->setParameter('mon', $params['monedas']);
        }

        if ($inRange == true) {
            $query->andWhere('factura.fechaContable >= :fi and factura.fechaContable <= :ff');
        } elseif ($inRange === false) {
            $query->andWhere('factura.fechaContable < :fi');
        }

        $query->andWhere('estado.codigo IN (:estados)')
            ->setParameter('estados', NmEstado::getCodeDistribuidor());

        return $query->getQuery()->getSingleScalarResult();
    }
}
