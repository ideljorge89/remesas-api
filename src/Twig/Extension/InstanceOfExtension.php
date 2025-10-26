<?php

namespace App\Twig\Extension;


use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\TwigTest;

class InstanceOfExtension extends AbstractExtension
{

    /**
     * {@inheritdoc}
     */
    public function getTests()
    {
        return array(
            'instanceof' => new TwigTest('instanceof', array($this, 'isInstanceof'))
        );
    }

    public function getFunctions()
    {
        return array(
            'class' => new TwigFunction( 'class', array($this, 'getClass'))
        );
    }

    function isInstanceOf($var, $instance)
    {
        return $var instanceof $instance;
    }

    public function getClass($object)
    {
        return (new \ReflectionClass($object))->getShortName();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'instanceof_extension';
    }
}