<?php

namespace App\Form;

use App\Entity\NmGrupoPagoAgente;
use App\Entity\NmGrupoPagoTransfAgente;
use App\Entity\TnAgencia;
use App\Entity\TnAgente;
use App\Form\Type\StatesType;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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

class TnAgenteType extends AbstractType
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
            ->add('nombre', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.agente.form.fields.nombre',
                'required' => true
            ))
            ->add('phone', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.agente.form.fields.telefono',
                'required' => false
            ))
            ->add('email', EmailType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.agente.form.fields.email',
                'required' => false
            ))
            ->add('enabled', CheckboxType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.agente.form.fields.enabled',
                'required' => false
            ))
            ->add('dir_line1', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.agente.form.fields.direccion1',
                'required' => true
            ))
            ->add('dir_line2', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.agente.form.fields.direccion2',
                'required' => false
            ))
            ->add('dir_line3', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.agente.form.fields.direccion3',
                'required' => false
            ))
            ->add('city', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.agente.form.fields.ciudad',
                'required' => true
            ))
            ->add('state', StatesType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.agente.form.fields.estado',
                'placeholder' => 'backend.comun.form.select_placeholder',
                'required' => true
            ))
            ->add('tipoAgente', ChoiceType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.agente.form.fields.tipo',
                'choices' => [
                    'Agente externo' => 'Externo',
                    'Agente interno' => 'Interno'
                ],
                'placeholder' => 'backend.comun.form.select_placeholder',
                'required' => true
            ))
            ->add('zip', NumberType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.agente.form.fields.zip',
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
                'label' => 'backend.agente.form.fields.pais',
                'placeholder' => 'backend.comun.form.select_placeholder',
                'required' => true,
                'data' => "US"
            ));
        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            $builder->add('agencia', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => TnAgencia::class,
                'query_builder' => function (EntityRepository $er) {
                    $query = $er->createQueryBuilder('a')
                        ->where("a.enabled = true");
                    return $query;
                },
                'label' => 'backend.agente.form.fields.agencia',
                'placeholder' => 'backend.comun.form.select_placeholder',
                'required' => false
            ));
            $builder->add('gruposPago', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => NmGrupoPagoAgente::class,
                'label' => 'backend.agencia.form.fields.grupo',
                'placeholder' => 'backend.comun.form.select_placeholder',
                'query_builder' => function (EntityRepository $er) {
                    $query = $er->createQueryBuilder('a')
                        ->where("a.enabled = true");
                    return $query;
                },
                'choice_label' => function (NmGrupoPagoAgente $grupoPago) {
                    return $grupoPago->getNombre() . " - " . ($grupoPago->getUtilidad() ? $grupoPago->getUtilidad() . " USD" : $grupoPago->getPorcentaje() . " %") . " - " . $grupoPago->getMoneda()->getSimbolo();
                },
                'group_by' => function (NmGrupoPagoAgente $nmGrupoPagoAgente) {
                    return $nmGrupoPagoAgente->getUsuario() ? $nmGrupoPagoAgente->getUsuario()->getUsername() : $this->user->getUserName();
                },
                'constraints' => [new Count(['min' => 1])],
                'multiple' => true,
                'required' => true
            ));
            $builder->add('gruposPagoTransferencias', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => NmGrupoPagoTransfAgente::class,
                'label' => 'backend.agencia.form.fields.grupo',
                'placeholder' => 'backend.comun.form.select_placeholder',
                'query_builder' => function (EntityRepository $er) {
                    $query = $er->createQueryBuilder('a')
                        ->where("a.enabled = true");
                    return $query;
                },
                'choice_label' => function (NmGrupoPagoTransfAgente $grupoPago) {
                    return $grupoPago->getNombre() . " - " . $grupoPago->getPorcentaje() . " %" . " - " . $grupoPago->getMoneda()->getSimbolo();
                },
                'group_by' => function (NmGrupoPagoTransfAgente $nmGrupoPagoAgente) {
                    return $nmGrupoPagoAgente->getUsuario() ? $nmGrupoPagoAgente->getUsuario()->getUsername() : $this->user->getUserName();
                },
                'constraints' => [new Count(['min' => 1])],
                'multiple' => true,
                'required' => true
            ));
        } else {
            $builder->add('gruposPago', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => NmGrupoPagoAgente::class,
                'label' => 'backend.agencia.form.fields.grupo',
                'placeholder' => 'backend.comun.form.select_placeholder',
                'query_builder' => function (EntityRepository $er) {
                    $query = $er->createQueryBuilder('a')
                        ->where("a.enabled = true")
                        ->andWhere('a.usuario = :user')
                        ->setParameter('user', $this->user);
                    return $query;
                },
                'choice_label' => function (NmGrupoPagoAgente $grupoPago) {
                    return $grupoPago->getNombre() . " - " . ($grupoPago->getUtilidad() ? $grupoPago->getUtilidad() . " USD" : $grupoPago->getPorcentaje() . " %") . " - " . $grupoPago->getMoneda()->getSimbolo();
                },
                'constraints' => [new Count(['min' => 1])],
                'multiple' => true,
                'required' => true
            ));
            $builder->add('gruposPagoTransferencias', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => NmGrupoPagoTransfAgente::class,
                'label' => 'backend.agencia.form.fields.grupo',
                'placeholder' => 'backend.comun.form.select_placeholder',
                'query_builder' => function (EntityRepository $er) {
                    $query = $er->createQueryBuilder('a')
                        ->where("a.enabled = true")
                        ->andWhere('a.usuario = :user')
                        ->setParameter('user', $this->user);
                    return $query;
                },
                'choice_label' => function (NmGrupoPagoTransfAgente $grupoPago) {
                    return $grupoPago->getNombre() . " - " . $grupoPago->getPorcentaje() . " %" . " - " . $grupoPago->getMoneda()->getSimbolo();
                },
                'group_by' => function (NmGrupoPagoTransfAgente $nmGrupoPagoAgente) {
                    return $nmGrupoPagoAgente->getUsuario() ? $nmGrupoPagoAgente->getUsuario()->getUsername() : $this->user->getUserName();
                },
                'constraints' => [new Count(['min' => 1])],
                'multiple' => true,
                'required' => true
            ));
        }

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
            'data_class' => TnAgente::class,
        ]);
    }
}
