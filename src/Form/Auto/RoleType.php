<?php

namespace App\Form\Auto;

use Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RoleType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'required' => true,
            'choices' => array(
                'backend.user_menu.type.agencia' => 'ROLE_AGENCIA',
                'backend.user_menu.type.agente' => 'ROLE_AGENTE',
                'backend.user_menu.type.distribuidor' => 'ROLE_DISTRIBUIDOR',
            ),
            'translation_domain' => 'FOSUserBundle',
            'label' => 'Rol',
        ));
    }

    public function getParent()
    {
        return ChoiceType::class;
    }

    public function getName()
    {
        return 'role_type';
    }
}
