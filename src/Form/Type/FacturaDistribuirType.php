<?php

namespace App\Form\Type;

use App\Entity\TnFactura;
use App\Entity\TnRemesa;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FacturaDistribuirType extends AbstractType
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('remesas', CollectionType::class, array(
                'translation_domain' => 'AppBundle',
                'entry_type' => RemesaDistribuidorType::class,
                'label' => 'backend.factura.dist.remesas',
                'required' => true
            ));

        $builder->addEventListener(FormEvents::POST_SET_DATA, function ($event) {
            $form = $event->getForm();
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (PreSubmitEvent $event) {
            $form = $event->getForm();
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => TnFactura::class
        ]);
    }
}
