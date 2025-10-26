<?php

namespace App\Form;

use App\Entity\NmMunicipio;
use App\Entity\NmProvincia;
use App\Entity\TnAgente;
use App\Entity\TnDestinatario;
use App\Entity\TnEmisor;
use App\Entity\TnUser;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TnDestinatarioType extends AbstractType
{
    private $provincia;

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
            ->add('nombre', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.destinatario.form.fields.nombre',
                'required' => true
            ))
            ->add('apellidos', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.destinatario.form.fields.apellidos',
                'required' => true
            ))
            ->add('alias', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.destinatario.form.fields.alias',
                'required' => false
            ))
            ->add('ci', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.destinatario.form.fields.ci',
                'required' => false,
                'empty_data' => ""
            ))
            ->add('phone', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.destinatario.form.fields.telefono',
                'required' => true
            ))
            ->add('enabled', CheckboxType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.destinatario.form.fields.enabled',
                'required' => false
            ))
            ->add('direccion', TextareaType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.destinatario.form.fields.direccion',
                'required' => true
            ))
//            ->add('direccion1', TextType::class, array(
//                'translation_domain' => 'AppBundle',
//                'label' => 'backend.destinatario.form.fields.direccion1',
//                'required' => false
//            ))
            ->add('municipio', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => NmMunicipio::class,
                'label' => 'backend.destinatario.form.fields.municipio',
                'placeholder' => 'backend.comun.form.select_placeholder',
                'required' => true,
            ));
        if ($this->security->isGranted('ROLE_AGENCIA') || $this->security->isGranted('ROLE_AGENTE')) {
            $builder->add('emisor', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => TnEmisor::class,
                'label' => 'backend.destinatario.form.fields.emisor',
                'query_builder' => function (EntityRepository $er) {
                    if ($this->security->isGranted('ROLE_AGENCIA')) {
                        $usuariosIds = $this->facturaManager->getUsersAgentesByAgencia($this->user->getAgencia());
                        $query = $er->createQueryBuilder('e')
                            ->where("e.enabled = true")
                            ->andWhere("e.usuario = :ua or e.usuario IN (:users)")
                            ->setParameter('ua', $this->user)
                            ->setParameter('users', $usuariosIds);
                    } else {
                        $tnAgente = $this->user->getAgente();
                        if ($tnAgente->getTipoAgente() == TnAgente::TIPO_EXTERNO) {
                            $query = $er->createQueryBuilder('e')
                                ->where("e.enabled = true")
                                ->andWhere("e.usuario = :ua")
                                ->setParameter('ua', $this->user);
                        } else {
                            $userAgencia = $tnAgente->getAgencia()->getUsuario()->getId();
                            $query = $er->createQueryBuilder('e')
                                ->where("e.enabled = true")
                                ->andWhere("e.usuario = :ua or e.usuario = :uagc")
                                ->setParameter('ua', $this->user)
                                ->setParameter('uagc', $userAgencia);
                        }
                    }
                    return $query;
                },
                'group_by' => function (TnEmisor $tnEmisor) {
                    if ($tnEmisor->getUsuario() != null && $tnEmisor->getUsuario()->getAgencia() != null) {
                        return $tnEmisor->getUsuario()->getAgencia()->getNombre();
                    } elseif ($tnEmisor->getUsuario() != null && $tnEmisor->getUsuario()->getAgente()) {
                        return $tnEmisor->getUsuario()->getAgente()->getNombre();
                    } else {
                        return $this->user->getUserName();
                    }
                },
                'choice_label' => function (TnEmisor $tnEmisor) {
                    return $tnEmisor->getNombre() . " " . $tnEmisor->getApellidos() . " " . $tnEmisor->getPhone();
                },
                'placeholder' => 'Seleccione el emisor',
                'required' => true
            ));
        } else {
            $builder->add('emisor', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => TnEmisor::class,
                'label' => 'backend.destinatario.form.fields.emisor',
                'query_builder' => function (EntityRepository $er) {
                    $query = $er->createQueryBuilder('e')
                        ->where("e.enabled = true");
                    return $query;
                },
                'group_by' => function (TnEmisor $tnEmisor) {
                    if ($tnEmisor->getUsuario() != null && $tnEmisor->getUsuario()->getAgencia() != null) {
                        return $tnEmisor->getUsuario()->getAgencia()->getNombre();
                    } elseif ($tnEmisor->getUsuario() != null && $tnEmisor->getUsuario()->getAgente()) {
                        return $tnEmisor->getUsuario()->getAgente()->getNombre();
                    } else {
                        return $this->user->getUserName();
                    }
                },
                'choice_label' => function (TnEmisor $tnEmisor) {
                    return $tnEmisor->getNombre() . " " . $tnEmisor->getApellidos() . " " . $tnEmisor->getPhone();
                },
                'placeholder' => 'Seleccione el emisor',
                'required' => true
            ));
        }
        $builder->add('provincia', EntityType::class, array(
            'translation_domain' => 'AppBundle',
            'class' => NmProvincia::class,
            'label' => 'backend.destinatario.form.fields.provincia',
            'placeholder' => 'backend.comun.form.select_placeholder',
            'required' => true
        ))
            ->add('country', HiddenType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.destinatario.form.fields.pais',
                'required' => true,
                'data' => "CU"
            ))
            ->add('nombreAlternativo', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.destinatario.form.fields.nombre_alt',
                'required' => false
            ))
            ->add('apellidosAlternativo', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.destinatario.form.fields.apellidos_alt',
                'required' => false
            ))
            ->add('ciAlternativo', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.destinatario.form.fields.ci_alt',
                'required' => false
            ))
            ->add('phone_alternativo', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.destinatario.form.fields.telefono_alt',
                'required' => false
            ))
            ->add('handler', HiddenType::class, array(
                'mapped' => false
            ));;

        if ($this->security->isGranted('ROLE_AGENCIA') || $this->security->isGranted('ROLE_SUPER_ADMIN')) {
            $builder->add('usuario', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.emisor.form.fields.user',
                'class' => TnUser::class,
                'query_builder' => function (EntityRepository $er) {
                    $query = $er->createQueryBuilder('u')
                        ->where('u.roles LIKE :ag or u.roles LIKE :agt')->setParameter('ag', "%ROLE_AGENCIA%")->setParameter('agt', '%ROLE_AGENTE%');
                    if ($this->security->isGranted('ROLE_AGENCIA')) {
                        $usuariosIds = $this->facturaManager->getUsersAgentesByAgencia($this->user->getAgencia());

                        $query->andWhere("u.id IN (:users)")
                            ->setParameter('users', $usuariosIds);
                    }
                    return $query;
                },
                'choice_label' => function (TnUser $tnUser) {
                    return $tnUser->getAgencia() ? $tnUser->getAgencia() : $tnUser->getAgente();
                },
                'required' => true,
                'placeholder' => 'backend.comun.form.select_placeholder'
            ));
        }

        $builder->addEventListener(FormEvents::POST_SET_DATA, function ($event) {
            if ($event->getData() != null) {
                $form = $event->getForm();
                $this->provincia = $event->getData()->getProvincia();

                $form->add('municipio', null, array(
                    'translation_domain' => 'AppBundle',
                    'class' => NmMunicipio::class,
                    'label' => 'backend.destinatario.form.fields.municipio',
                    'required' => true,
                    'query_builder' => function (EntityRepository $er) {
                        $query = $er->createQueryBuilder('o')
                            ->where("o.provincia = :prov")
                            ->setParameter('prov', $this->provincia);
                        return $query;
                    }
                ));
            }
        });
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($builder) {
                $form = $event->getForm();

                $form->add('municipio', EntityType::class, array(
                    'translation_domain' => 'AppBundle',
                    'class' => NmMunicipio::class,
                    'label' => 'backend.destinatario.form.fields.municipio',
                    'placeholder' => 'backend.comun.form.select_placeholder',
                    'required' => true,
                ));
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => TnDestinatario::class,
        ]);
    }
}
