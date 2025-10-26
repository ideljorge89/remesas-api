<?php

namespace App\Form\Type;

use App\Entity\TnRepartidor;
use App\Twig\AppExtension;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EnvioAdminRepartidorType extends AbstractType
{

    /**
     * @var AppExtension
     */
    private $twig_extension;


    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('repartidor', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => TnRepartidor::class,
                'label' => 'backend.transferencia.table.repartidor',
                'query_builder' => function (EntityRepository $er) {
                    $query = $er->createQueryBuilder('rp')
                        ->where("rp.enabled = true");
                    return $query;
                },
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
