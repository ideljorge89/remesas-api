<?php

namespace App\Form;

use App\Entity\NmGrupoPagoTransf;
use App\Entity\NmMoneda;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NmGrupoPagoTransfType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nombre', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.grupo_pago_transf.form.fields.nombre',
                'required' => true
            ))
            ->add('enabled', CheckboxType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.grupo_pago_transf.form.fields.enabled',
                'required' => false
            ))
            ->add('porcentaje', NumberType::class, array(
                'scale' => 2,
                'translation_domain' => 'AppBundle',
                'label' => 'backend.grupo_pago_transf.form.fields.porciento',
                'required' => true,
                'attr' => [
                    'class' => 'money',
                    'data-rule-number' => true,
                    'data-rule-minLength' => 1,
                    'data-rule-maxLength' => 5
                ]
            ))
            ->add('moneda', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => NmMoneda::class,
                'query_builder' => function (EntityRepository $er) {
                    $query = $er->createQueryBuilder('m')
                        ->where("m.aviable = true")
                        ->andWhere('m.codigo = :cod')
                        ->setParameter('cod', NmMoneda::CURRENCY_USD);
                    return $query;
                },
                'choice_label' => function (NmMoneda $nmMoneda) {
                    return $nmMoneda->getSimbolo();
                },
                'label' => 'backend.grupo_pago_transf.form.fields.moneda',
                'required' => true
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => NmGrupoPagoTransf::class,
        ]);
    }
}
