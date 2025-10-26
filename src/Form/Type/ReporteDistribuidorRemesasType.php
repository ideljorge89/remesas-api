<?php

namespace App\Form\Type;

use App\Entity\NmEstado;
use App\Entity\NmMunicipio;
use App\Twig\AppExtension;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReporteDistribuidorRemesasType extends AbstractType
{

    private $container;
    private $user;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @var AppExtension
     */
    private $twig_extension;


    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->user = $this->container->get('security.token_storage')->getToken()->getUser();

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
            ->add('zonas', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => NmMunicipio::class,
                'query_builder' => function (EntityRepository $er) {
                    $query = $er->createQueryBuilder('z')
                        ->join('z.jefes', 'distrubuidor')
                        ->where('distrubuidor.id = :dist')
                        ->setParameter('dist', $this->user->getDistribuidor());
                    return $query;
                },
                'label' => 'backend.distribuidor.form.fields.zonas',
                'placeholder' => 'backend.comun.form.select_placeholder',
                'multiple' => true,
                'required' => false
            ))
            ->add('estado', ChoiceType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.factura.form.fields.estado',
                'placeholder' => 'backend.comun.form.select_placeholder',
                'required' => false,
                'choices'=>NmEstado::getReportDistribuidor(),
                'multiple' => true
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
