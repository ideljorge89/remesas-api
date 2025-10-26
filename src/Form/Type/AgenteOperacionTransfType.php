<?php

namespace App\Form\Type;

use App\Entity\TnOperacionAgencia;
use App\Entity\TnOperacionAgente;
use App\Entity\TnOperacionAgenteTransf;
use App\Entity\TnSaldoAgencia;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class AgenteOperacionTransfType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('porcentaje', NumberType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.agencia.form.fields.porciento',
                'required' => false,
                'attr' => [
                    'data-rule-number' => true
                ], 'constraints' => [
                    new NotBlank()
                ]
            ));

        $builder->addEventListener(FormEvents::POST_SET_DATA, function ($event) {

        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (PreSubmitEvent $event) {

        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => TnOperacionAgenteTransf::class,
        ]);
    }
}
