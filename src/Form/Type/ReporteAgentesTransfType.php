<?php

namespace App\Form\Type;

use App\Entity\NmEstado;
use App\Entity\NmEstadoTransferencia;
use App\Entity\TnAgencia;
use App\Entity\TnAgente;
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

class ReporteAgentesTransfType extends AbstractType
{
    private $container;
    private $security;
    private $user;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->security = $this->container->get('security.authorization_checker');
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
            ->add('agente', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => TnAgente::class,
                'label' => 'backend.agente.form.fields.agente',
                'query_builder' => function (EntityRepository $er) {
                    if ($this->security->isGranted('ROLE_AGENCIA')) {
                        $query = $er->createQueryBuilder('a')
                            ->where("a.enabled = true")
                            ->andWhere('a.agencia = :ag')
                            ->setParameter('ag', $this->user->getAgencia());
                    } else {
                        $query = $er->createQueryBuilder('a')
                            ->where("a.enabled = true");
                    }
                    return $query;
                },
                'group_by' => function (TnAgente $tnAgente) {
                    return $tnAgente->getAgencia() ? $tnAgente->getAgencia()->getNombre() : $this->user->getUserName();
                },
                'multiple' => true,
                'required' => false
            ))
            ->add('estado', ChoiceType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.factura.form.fields.estado',
                'placeholder' => 'backend.comun.form.select_placeholder',
                'required' => false,
                'choices' => NmEstadoTransferencia::getEstados(),
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
