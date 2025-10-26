<?php

namespace App\Util;

use Cart\Storage\Store;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CartSessionStorage implements Store
{
    /**
     * @var Session
     */
    private $session;

    public function __construct(SessionInterface $session){
        $this->session=$session;
    }
    /**
     * {@inheritdoc}
     */
    public function get($cartId)
    {
        return $this->session->get('cart/'.$cartId,serialize(array()));

        /*return isset($_SESSION[$cartId]) ? $_SESSION[$cartId] : serialize(array());*/
    }

    /**
     * {@inheritdoc}
     */
    public function put($cartId, $data)
    {
        $this->session->set('cart/'.$cartId,$data);
        /*$_SESSION[$cartId] = $data;*/
    }

    /**
     * {@inheritdoc}
     */
    public function flush($cartId)
    {
        $this->session->remove('cart/'.$cartId);
        /*unset($_SESSION[$cartId]);*/
    }
}
