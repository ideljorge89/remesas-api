<?php

namespace App\Form\Type;

use App\Entity\NmEstado;
use App\Entity\NmMunicipio;
use App\Entity\NmProvincia;
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

class RemesasSerachType extends AbstractType
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
            ->add('direccion', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'attr' => array(
                    'placeholder' => 'Dirección'
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
                'choices' => [
                    'Pendiente' => 'pendiente',
                    'Entregada' => 'entregada',
                    'Cancelada' => 'cancelada'
                ],
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
            ->add('municipio', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => NmMunicipio::class,
                'query_builder' => function (EntityRepository $er) {
                    $query = $er->createQueryBuilder('m')
                        ->join('m.jefes', 'jefeZona')
                        ->where('jefeZona.id = :dist')
                        ->setParameter('dist', $this->user->getDistribuidor());
                    return $query;
                },
                'multiple' => true,
                'required' => false
            ))
            ->add('provincia', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => NmProvincia::class,
                'query_builder' => function (EntityRepository $er) {
                    $query = $er->createQueryBuilder('p')
                        ->join('p.municipios', 'm')
                        ->join('m.jefes', 'jefeZona')
                        ->where('jefeZona.id = :dist')
                        ->setParameter('dist', $this->user->getDistribuidor());
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
