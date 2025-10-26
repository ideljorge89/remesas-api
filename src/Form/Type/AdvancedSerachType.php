<?php

namespace App\Form\Type;

use App\Entity\NmEstado;
use App\Entity\NmMoneda;
use App\Entity\TnAgencia;
use App\Entity\TnAgente;
use App\Entity\TnDistribuidor;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdvancedSerachType extends AbstractType
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
            ->add('orden', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'attr' => array(
                    'placeholder' => 'Nro. Factura'
                ),
                'required' => false
            ))
            ->add('emisor', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'attr' => array(
                    'placeholder' => 'Emisor'
                ),
                'required' => false
            ))
            ->add('destinatario', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'attr' => array(
                    'placeholder' => 'Destinatario'
                ),
                'required' => false
            ))
            ->add('estado', ChoiceType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.factura.form.fields.estado',
                'placeholder' => 'backend.comun.form.select_placeholder',
                'required' => false,
                'choices' => NmEstado::getEstados(),
                'multiple' => true
            ))
            ->add('fechaInicio', DateTimeType::class, array(
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'required' => false,
                'attr' => array(
                    'class' => 'datepicker'
                )
            ))
            ->add('fechaFin', DateTimeType::class, array(
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'required' => false,
                'attr' => array(
                    'class' => 'datepicker'
                )
            ))
            ->add('moneda', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => NmMoneda::class,
                'query_builder' => function (EntityRepository $er) {
                    $query = $er->createQueryBuilder('m')
                        ->where("m.enabled = true")
                        ->andWhere("m.aviable = true");
                    return $query;
                },
                'multiple' => true,
                'required' => false
            ));

        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            $builder
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
                ))
                ->add('distribuidor', EntityType::class, array(
                    'translation_domain' => 'AppBundle',
                    'class' => TnDistribuidor::class,
                    'query_builder' => function (EntityRepository $er) {
                        $query = $er->createQueryBuilder('d')
                            ->where("d.enabled = true");
                        return $query;
                    },
                    'multiple' => true,
                    'required' => false
                ));
        } elseif ($this->security->isGranted('ROLE_AGENCIA')) {
            $builder
                ->add('agente', EntityType::class, array(
                    'translation_domain' => 'AppBundle',
                    'class' => TnAgente::class,
                    'query_builder' => function (EntityRepository $er) {
                        $query = $er->createQueryBuilder('a')
                            ->join('a.usuario', 'usuario')
                            ->where("a.enabled = true")
                            ->andWhere('a.agencia = :ag')
                            ->setParameter('ag', $this->user->getAgencia());
                        return $query;
                    },
                    'multiple' => true,
                    'required' => false
                ));
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
