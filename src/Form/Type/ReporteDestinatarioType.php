<?php

namespace App\Form\Type;

use App\Entity\NmEstado;
use App\Entity\TnAgencia;
use App\Entity\TnAgente;
use App\Twig\AppExtension;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReporteDestinatarioType extends AbstractType
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
            ->add('agencia', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => TnAgencia::class,
                'label' => 'backend.agente.table.agencia',
                'query_builder' => function (EntityRepository $er) {
                    $query = $er->createQueryBuilder('d')
                        ->where("d.enabled = true");
                    return $query;
                },
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
