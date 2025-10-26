<?php

namespace App\Form;

use App\Entity\NmMunicipio;
use App\Entity\NmProvincia;
use App\Entity\TnDistribuidor;
use App\Entity\TnRepartidor;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;

class TnRepartidorType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nombre', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.repartidor.form.fields.nombre',
                'required' => true
            ))
            ->add('observacion', TextareaType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.repartidor.form.fields.observacion',
                'required' => false
            ))
            ->add('comision', NumberType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.repartidor.form.fields.comision',
                'required' => true
            ))
            ->add('enabled', CheckboxType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.repartidor.form.fields.enabled',
                'required' => false
            ))
           ;

        $builder->addEventListener(FormEvents::POST_SET_DATA, function ($event) {
            if ($event->getData() != null) {
                $form = $event->getForm();
            }
        });
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($builder) {
                $form = $event->getForm();

            }
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => TnRepartidor::class,
        ]);
    }
}
