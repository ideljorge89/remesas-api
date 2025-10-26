<?php

namespace App\Form\Type;

use App\Entity\NmEstado;
use App\Entity\NmMoneda;
use App\Entity\NmMunicipio;
use App\Entity\TnDistribuidor;
use App\Twig\AppExtension;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CierreDistribuidorType extends AbstractType
{

    /**
     * @var AppExtension
     */
    private $twig_extension;


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
            ->add('distribuidor', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => TnDistribuidor::class,
                'label' => 'backend.factura.table.distribuidor',
                'query_builder' => function (EntityRepository $er) {
                    $query = $er->createQueryBuilder('d')
                        ->where("d.enabled = true");
                    return $query;
                },
                'multiple' => true,
                'required' => false
            ))
            ->add('moneda', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => NmMoneda::class,
                'query_builder' => function (EntityRepository $er) {
                    $query = $er->createQueryBuilder('m')
                        ->where("m.enabled = true");
                    return $query;
                },
                'choice_label' => function (NmMoneda $nmMoneda) {
                    return $nmMoneda->getSimbolo();
                },
                'label' => 'backend.factura.form.fields.moneda',
                'multiple' => true,
                'required' => false
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
