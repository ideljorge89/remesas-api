<?php

namespace App\Form;

use App\Entity\NmGrupoPago;
use App\Entity\NmMoneda;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NmGrupoPagoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nombre', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.grupo_pago.form.fields.nombre',
                'required' => true
            ))
            ->add('enabled', CheckboxType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.grupo_pago.form.fields.enabled',
                'required' => false
            ))
            ->add('porcentaje', NumberType::class, array(
                'scale' => 2,
                'translation_domain' => 'AppBundle',
                'label' => 'backend.grupo_pago.form.fields.porciento',
                'required' => true,
                'attr' => [
                    'class' => 'money',
                    'data-rule-number' => true,
                    'data-rule-minLength' => 1,
                    'data-rule-maxLength' => 5
                ]
            ))
            ->add('minimo', NumberType::class, array(
                'scale' => 2,
                'translation_domain' => 'AppBundle',
                'label' => 'backend.grupo_pago.form.fields.minimo',
                'required' => false,
                'attr' => [
                    'data-rule-number' => true,
                ]
            ))
            ->add('tipoUtilidad', ChoiceType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.grupo_pago.form.fields.tipo',
                'placeholder' => 'backend.comun.form.select_placeholder',
                'required' => false,
                'choices' => NmGrupoPago::getAllTipos()
            ))
            ->add('utilidadFija', NumberType::class, array(
                'scale' => 2,
                'translation_domain' => 'AppBundle',
                'label' => 'backend.grupo_pago.form.fields.utilidad_fija',
                'required' => false,
                'attr' => [
                    'data-rule-number' => true,
                ]
            ))
            ->add('moneda', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => NmMoneda::class,
                'query_builder' => function (EntityRepository $er) {
                    $query = $er->createQueryBuilder('m')
                        ->where("m.aviable = true");
                    return $query;
                },
                'choice_label' => function (NmMoneda $nmMoneda) {
                    return $nmMoneda->getSimbolo();
                },
                'label' => 'backend.grupo_pago.form.fields.moneda',
                'placeholder' => 'Seleccione una moneda',
                'required' => true
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => NmGrupoPago::class,
        ]);
    }
}
