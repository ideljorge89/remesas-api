<?php

namespace App\Form\Type;

use App\Entity\NmEstado;
use App\Entity\TnAgencia;
use App\Entity\TnApoderado;
use App\Entity\TnDistribuidor;
use App\Twig\AppExtension;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReporteAdminApoderadosType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('fechaInicio', DateTimeType::class, array(
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'required' => true,
                'attr' => array(
                    'class' => 'datepicker'
                )
            ))
            ->add('fechaFin', DateTimeType::class, array(
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'required' => true,
                'attr' => array(
                    'class' => 'datepicker'
                )
            ))
            ->add('apoderado', EntityType::class, array(
                'class' => TnApoderado::class,
                'label' => 'Apoderado',
                'required' => true,
                'choice_label' => function (TnApoderado $tnApoderado) {
                    return $tnApoderado->getNombre() . " - " . $tnApoderado->getAgencia()->getNombre();
                },
            ))
            ->add('estado', ChoiceType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.factura.form.fields.estado',
                'placeholder' => 'backend.comun.form.select_placeholder',
                'required' => false,
                'choices' => NmEstado::getEstados(),
                'multiple' => true
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
