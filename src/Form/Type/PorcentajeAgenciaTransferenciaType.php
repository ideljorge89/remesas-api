<?php

namespace App\Form\Type;

use App\Entity\TnAgencia;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PorcentajeAgenciaTransferenciaType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('porcentajesTransferencias', CollectionType::class, array(
                'translation_domain' => 'AppBundle',
                'entry_type' => AgenciaOperacionTransfType::class,
                'label' => 'backend.agencia.form.fields.porcientos',
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
            'data_class' => TnAgencia::class
        ]);
    }
}
