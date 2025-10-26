<?php

namespace App\Form;

use App\Entity\TnListaNegra;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TnListaNegraType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nombre', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.lista_negra.form.fields.nombre',
                'required' => true
            ))
            ->add('apellidos', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.lista_negra.form.fields.apellidos',
                'required' => true
            ))
            ->add('phone', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.lista_negra.form.fields.telefono',
                'required' => false
            ))
            ->add('ci', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.lista_negra.form.fields.ci',
                'required' => false,
                'empty_data' => ""
            ))
            ->add('enabled', CheckboxType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.lista_negra.form.fields.enabled',
                'required' => false
            ))
            ->add('direccion', TextareaType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.lista_negra.form.fields.direccion',
                'required' => true
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => TnListaNegra::class,
        ]);
    }
}
