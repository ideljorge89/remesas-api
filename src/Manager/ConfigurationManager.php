<?php

namespace App\Manager;


use App\Entity\TnConfiguration;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ConfigurationManager
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function has($option)
    {
        return $this->
        container->
        get('doctrine')->
        getManager()->
        getRepository('App:TnConfiguration')
            ->getHas($option);
    }

    public function get($option, $default = NULL)
    {
        return $this->
        container->
        get('doctrine')->
        getManager()->
        getRepository('App:TnConfiguration')
            ->getOption($option, $default);
    }

    public function set($option, $value)
    {
        $record = $this->
        container->
        get('doctrine')->
        getManager()->
        getRepository('App:TnConfiguration')->
        findOneBy(array('attr' => $option));
        if (!$record)
            throw new \Exception("The $option not exist in configuration table");
        $record->setValue($value);
        $this->container->get('doctrine')->getManager()->persist($record);
    }

    public function create($field, $data, $locked = false)
    {
        $record = $this->
        container->
        get('doctrine')->
        getManager()->
        getRepository('App:TnConfiguration')->
        findOneBy(array('attr' => $field));
        if ($record)
            throw new \Exception('This value already exists.');

        $p = new TnConfiguration();
        $p->setValue($data);
        $p->setAttr($field);
        $p->setLocked($locked);

        $this->container->get('doctrine')->getManager()->persist($p);
        $this->container->get('doctrine')->getManager()->flush();

        return $this;
    }

    /**
     * Eliminando las peticiones de registros de usuarios no confirmados en al menos 72 horas
     */
    public function deleteExpiredRegistrationUser()
    {
        $em = $this->container->get('doctrine.orm.entity_manager');
        try {
            $usersRegistration = $em->getRepository('App:TnUser')->findUsersNoConfirmation();

            $em->getConnection()->beginTransaction();
            $today = new \DateTime('now');
            foreach ($usersRegistration as $user) {
                if ($user->getConfirmationToken() != null) {
                    if ($user->getCreated() != null) {
                        if ($user->getCreated()->diff($today)->days >= 3) {
                            $em->remove($user);
                            $em->flush();
                        }
                    }
                }
            }
            $em->getConnection()->commit();
            return true;
        } catch (\Exception $e) {
            $em->getConnection()->rollback();
            return false;
        }
    }


    function convertCurrency($amount, $from_currency, $to_currency)
    {
        $apikey = $this->container->getParameter('currency_converter_api_key');
        $from_Currency = urlencode($from_currency);
        $to_Currency = urlencode($to_currency);
        $query = "{$from_Currency}_{$to_Currency}";
        $json = file_get_contents("https://free.currconv.com/api/v7/convert?q={$query}&compact=y&apiKey={$apikey}");
        $obj = json_decode($json, true);
        $val = $obj["$query"];
        $total = $val["val"] * $amount;
        return number_format($total, 4, '.', '');
    }


    function setConversionCurrencies($from, $to)
    {
        $V = $this->convertCurrency(1, $from, $to);

        if ($V != null) {
            try {
                $this->set('currency_rate_' . $from . "_" . $to, $V);
            } catch (\Exception $e) {
                $this->create('currency_rate_' . $from . "_" . $to, $V, true);
            }
        }
    }
}