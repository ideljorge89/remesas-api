<?php

namespace App\Form;

use App\Entity\NmGrupoPagoAgente;
use App\Entity\NmMoneda;
use App\Entity\TnUser;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NmGrupoPagoAgenteType extends AbstractType
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->security = $this->container->get('security.authorization_checker');
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nombre', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.grupo_pago_agente.form.fields.nombre',
                'required' => true
            ))
            ->add('enabled', CheckboxType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.grupo_pago_agente.form.fields.enabled',
                'required' => false
            ))
            ->add('utilidad', NumberType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.grupo_pago_agente.form.fields.utilidad',
                'required' => false,
                'attr' => [
                    'class' => 'money',
                    'data-rule-minLength' => 1,
                    'data-rule-maxLength' => 5
                ]
            ))
            ->add('porcentaje', NumberType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.grupo_pago_agente.form.fields.porciento',
                'required' => false,
                'attr' => [
                    'class' => 'money',
                    'data-rule-minLength' => 1,
                    'data-rule-maxLength' => 5
                ]
            ))
            ->add('minimo', NumberType::class, array(
                'scale' => 2,
                'translation_domain' => 'AppBundle',
                'label' => 'backend.grupo_pago.form.fields.minimo',
                'required' => false,
                'attr' => [
                    'data-rule-number' => true,
                ]
            ))
            ->add('tipoUtilidad', ChoiceType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.grupo_pago.form.fields.tipo',
                'placeholder' => 'backend.comun.form.select_placeholder',
                'required' => false,
                'choices' => NmGrupoPagoAgente::getAllTipos()
            ))
            ->add('utilidadFija', NumberType::class, array(
                'scale' => 2,
                'translation_domain' => 'AppBundle',
                'label' => 'backend.grupo_pago.form.fields.utilidad_fija',
                'required' => false,
                'attr' => [
                    'data-rule-number' => true,
                ]
            ))
            ->add('moneda', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => NmMoneda::class,
                'query_builder' => function (EntityRepository $er) {
                    $query = $er->createQueryBuilder('m')
                        ->where("m.enabled = true");
                    return $query;
                },
                'choice_label' => function (NmMoneda $nmMoneda) {
                    return $nmMoneda->getSimbolo();
                },
                'label' => 'backend.grupo_pago.form.fields.moneda',
                'placeholder' => 'Seleccione una moneda',
                'required' => true
            ));

        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            $builder->add('usuario', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.emisor.form.fields.user',
                'class' => TnUser::class,
                'query_builder' => function (EntityRepository $er) {
                    $query = $er->createQueryBuilder('u')
                        ->where('u.roles LIKE :ag')->setParameter('ag', "%ROLE_AGENCIA%");
                    return $query;
                },
                'choice_label' => function (TnUser $tnUser) {
                    return $tnUser->getAgencia();
                },
                'required' => true,
                'placeholder' => 'backend.comun.form.select_placeholder'
            ));
        }

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($builder) {
                $form = $event->getForm();

                //Verificando si ya un fondo para esa moneda
                if (is_null($form->getData()->getPorcentaje()) && is_null($form->getData()->getUtilidad())) {
                    $form->get('porcentaje')->addError(new FormError('Debe especificar uno de los tipos de pago, utilidad o porciento.'));
                }

                $tipo = 0;
                if (!is_null($form->getData()->getPorcentaje()) && $form->getData()->getPorcentaje() != 0) {
                    $tipo++;
                }
                if (!is_null($form->getData()->getUtilidad()) && $form->getData()->getUtilidad() != 0) {
                    $tipo++;
                }
                if ($tipo > 1 || $tipo == 0) {
                    $form->get('porcentaje')->addError(new FormError('Debe especificar un solo tipo de pago, utilidad o porciento.'));
                }
            });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => NmGrupoPagoAgente::class,
        ]);
    }
}
