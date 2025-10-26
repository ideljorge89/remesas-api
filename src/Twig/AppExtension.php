<?php

namespace App\Twig;

use App\Entity\NmEstado;
use App\Entity\NmEstadoTransferencia;
use App\Entity\NmGrupoPago;
use App\Entity\NmGrupoPagoAgente;
use App\Entity\TnAgencia;
use App\Entity\TnAgente;
use App\Entity\TnApoderado;
use App\Entity\TnDistribuidor;
use App\Entity\TnEmisor;
use App\Entity\TnFactura;
use App\Entity\TnOperacionAgencia;
use App\Entity\TnOperacionAgente;
use App\Entity\TnSaldoAgencia;
use App\Entity\TnSaldoAgente;
use App\Entity\TnTransferencia;
use App\Form\Type\StatesType;
use App\Util\DirectoryNamerUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Loggable\Entity\LogEntry;
use Gedmo\Translatable\Entity\Translation;
use Symfony\Bridge\Twig\Extension\AssetExtension;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Intl\Countries;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{

    private $em;
    private $container;
    private $security;
    private $asset;
    private $directoryNamerUtil;
    private $user;

    public function __construct(ContainerInterface $container, EntityManagerInterface $entityManager, AssetExtension $asset, DirectoryNamerUtil $directoryNamerUtil)
    {
        $this->em = $entityManager;
        $this->container = $container;
        $this->security = $this->container->get('security.authorization_checker');
        $this->asset = $asset;
        $this->directoryNamerUtil = $directoryNamerUtil;
        $this->user = $this->container->get('security.token_storage')->getToken() ? $this->container->get('security.token_storage')->getToken()->getUser() : null;
    }

    public function getFilters()
    {
        return array(
            new TwigFilter('country', array($this, 'countryFilter')),
            new TwigFilter('state', array($this, 'stateFilter')),
        );
    }

    public function getFunctions()
    {
        return array(
            'validaNumero' => new TwigFunction('validaNumero', array($this, 'validaNumero')),
            'contenedorHandlerValue' => new TwigFunction('contenedorHandlerValue', array($this, 'contenedorHandlerValue')),
            'contenedorHandlerHeader' => new TwigFunction('contenedorHandlerHeader', array($this, 'contenedorHandlerHeader')),
            'documentPublicPath' => new TwigFunction('documentPublicPath', array($this, 'documentPublicPath')),
            'toArray' => new TwigFunction('toArray', array($this, 'decodeToArray')),
            'toStringDate' => new TwigFunction('toStringDate', array($this, 'toStringDate')),
            'translateEn' => new TwigFunction('translateEn', array($this, 'findTranslateEn')),
            'translateEs' => new TwigFunction('translateEs', array($this, 'findTranslateEs')),
            'daysDiff' => new TwigFunction('daysDiff', array($this, 'daysDiff')),
            'translateEntity' => new TwigFunction('translateEntity', array($this, 'translateEntity')),
            'value_config' => new TwigFunction('value_config', array($this, 'value_config')),
            'json_decode' => new TwigFunction('json_decode', array($this, 'json_decode')),
            'roundTo' => new TwigFunction('roudTo', array($this, 'roundTo')),
            'percentUser' => new TwigFunction('percentUser', array($this, 'percentUser')),
            'findAsignacionZona' => new TwigFunction('findAsignacionZona', array($this, 'findAsignacionZona')),
            'findEstado' => new TwigFunction('findEstado', array($this, 'findEstado')),
            'findEstadoTransferencia' => new TwigFunction('findEstadoTransferencia', array($this, 'findEstadoTransferencia')),
            'findAgente' => new TwigFunction('findAgente', array($this, 'findAgente')),
            'findAgencia' => new TwigFunction('findAgencia', array($this, 'findAgencia')),
            'findEmisor' => new TwigFunction('findEmisor', array($this, 'findEmisor')),
            'logsFactura' => new TwigFunction('logsFactura', array($this, 'logsFactura')),
            'logsTransferencia' => new TwigFunction('logsTransferencia', array($this, 'logsTransferencia')),
            'showImporte' => new TwigFunction('showImporte', array($this, 'showImporte')),
            'showImporteTrans' => new TwigFunction('showImporteTrans', array($this, 'showImporteTrans')),
            'is_unlimited' => new TwigFunction('is_unlimited', array($this, 'is_unlimited')),
            'ready_to_aprove' => new TwigFunction('ready_to_aprove', array($this, 'ready_to_aprove')),
            'is_apoderado' => new TwigFunction('is_apoderado', array($this, 'is_apoderado')),
            'saldo_agencia' => new TwigFunction('saldo_agencia', array($this, 'saldo_agencia')),
            'saldo_agente' => new TwigFunction('saldo_agente', array($this, 'saldo_agente')),
            'saldo_distribuido' => new TwigFunction('saldo_distribuido', array($this, 'saldo_distribuido')),
            'saldo_distribuido_agencia' => new TwigFunction('saldo_distribuido_agencia', array($this, 'saldo_distribuido_agencia')),
            'resto' => new TwigFunction('resto', array($this, 'resto')),
            'porciento_operacion_agencia' => new TwigFunction('porciento_operacion_agencia', array($this, 'porciento_operacion_agencia')),
            'porciento_operacion_agente' => new TwigFunction('porciento_operacion_agente', array($this, 'porciento_operacion_agente')),
            'tipo_fichero' => new TwigFunction('tipo_fichero', array($this, 'tipo_fichero')),
            'dump_exit' => new TwigFunction('dump_exit', array($this, 'dump_exit')),
        );
    }


    public function dump_exit()
    {
        dump("Since here");
        exit;
    }

    public function is_apoderado(TnAgencia $tnAgencia)
    {
        $apoderado = $this->em->getRepository(TnApoderado::class)->findOneBy(['agencia' => $tnAgencia]);
        if (!is_null($apoderado)) {
            return true;
        }
        return false;
    }


    public function tipo_fichero($type)
    {
        $tipos = [
            'destinatario' => "Destinatario",
            'factura-TE' => "Factura-Caribe",
            'factura-CA' => "Factura-Canadá",
            'factura-TP' => "Factura-Tramipro",
            'factura-TM' => "Factura-Multiple",
            'factura-CMX' => "Factura-CubaMax",
            'factura-VCB' => "Factura-VaCuba"
        ];
        return $tipos[$type];
    }

    public function porciento_operacion_agencia($tnAgencia, $moneda)
    {
        $nmGrupoPago = $this->em->getRepository(NmGrupoPago::class)->grupoPagoAgencia($tnAgencia, $moneda);
        $porcentajeOperacion = $this->em->getRepository(TnOperacionAgencia::class)->findOneBy(['agencia' => $tnAgencia, 'grupoPago' => $nmGrupoPago]);
        if (!is_null($porcentajeOperacion) && !is_null($porcentajeOperacion->getPorcentaje())) {
            return $porcentajeOperacion->getPorcentaje();
        }
        return null;
    }

    public function porciento_operacion_agente($tnAgente, $moneda)
    {
        $nmGrupoPago = $this->em->getRepository(NmGrupoPagoAgente::class)->grupoPagoAgente($tnAgente, $moneda);
        $porcentajeOperacion = $this->em->getRepository(TnOperacionAgente::class)->findOneBy(['agente' => $tnAgente, 'grupoPagoAgente' => $nmGrupoPago]);
        if (!is_null($porcentajeOperacion) && !is_null($porcentajeOperacion->getPorcentaje())) {
            return $porcentajeOperacion->getPorcentaje();
        }
        return null;
    }

    public function saldo_agencia(TnAgencia $tnAgencia, NmGrupoPago $grupoPago)
    {
        $saldoAgencia = $this->em->getRepository(TnSaldoAgencia::class)->findOneBy(['agencia' => $tnAgencia, 'grupoPago' => $grupoPago]);
        if (!is_null($saldoAgencia)) {
            return $saldoAgencia->getSaldo();
        }
        return 0;
    }

    public function saldo_agente(TnAgente $tnAgente, NmGrupoPagoAgente $grupoPago)
    {
        $saldoAgente = $this->em->getRepository(TnSaldoAgente::class)->findOneBy(['agente' => $tnAgente, 'grupoPagoAgente' => $grupoPago]);
        if (!is_null($saldoAgente)) {
            return $saldoAgente->getSaldo();
        }
        return 0;
    }

    public function saldo_distribuido($moneda)
    {
        $total = 0;
        $tnAgencias = $this->em->getRepository(TnAgencia::class)->findBy(['enabled' => true]);
        foreach ($tnAgencias as $tnAgencia) {
            foreach ($tnAgencia->getSaldoMonedas() as $saldoMoneda) {
                if ($saldoMoneda->getGrupoPago()->getMoneda()->getSimbolo() == $moneda) {
                    $total += $saldoMoneda->getSaldo();
                }
            }

        }
        return $total;
    }

    public function saldo_distribuido_agencia(TnAgencia $tnAgencia, $moneda)
    {
        $total = 0;
        foreach ($tnAgencia->getAgentes() as $tnAgente) {
            foreach ($tnAgente->getSaldoMonedas() as $saldoMoneda) {
                if (!is_null($saldoMoneda->getGrupoPagoAgente()) && !is_null($saldoMoneda->getGrupoPagoAgente()->getMoneda()) && $saldoMoneda->getGrupoPagoAgente()->getMoneda()->getSimbolo() == $moneda) {
                    $total += $saldoMoneda->getSaldo();
                }
            }

        }
        return $total;
    }

    public function ready_to_aprove(TnFactura $factura)
    {
        foreach ($factura->getRemesas() as $remesa) {
            if ($remesa->getDistribuidor() != null) {
                $tnDistribuidor = $this->em->getRepository(TnDistribuidor::class)->findByDistZona($remesa->getDistribuidor(), $remesa->getDestinatario()->getMunicipio());
                if ($tnDistribuidor == null) {
                    return false;
                }
            }
        }

        return true;
    }

    public function resto($number, $check, $same)
    {
        if (!$same) {
            if ($number != 0 && $number != $check && $number % $check == 0) {
                return true;
            }
        } else {
            if ($number != 0 && $number % $check == 0) {
                return true;
            }
        }

        return false;
    }

    public function logsFactura($id)
    {
        $tnFactura = $this->em->getRepository(TnFactura::class)->find($id);
        $repository = $this->em->getRepository(LogEntry::class);
        $logs = $repository->getLogEntries($tnFactura);

        return $logs;
    }

    public function logsTransferencia($id)
    {
        $tnTransferencia = $this->em->getRepository(TnTransferencia::class)->find($id);
        $repository = $this->em->getRepository(LogEntry::class);
        $logs = $repository->getLogEntries($tnTransferencia);

        return $logs;
    }

    public function findEmisor($id)
    {
        $emisor = $this->em->getRepository(TnEmisor::class)->find($id);
        return $emisor ? $emisor : null;
    }

    public function findAgente($id)
    {
        $agente = $this->em->getRepository(TnAgente::class)->find($id);
        return $agente ? $agente : null;
    }

    public function findAgencia($id)
    {
        $agencia = $this->em->getRepository(TnAgencia::class)->find($id);
        return $agencia ? $agencia : null;
    }

    public function findEstado($id)
    {
        $estado = $this->em->getRepository(NmEstado::class)->find($id);
        return $estado ? $estado->getCodigo() : null;
    }

    public function findEstadoTransferencia($id)
    {
        $estado = $this->em->getRepository(NmEstadoTransferencia::class)->find($id);
        return $estado ? $estado->getCodigo() : null;
    }

    public function roudTo($number)
    {
        return round($number, 2);
    }

    public function percentUser()
    {
        return $this->user->getPorcentaje();
    }

    public function showImporte(TnFactura $tnFactura)
    {
        $user = $this->container->get('security.token_storage')->getToken()->getUser();

        $security = $this->container->get('security.authorization_checker');
        if ($security->isGranted("ROLE_AGENCIA")) {
            if (($tnFactura->getAgente() == null || ($tnFactura->getAgente() != null && $tnFactura->getAgente()->getTipoAgente() == TnAgente::TIPO_INTERNO)) && $tnFactura->getAgencia()->getId() == $user->getAgencia()->getId()) {
                return true;
            }
        } elseif ($security->isGranted("ROLE_AGENTE")) {
            if ($tnFactura->getAgente() && $tnFactura->getAgente()->getId() == $user->getAgente()->getId()) {
                return true;
            }
        } else {
            if ($tnFactura->getAgencia() == null && $tnFactura->getAgente() == null) {
                return true;
            }
        }
        return false;
    }

    public function is_unlimited()
    {
        $user = $this->container->get('security.token_storage')->getToken()->getUser();

        $security = $this->container->get('security.authorization_checker');
        if ($security->isGranted("ROLE_AGENCIA")) {
            if ($user->getAgencia() != null && $user->getAgencia()->getUnlimited()) {
                return true;
            }
        }
        return false;
    }

    public function showImporteTrans(TnTransferencia $tnTransferencia)
    {
        $user = $this->container->get('security.token_storage')->getToken()->getUser();

        $security = $this->container->get('security.authorization_checker');
        if ($security->isGranted("ROLE_AGENCIA")) {
            if (($tnTransferencia->getAgente() == null || ($tnTransferencia->getAgente() != null && $tnTransferencia->getAgente()->getTipoAgente() == TnAgente::TIPO_INTERNO)) && $tnTransferencia->getAgencia()->getId() == $user->getAgencia()->getId()) {
                return true;
            }
        } elseif ($security->isGranted("ROLE_AGENTE")) {
            if ($tnTransferencia->getAgente() && $tnTransferencia->getAgente()->getId() == $user->getAgente()->getId()) {
                return true;
            }
        } else {
            if ($tnTransferencia->getAgencia() == null && $tnTransferencia->getAgente() == null) {
                return true;
            }
        }
        return false;
    }

    public function findAsignacionZona(TnFactura $factura)
    {
        return $this->container->get('factura_manager')->findAsignacionesZona($factura);
    }

    public function orderByOrdenArrayList($collection)
    {
        $iterator = $collection->getIterator();
        $iterator->uasort(function ($a, $b) {
            return ($a->getOrden() < $b->getOrden()) ? -1 : 1;
        });
        $collection = new ArrayCollection(iterator_to_array($iterator));
        return $collection;
    }

    public function value_config($value)
    {
        return $this->container->get('configuration')->get($value);
    }


    public function countryFilter($countryCode, $locale = "es")
    {

        $c = Countries::getNames($locale);
        //$c = Countries\Symfony\Component\Intl\Locale\Locale::getDisplayCountries($locale);

        return array_key_exists($countryCode, $c)
            ? $c[$countryCode]
            : $countryCode;
    }

    public function stateFilter($stateCode)
    {
        $s = StatesType::getNames();

        return array_key_exists($stateCode, $s)
            ? $s[$stateCode]
            : $stateCode;
    }


    public function translateEntity($entity, $locale, $fild)
    {
        $repository = $this->em->getRepository(Translation::class);
        if (is_object($entity)) {
            $trans = $repository->findTranslations($entity);
            if (isset($trans[$locale])) {
                if (isset($trans[$locale][$fild])) {
                    return $trans[$locale][$fild];
                } else {
                    return '';
                }
            }
        }
        return '';
    }

    public function daysDiff($date)
    {
        $hoy = new \DateTime('now');
        $interval = $hoy->diff($date);
        return ($interval->format('%d'));
    }

    public function findTranslateEn($entity)
    {
        $repository = $this->em->getRepository(Translation::class);
        $trans = $repository->findTranslations($entity);
        if (isset($trans['en'])) {
            if (isset($trans['en']['nombre'])) {
                return $trans['en']['nombre'];
            } else {
                return '';
            }
        }
    }

    public function findTranslateEs($entity)
    {
        $repository = $this->em->getRepository(Translation::class);
        $trans = $repository->findTranslations($entity);
        if (isset($trans['es'])) {
            if (isset($trans['es']['nombre'])) {
                return $trans['es']['nombre'];
            } else {
                return '';
            }
        }
    }


    public function documentPublicPath($object)
    {
        try {
            $r = '';
            try {
                $r = $this->directoryNamerUtil->getDocumentPath($object);
            } catch (\Exception $exception) {

            }

            if (!$r) {
                $r = $this->directoryNamerUtil->getDocumentOldPath($object);
            }
            $asset = '';
            if ($r && is_file($r)) {
                $root = $this->container->getParameter('kernel.project_dir');
                $web = $this->container->getParameter('web_dir');
                $asset = str_replace($root . '/', '', str_replace($web . '/', '', $r));
            }
            return $asset;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function decodeToArray($string)
    {
        return json_decode($string, true);
    }

    public function toStringDate($fecha)
    {
        return $fecha->format('d-m-Y');
    }

    public function json_decode($data)
    {
        return @json_decode($data, true);
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'app_extension';
    }
}
