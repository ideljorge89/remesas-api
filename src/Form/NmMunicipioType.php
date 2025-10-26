<?php

namespace App\Form;

use App\Entity\NmMoneda;
use App\Entity\NmMunicipio;
use App\Entity\NmProvincia;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;

class NmMunicipioType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('codigo', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.municipio.form.fields.codigo',
                'required' => true
            ))
            ->add('tasaFija', NumberType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.municipio.form.fields.tasa',
                'required' => true
            ))
            ->add('monedasEntrega', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => NmMoneda::class,
                'label' => 'backend.municipio.form.fields.moneda',
                'placeholder' => 'backend.comun.form.select_placeholder',
                'choice_label' => function (NmMoneda $nmMoneda) {
                    return $nmMoneda->getSimbolo();
                },
                'query_builder' => function (EntityRepository $er) {
                    $query = $er->createQueryBuilder('mn')
                        ->where("mn.aviable = true and mn.enabled = true");
                    return $query;
                },
                'constraints' => [new Count(['min' => 1])],
                'multiple' => true,
                'required' => true
            ))
            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => NmMunicipio::class,
        ]);
    }
}
