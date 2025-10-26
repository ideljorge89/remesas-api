<?php

namespace App\Form\Type;

use App\Entity\NmEstado;
use App\Entity\NmEstadoTransferencia;
use App\Entity\NmMoneda;
use App\Entity\TnAgencia;
use App\Entity\TnAgente;
use App\Entity\TnDistribuidor;
use App\Entity\TnRepartidor;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdvancedSerachAsignarType extends AbstractType
{

    private $container;
    private $facturaManager;
    private $security;
    private $user;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->facturaManager = $container->get('factura_manager');
        $this->security = $this->container->get('security.authorization_checker');
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->user = $this->container->get('security.token_storage')->getToken()->getUser();

        $builder
            ->add('codigo', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'attr' => array(
                    'placeholder' => 'Código o Referencia'
                ),
                'required' => false
            ))
            ->add('tarejta', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'attr' => array(
                    'placeholder' => '# Tarjeta'
                ),
                'required' => false
            ))
            ->add('titular', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'attr' => array(
                    'placeholder' => 'Titular'
                ),
                'required' => false
            ))
            ->add('fechaInicio', DateTimeType::class, array(
                'widget' => 'single_text',
                'format' => 'dd-MM-yyyy',
                'required' => false,
                'attr' => array(
                    'class' => 'datepicker'
                )
            ))
            ->add('fechaFin', DateTimeType::class, array(
                'widget' => 'single_text',
                'format' => 'dd-MM-yyyy',
                'required' => false,
                'attr' => array(
                    'class' => 'datepicker'
                )
            ))
            ->add('agencia', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => TnAgencia::class,
                'query_builder' => function (EntityRepository $er) {
                    $query = $er->createQueryBuilder('d')
                        ->where("d.enabled = true");
                    return $query;
                },
                'multiple' => true,
                'required' => false
            ))
            ->add('nota', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'attr' => array(
                    'placeholder' => 'Nota'
                ),
                'required' => false
            ))
            ->add('agente', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => TnAgente::class,
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
