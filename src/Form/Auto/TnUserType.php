<?php

namespace App\Form\Auto;

use App\Entity\TnAgencia;
use App\Entity\TnAgente;
use App\Entity\TnDistribuidor;
use App\Entity\TnUser;
use Doctrine\ORM\EntityRepository;
use Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormError;

class TnUserType extends AbstractType
{
    private $container;
    private $webRoot;
    private $translator;
    private $security;
    private $user;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->security = $this->container->get('security.authorization_checker');
        $kernel_root = $this->container->getParameter('kernel.project_dir');
        $web_dir = $this->container->getParameter('web_dir');
        $this->webRoot = realpath($kernel_root . $web_dir);
        $this->translator = $this->container->get('translator');
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->user = $this->container->get('security.token_storage')->getToken()->getUser();

        $builder
            ->add('email', EmailType::class, array('translation_domain' => 'FOSUserBundle',
                'attr' => array('placeholder' => 'form.email'),
                'required' => true,
                'label' => 'form.email',
                'trim' => true
            ))
            ->add('username', TextType::class, array(
                'label' => 'form.username', 'translation_domain' => 'FOSUserBundle',
                'attr' => array('placeholder' => 'form.username'),
                'required' => true,
                'trim' => true
            ))
            ->add('filePath', TextType::class, array(
                'required' => false
            ))
            ->add('plainPassword', RepeatedType::class, array(
                'type' => PasswordType::class,
                'required' => false,
                'label' => 'Password', 'translation_domain' => 'FOSUserBundle',
                //'invalid_message' => 'fos_user.password.invalid',
                'first_options' => array('label' => 'form.password',
                    'required' => false,
                    'attr' => array('placeholder' => 'form.password', 'title' => 'form.password.title')
                ),
                'second_options' => array('required' => false, 'invalid_message' => 'fos_user.password.mismatch', 'label' => 'form.password_confirmation', 'attr' => array('placeholder' => 'form.password_confirmation')),
            ));

        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            $builder->add('user_roles', RoleType::class, array(
                'required' => true,
                'mapped' => false))
                ->add('agencia', EntityType::class, array(
                    'translation_domain' => 'AppBundle',
                    'class' => TnAgencia::class,
                    'query_builder' => function (EntityRepository $er) {
                        $query = $er->createQueryBuilder('a')
                            ->leftJoin('a.usuario', 'usuario')
                            ->where("a.enabled = true")
                            ->andWhere('usuario.id is NULL');
                        return $query;
                    },
                    'label' => 'backend.agente.form.fields.agencia',
                    'placeholder' => 'Seleccione una agencia',
                    'required' => true
                ));

            $builder->add('agente', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => TnAgente::class,
                'query_builder' => function (EntityRepository $er) {
                    $query = $er->createQueryBuilder('a')
                        ->leftJoin("a.usuario", "usuario")
                        ->where("a.enabled = true")
                        ->andWhere('usuario.id is NULL');
                    return $query;
                },
                'label' => 'backend.agente.form.fields.agente',
                'placeholder' => 'Seleccione un agente',
                'required' => true
            ));

            $builder->add('agencia', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => TnAgencia::class,
                'query_builder' => function (EntityRepository $er) {
                    $query = $er->createQueryBuilder('a')
                        ->leftJoin("a.usuario", "usuario")
                        ->where("a.enabled = true")
                        ->andWhere('usuario.id is NULL');
                    return $query;
                },
                'label' => 'backend.agente.form.fields.agencia',
                'placeholder' => 'Seleccione una agencia',
                'required' => true
            ));

            $builder->add('distribuidor', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => TnDistribuidor::class,
                'query_builder' => function (EntityRepository $er) {
                    $query = $er->createQueryBuilder('d')
                        ->leftJoin("d.usuario", "usuario")
                        ->where("d.enabled = true")
                        ->andWhere('usuario.id is NULL');
                    return $query;
                },
                'label' => 'backend.factura.table.distribuidor',
                'placeholder' => 'Seleccione un distrbuidor',
                'required' => true
            ));
        } else {
            $builder->add('agente', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => TnAgente::class,
                'query_builder' => function (EntityRepository $er) {
                    $query = $er->createQueryBuilder('a')
                        ->leftJoin("a.usuario", "usuario")
                        ->where("a.enabled = true")
                        ->andWhere("a.agencia = :ag")
                        ->andWhere('usuario.id is NULL')
                        ->setParameter('ag', $this->user->getAgencia());
                    return $query;
                },
                'label' => 'backend.agente.form.fields.agente',
                'placeholder' => 'Seleccione un agente',
                'required' => true
            ));
        }


        $factory = $builder->getFormFactory();


        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            function (FormEvent $event) use ($factory, $builder) {
                $data = $event->getData();
                $form = $event->getForm();

                if ($data->getId() != null) {
                    if ($this->security->isGranted('ROLE_AGENCIA')) {
                        if ($data->getAgente() != null) {
                            $form->add('agente', HiddenType::class, ["property_path" => "agente.id"]);

                        }
                    } else {
                        if ($data->getAgencia() != null) {
                            $form->add('agencia', HiddenType::class, ["property_path" => "agencia.id"]);
                            $form->remove("agente");
                            $form->remove("distribuidor");
                        }
                        if ($data->getAgente() != null) {
                            $form->add('agente', HiddenType::class, ["property_path" => "agente.id"]);
                            $form->remove("agencia");
                            $form->remove("distribuidor");
                        }
                        if ($data->getDistribuidor() != null) {
                            $form->add('distribuidor', HiddenType::class, ["property_path" => "distribuidor.id"]);
                            $form->remove("agente");
                            $form->remove("agencia");
                        }

                        $form->remove("user_roles");
                    }
                }
            });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (PreSubmitEvent $event) {
            $form = $event->getForm();
            $data = $form->getData();
            if ($data->getId() != null) {
                if ($this->security->isGranted('ROLE_AGENCIA')) {
                    $form->remove("agente");
                    $form->add('agente', EntityType::class, array(
                        'translation_domain' => 'AppBundle',
                        'class' => TnAgente::class,
                        'query_builder' => function (EntityRepository $er) {
                            $query = $er->createQueryBuilder('a')
                                ->leftJoin("a.usuario", "usuario")
                                ->where("a.enabled = true")
                                ->andWhere("a.agencia = :ag")
                                ->andWhere('usuario.id is NULL')
                                ->setParameter('ag', $this->user->getAgencia());
                            return $query;
                        },
                        'label' => 'backend.agente.form.fields.agente',
                        'placeholder' => 'Seleccione un agente',
                        'required' => true
                    ));
                } else {
                    if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
                        $form->remove("agente");
                        $form->add('agente', EntityType::class, array(
                            'translation_domain' => 'AppBundle',
                            'class' => TnAgente::class,
                            'query_builder' => function (EntityRepository $er) {
                                $query = $er->createQueryBuilder('a')
                                    ->leftJoin("a.usuario", "usuario")
                                    ->where("a.enabled = true");
                                return $query;
                            },
                            'label' => 'backend.agente.form.fields.agente',
                            'placeholder' => 'Seleccione un agente',
                            'required' => true
                        ));
                        $form->remove("agencia");
                        $form->add('agencia', EntityType::class, array(
                            'translation_domain' => 'AppBundle',
                            'class' => TnAgencia::class,
                            'query_builder' => function (EntityRepository $er) {
                                $query = $er->createQueryBuilder('a')
                                    ->leftJoin("a.usuario", "usuario")
                                    ->where("a.enabled = true");
                                return $query;
                            },
                            'label' => 'backend.agente.form.fields.agencia',
                            'placeholder' => 'Seleccione una agencia',
                            'required' => true
                        ));
                        $form->remove("distribuidor");
                        $form->add('distribuidor', EntityType::class, array(
                            'translation_domain' => 'AppBundle',
                            'class' => TnDistribuidor::class,
                            'query_builder' => function (EntityRepository $er) {
                                $query = $er->createQueryBuilder('d')
                                    ->leftJoin("d.usuario", "usuario")
                                    ->where("d.enabled = true");
                                return $query;
                            },
                            'label' => 'backend.factura.table.distribuidor',
                            'placeholder' => 'Seleccione un distrbuidor',
                            'required' => true
                        ));
                    }
                }
            }
        });

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                $data = $event->getData();
                $form = $event->getForm();

                if ($this->security->isGranted('ROLE_AGENCIA')) {
                    if ($data->getAgente() == null) {
                        $form->get('agente')->addError(new FormError('Debe seleccionar un agente para crear el usuario.'));
                    }
                } else {
                    if ($data->getId() == null) {
                        if ($form->get('user_roles')->getData() == "ROLE_AGENCIA") {
                            if ($data->getAgencia() == null) {
                                $form->get('agencia')->addError(new FormError('Debe seleccionar una agencia para el Rol Agencia.'));
                            }
                        } elseif ($form->get('user_roles')->getData() == "ROLE_AGENTE") {
                            if ($data->getAgente() == null) {
                                $form->get('agente')->addError(new FormError('Debe seleccionar un agente para el Rol Agente.'));
                            }
                        } else {
                            if ($data->getDistribuidor() == null) {
                                $form->get('distribuidor')->addError(new FormError('Debe seleccionar un distribuidor para el Rol Distribuidor.'));
                            }
                        }
                    }
                }

                if ($data instanceof TnUser) {

                    if (!$data->getId()) {
                        $pass = $form->get('plainPassword')->getData();

                        if ($pass != "" && !preg_match('/^(?=.*\d)(?=.*[a-z])(?=.*[^\w])(?=.*[A-Z]).{6,20}$/', $pass)) {
                            $tranlator = $this->container->get('translator');

                            $form->get('plainPassword')->get('first')->addError(new FormError(
                                $tranlator->trans('form.password.invalid', array(), 'FOSUserBundle')
                            ));
                        }
                    } else {
                        $pass = $form->get('plainPassword')->getData();
                        if ($pass != null && $pass != null) {
                            $data->setPassword($pass);
                        }
                    }

                    $dir = $this->webRoot . '/' . $data->getFilePath();

                    /* if($data->getId()===null){*/
                    if ($data->getFilePath() != '' && file_exists($dir)) {
                        $b64 = $this->container->get('app.twig.extension.base_64');
                        $default = $this->webRoot . '/assets/backend/img/profile_small.png';
                        $image = $b64->toBase64Filter($dir, $default);
                        $data->setAvatar($image);
                    }

                }
            });
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'App\Entity\TnUser',
            'validation_groups' => array('Profile'),
            'cascade_validation' => true
        ));
    }

    public function getName()
    {
        return 'tn_user_type';
    }
}
