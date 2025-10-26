<?php

namespace App\Form\Type;

use App\Entity\NmEstado;
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

class ReporteAdminDistribuidorType extends AbstractType
{

    /**
     * @var AppExtension
     */
    private $twig_extension;


    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('distribuidor', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => TnDistribuidor::class,
                'label' => 'backend.factura.table.distribuidor',
                'query_builder' => function (EntityRepository $er) {
                    $query = $er->createQueryBuilder('d')
                        ->where("d.enabled = true");
                    return $query;
                },
                'required' => true
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
