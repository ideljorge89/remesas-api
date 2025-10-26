<?php

namespace App\Form;

use App\Entity\NmGrupoPago;
use App\Entity\NmGrupoPagoTransf;
use App\Entity\TnAgencia;
use App\Form\Type\StatesType;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;

class TnAgenciaType extends AbstractType
{
    private $container;
    private $entityManager;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->entityManager = $container->get('doctrine')->getManager();;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nombre', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.agencia.form.fields.nombre',
                'required' => true
            ))
            ->add('phone', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.agencia.form.fields.telefono',
                'required' => false
            ))
            ->add('email', EmailType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.agencia.form.fields.email',
                'required' => false
            ))
            ->add('enabled', CheckboxType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.agencia.form.fields.enabled',
                'required' => false
            ))
            ->add('retenida', CheckboxType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.agencia.form.fields.retenida',
                'required' => false
            ))
            ->add('dir_line1', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.agencia.form.fields.direccion1',
                'required' => true
            ))
            ->add('dir_line2', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.agencia.form.fields.direccion2',
                'required' => false
            ))
            ->add('dir_line3', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.agencia.form.fields.direccion3',
                'required' => false
            ))
            ->add('city', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.agencia.form.fields.ciudad',
                'required' => true
            ))
            ->add('state', StatesType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.agencia.form.fields.estado',
                'placeholder' => 'backend.comun.form.select_placeholder',
                'required' => true
            ))
            ->add('zip', NumberType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.agencia.form.fields.zip',
                'required' => true,
                'attr' => [
                    'class' => 'money',
                    'data-rule-number' => true,
                    'data-rule-minLength' => 5,
                    'data-rule-maxLength' => 5
                ]
            ))
            ->add('country', CountryType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.agencia.form.fields.pais',
                'placeholder' => 'backend.comun.form.select_placeholder',
                'required' => true,
                'data' => "US"
            ))
            ->add('gruposPago', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => NmGrupoPago::class,
                'label' => 'backend.agencia.form.fields.grupo',
                'placeholder' => 'backend.comun.form.select_placeholder',
                'choice_label' => function (NmGrupoPago $grupoPago) {
                    return $grupoPago->getNombre() . " - (" . $grupoPago->getPorcentaje() . " %) - " . $grupoPago->getMoneda()->getSimbolo();
                },
                'query_builder' => function (EntityRepository $er) {
                    $query = $er->createQueryBuilder('gp')
                        ->where("gp.enabled = true");
                    return $query;
                },
                'constraints' => [new Count(['min' => 1])],
                'multiple' => true,
                'required' => true
            ))
            ->add('gruposPagoTransferencias', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => NmGrupoPagoTransf::class,
                'label' => 'backend.agencia.form.fields.grupo_transf',
                'placeholder' => 'backend.comun.form.select_placeholder',
                'choice_label' => function (NmGrupoPagoTransf $grupoPago) {
                    return $grupoPago->getNombre() . " - (" . $grupoPago->getPorcentaje() . " %) - " . $grupoPago->getMoneda()->getSimbolo();
                },
                'query_builder' => function (EntityRepository $er) {
                    $query = $er->createQueryBuilder('gp')
                        ->where("gp.enabled = true");
                    return $query;
                },
                'constraints' => [new Count(['min' => 1])],
                'multiple' => true,
                'required' => true
            ));
//            ->add('grupoPago', EntityType::class, array(
//                'translation_domain' => 'AppBundle',
//                'class' => NmGrupoPago::class,
//                'query_builder' => function (EntityRepository $er) {
//                    $query = $er->createQueryBuilder('a')
//                        ->where("a.enabled = true");
//                    return $query;
//                },
//                'label' => 'backend.agencia.form.fields.grupo',
//                'required' => true
//            ));
        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($builder) {
                $form = $event->getForm();
                //Verificando que no tenga grupos de para la misma moneda, 1 solo grupo por moneda.
                $checkMonedas = [];
                foreach ($form->getData()->getGruposPago() as $grupoPago) {
                    if (in_array($grupoPago->getMoneda()->getSimbolo(), $checkMonedas)) {
                        $form->get('gruposPago')->addError(new FormError('Solo puede asociar un Grupo de Pago para 1 misma moneda, revise por favor.'));
                    } else {
                        $checkMonedas[] = $grupoPago->getMoneda()->getSimbolo();
                    }
                }
                $checkMonedasT = [];
                foreach ($form->getData()->getGruposPagoTransferencias() as $gruposPagoTransferencia) {
                    if (in_array($gruposPagoTransferencia->getMoneda()->getSimbolo(), $checkMonedasT)) {
                        $form->get('gruposPagoTransferencias')->addError(new FormError('Solo puede asociar un Grupo de Pago para 1 misma moneda, revise por favor.'));
                    } else {
                        $checkMonedasT[] = $gruposPagoTransferencia->getMoneda()->getSimbolo();
                    }
                }
            });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => TnAgencia::class,
        ]);
    }
}
