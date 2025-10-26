<?php

namespace App\Form\Type;

use App\Entity\NmEstado;
use App\Entity\TnAgencia;
use App\Entity\TnApoderado;
use App\Entity\TnDistribuidor;
use App\Twig\AppExtension;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReporteAgenciaApoderadosType extends AbstractType
{

    private $container;
    private $entityManager;
    private $user;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->entityManager = $container->get('doctrine')->getManager();
    }

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
            ->add('agencia', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => TnAgencia::class,
                'label' => 'Agencias subordinadas',
                'query_builder' => function (EntityRepository $er) {
                    $agencias = [];
                    if (!is_null($this->user->getAgencia()) && !is_null($this->user->getAgencia()->getApoderado())) {
                        foreach ($this->user->getAgencia()->getApoderado()->getSubordinadas() as $agencia) {
                            $agencias[] = $agencia->getId();
                        }
                    }
                    $query = $er->createQueryBuilder('ag')
                        ->where("ag.enabled = true")
                        ->andWhere('ag.id IN (:ids)')
                        ->setParameter('ids', $agencias);
                    return $query;
                },
                'multiple' => true,
                'required' => false
            ))
            ->add('estado', ChoiceType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.factura.form.fields.estado',
                'placeholder' => 'backend.comun.form.select_placeholder',
                'required' => false,
                'choices' => NmEstado::getEstados(),
                'multiple' => true
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
