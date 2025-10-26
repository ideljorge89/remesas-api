<?php

namespace App\Form\Type;

use App\Entity\NmEstado;
use App\Entity\TnFactura;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;

class FacturaAprobarType extends AbstractType
{
    private $container;
    private $security;
    private $user;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->security = $this->container->get('security.authorization_checker');
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->user = $this->container->get('security.token_storage')->getToken()->getUser();

        $builder
            ->add('facturas', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => TnFactura::class,
                'query_builder' => function (EntityRepository $er) {
                    $query = $er->createQueryBuilder('f')
                        ->join('f.estado', 'estado')
                        ->where("estado.codigo = :f_est")
                        ->andWhere('f.retenida is NULL or f.retenida = false')
                        ->addOrderBy('f.no_factura', 'ASC')
                        ->setParameter('f_est', NmEstado::ESTADO_PENDIENTE);
                    return $query;
                },
                'constraints' => [new Count(['min' => 1])],
                'label' => 'backend.factura.aprove.title',
                'placeholder' => 'backend.comun.form.select_placeholder',
                'multiple' => true,
                'expanded' => true,
                'required' => true
            ));

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                $form = $event->getForm();
                if (count($form->getData()['facturas']) == 0) {
                    $form->get('facturas')->addError(new FormError('Para aprobar debe seleccionar al menos 1 factura, revise.'));
                }
            });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
