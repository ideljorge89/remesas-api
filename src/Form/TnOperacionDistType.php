<?php

namespace App\Form;

use App\Entity\NmMoneda;
use App\Entity\TnDistribuidor;
use App\Entity\TnOperacionDist;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TnOperacionDistType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('distribuidor', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => TnDistribuidor::class,
                'label' => 'backend.operacion.table.distribuidor',
                'query_builder' => function (EntityRepository $er) {
                    $query = $er->createQueryBuilder('d')
                        ->where("d.enabled = true");
                    return $query;
                },
                'placeholder' => 'backend.comun.form.select_placeholder',
                'choice_attr' => function (TnDistribuidor $tnDistribuidor) {
                    return [

                    ];
                },
                'choice_label' => function (TnDistribuidor $tnDistribuidor) {
                    return $tnDistribuidor->getNombre() . " " . $tnDistribuidor->getApellidos() . " - " . $tnDistribuidor->getPhone();
                },
                'required' => true
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
                'choice_attr' => function (NmMoneda $nmMoneda) {
                    return [
                        'data-tasa' => $nmMoneda->getTasaCambio(),
                        'data-minimo' => $nmMoneda->getMinimo()
                    ];
                },
                'label' => 'backend.operacion.form.fields.moneda',
                'placeholder' => 'Seleccione una moneda',
                'required' => true
            ))
            ->add('tipo', ChoiceType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.operacion.form.fields.tipo',
                'placeholder' => 'backend.comun.form.select_placeholder',
                'required' => false,
                'choices' => TnOperacionDist::getTipos(),
            ))
            ->add('importe', NumberType::class, array(
                'translation_domain' => 'AppBundle',
                'scale' => 2,
                'label' => 'backend.operacion.form.fields.importe',
                'required' => true,
                'attr' => [
                    'data-rule-number' => true
                ]
            ))
            ->add('notas', TextareaType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.operacion.form.fields.notas',
                'required' => false
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => TnOperacionDist::class,
        ]);
    }
}
