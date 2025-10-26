<?php

namespace App\Form;

use App\Entity\NmEstado;
use App\Entity\NmGrupoPago;
use App\Entity\NmGrupoPagoAgente;
use App\Entity\TnAgencia;
use App\Entity\TnAgente;
use App\Entity\TnEmisor;
use App\Entity\TnFactura;
use App\Entity\TnSaldoAgencia;
use App\Entity\TnSaldoAgente;
use App\Form\Type\TnRemesaType;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TnFacturaType extends AbstractType
{
    private $container;
    private $facturaManager;
    private $entityManager;
    private $webRoot;
    private $translator;
    private $security;
    private $user;

    private $factura = null;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->entityManager = $container->get('doctrine')->getManager();
        $this->facturaManager = $container->get('factura_manager');
        $this->security = $this->container->get('security.authorization_checker');
        $kernel_root = $this->container->getParameter('kernel.project_dir');
        $web_dir = $this->container->getParameter('web_dir');
        $this->webRoot = realpath($kernel_root . $web_dir);
        $this->translator = $this->container->get('translator');
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $t = $this->container->get('translator');
        $new_choiceEmisor = new ChoiceView(array(), 'new', $t->trans('backend.factura.form.fields.add_new', array(), 'AppBundle'), array(
            'data-target' => $this->container->get('router')->generate('tn_emisor_new', array('modal' => ''))
        )); // <- new option
        array_unshift($view->children['emisor']->vars['choices'], $new_choiceEmisor);

    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->user = $this->container->get('security.token_storage')->getToken()->getUser();

        if (!is_null($builder->getForm()->getData()->getId())) {
            $this->factura = $builder->getForm()->getData()->getId();
        }
        if ($this->security->isGranted('ROLE_AGENCIA') || $this->security->isGranted('ROLE_AGENTE')) {
            $builder
                ->add('emisor', EntityType::class, array(
                    'translation_domain' => 'AppBundle',
                    'label' => 'backend.factura.form.fields.emisor',
                    'class' => TnEmisor::class,
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
                        return $tnEmisor->getNombre() . " " . $tnEmisor->getApellidos() . " - " . $tnEmisor->getPhone();
                    },
                    'required' => true,
                    'placeholder' => 'backend.comun.form.select_placeholder'
                ));
        } else {
            $builder
                ->add('emisor', EntityType::class, array(
                    'translation_domain' => 'AppBundle',
                    'label' => 'backend.factura.form.fields.emisor',
                    'class' => TnEmisor::class,
                    'query_builder' => function (EntityRepository $er) {
                        if ($this->factura != null) {
                            $factura = $this->entityManager->getRepository(TnFactura::class)->find($this->factura);
                            if ($factura != null && $factura->getAgente() != null && $factura->getAgente()->getTipoAgente() == TnAgente::TIPO_EXTERNO) {
                                $query = $er->createQueryBuilder('e')
                                    ->where("e.enabled = true")
                                    ->andWhere("e.usuario = :ua")
                                    ->setParameter('ua', $factura->getAgente()->getUsuario());
                            } elseif ($factura != null && $factura->getAgencia() != null) {
                                if ($factura->getAgencia()->getUsuario() != null) {
                                    $dql = "SELECT us.id FROM App:TnAgente ag JOIN ag.usuario us WHERE ag.agencia = :agcs";
                                    $arrayId = $this->entityManager->createQuery($dql)->setParameter('agcs', $factura->getAgencia())->getResult();
                                    $ids = [];
                                    foreach ($arrayId as $item) {
                                        $ids[] = $item['id'];
                                    }
                                    $query = $er->createQueryBuilder('em')
                                        ->join('em.usuario', 'usuario')
                                        ->where('em.enabled = true')
                                        ->andWhere('usuario.id IN (:users) or usuario.id = :useragc')
                                        ->orderBy('em.created', 'DESC')
                                        ->setParameter('users', $ids)
                                        ->setParameter('useragc', $factura->getAgencia()->getUsuario()->getId());
                                } else {
                                    $query = $er->createQueryBuilder('e')
                                        ->where("e.enabled = true");
                                }
                            } else {
                                $query = $er->createQueryBuilder('e')
                                    ->where("e.enabled = true");
                            }
                            return $query;
                        } else {
                            $query = $er->createQueryBuilder('e')
                                ->where("e.enabled = true");
                            return $query;
                        }
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
                        return $tnEmisor->getNombre() . " " . $tnEmisor->getApellidos() . " - " . $tnEmisor->getPhone();
                    },
                    'required' => true,
                    'placeholder' => 'backend.comun.form.select_placeholder'
                ));
        }
//            ->add('agente')
        $builder->add('remesas', CollectionType::class, array(
            'translation_domain' => 'AppBundle',
            'required' => true,
            'entry_type' => TnRemesaType::class,
            'entry_options' => ['attr_translation_parameters' => ['factura' => $this->factura]],
            'prototype' => true,
            'prototype_name' => 'factura_remesa__name__',
            'allow_add' => true,
            'allow_delete' => true,
            'error_bubbling' => true,
            'by_reference' => false
        ))->add('btnEmisor', CheckboxType::class, array(
            'required' => true
        ));

        if ($this->user->getAuth()) {
            $builder->add('auth', CheckboxType::class, array(
                'required' => true
            ));
        }


        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            $builder->add('estado', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.factura.form.fields.estado',
                'class' => NmEstado::class,
                'required' => true
            ));

            $builder->add('agente', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => TnAgente::class,
                'query_builder' => function (EntityRepository $er) {
                    $query = $er->createQueryBuilder('a')
                        ->join('a.usuario', 'usuario')
                        ->where("a.enabled = true")
                        ->andWhere('usuario.porcentaje IS NOT NULL and usuario.porcentaje <> 0');
                    return $query;
                },
                'choice_attr' => function (TnAgente $tnAgente) {
                    return [
                        'data-type' => 'porciento',
                        'data-porciento' => $tnAgente->getUsuario()->getPorcentaje(),
                        'data-tipoagente' => $tnAgente->getTipoAgente(),
                        'data-agencia' => $tnAgente->getAgencia() ? $tnAgente->getAgencia()->getId() : ''
                    ];
                },
                'choice_label' => function (TnAgente $tnAgente) {
                    return $tnAgente . " - " . $tnAgente->getUsuario()->getPorcentaje() . " %";
                },
                'group_by' => function (TnAgente $tnAgente) {
                    return $tnAgente->getAgencia() ? ($tnAgente->getAgencia()->getUsuario() ? $tnAgente->getAgencia()->getUsuario()->getUsername() : $tnAgente->getAgencia()) : $this->user->getUsername();
                },
                'label' => 'backend.agente.form.fields.agente',
                'placeholder' => 'backend.comun.form.select_placeholder',
                'required' => false
            ));

            $builder->add('agencia', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => TnAgencia::class,
                'query_builder' => function (EntityRepository $er) {
                    $query = $er->createQueryBuilder('a')
                        ->join('a.usuario', 'usuario')
                        ->where("a.enabled = true")
                        ->andWhere('usuario.porcentaje IS NOT NULL and usuario.porcentaje <> 0');
                    return $query;
                },
                'choice_attr' => function (TnAgencia $tnAgencia) {
                    return [
                        'data-type' => 'porciento',
                        'data-porciento' => $tnAgencia->getUsuario()->getPorcentaje(),
                        'data-unlimited' => $tnAgencia->getUnlimited()
                    ];
                },
                'choice_label' => function (TnAgencia $tnAgencia) {
                    return $tnAgencia . " - " . $tnAgencia->getUsuario()->getPorcentaje() . " %";
                },
                'label' => 'backend.agente.form.fields.agencia',
                'placeholder' => 'backend.comun.form.select_placeholder',
                'required' => false
            ));
        } elseif ($this->security->isGranted('ROLE_AGENCIA')) {
            $builder->add('agente', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => TnAgente::class,
                'query_builder' => function (EntityRepository $er) {
                    $query = $er->createQueryBuilder('a')
                        ->join('a.usuario', 'usuario')
                        ->where("a.enabled = true")
                        ->andWhere('usuario.porcentaje IS NOT NULL and usuario.porcentaje <> 0')
                        ->andWhere('a.agencia = :ag')
                        ->setParameter('ag', $this->user->getAgencia());
                    return $query;
                },
                'choice_attr' => function (TnAgente $tnAgente) {
                    return [
                        'data-type' => 'porciento',
                        'data-porciento' => $tnAgente->getUsuario()->getPorcentaje(),
                        'data-tipoagente' => $tnAgente->getTipoAgente()
                    ];
                },
                'choice_label' => function (TnAgente $tnAgente) {
                    return $tnAgente . " - " . $tnAgente->getUsuario()->getPorcentaje() . " %";
                },
                'label' => 'backend.agente.form.fields.agente',
                'placeholder' => 'backend.comun.form.select_placeholder',
                'required' => false
            ));
        }

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (PreSubmitEvent $event) {
            $form = $event->getForm();

            if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
                $form->remove("emisor");
                $form->add('emisor', EntityType::class, array(
                    'translation_domain' => 'AppBundle',
                    'class' => TnEmisor::class,
                    'query_builder' => function (EntityRepository $er) {
                        $query = $er->createQueryBuilder('e')
                            ->where("e.enabled = true");;
                        return $query;
                    },
                    'label' => 'backend.factura.form.fields.emisor',
                    'choice_label' => function (TnEmisor $tnEmisor) {
                        return $tnEmisor->getNombre() . " " . $tnEmisor->getApellidos() . " - " . $tnEmisor->getPhone();
                    },
                    'required' => true,
                    'placeholder' => 'backend.comun.form.select_placeholder'
                ));
            }

        });

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                $form = $event->getForm();
                if (count($form->getData()->getRemesas()) > 0) {
                    //Verificando si es agente o agencia y los grupos de pago que tiene asociado con las monedas de las remesas.
                    if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
                        $monedas = [];
                        $type = '';
                        if ($form->getData()->getAgencia() != null) {
                            foreach ($form->getData()->getAgencia()->getGruposPago() as $grupo) {
                                if ($grupo->getMoneda() != null) {
                                    if (!in_array($grupo->getMoneda()->getSimbolo(), $monedas)) {
                                        $monedas[] = $grupo->getMoneda()->getSimbolo();
                                    }
                                }
                            }
                            $type = "Agencia";
                            foreach ($form->getData()->getRemesas() as $remesa) {
                                //Verficando el saldo
                                $tnAgencia = $form->getData()->getAgencia();
                                $moneda = $remesa->getMoneda();
                                $nmGrupoPago = $this->entityManager->getRepository(NmGrupoPago::class)->grupoPagoAgencia($tnAgencia, $moneda);
                                if (!is_null($nmGrupoPago)) {
                                    $saldoMoneda = $this->entityManager->getRepository(TnSaldoAgencia::class)->findOneBy(['agencia' => $tnAgencia, 'grupoPago' => $nmGrupoPago]);
                                    if (!is_null($saldoMoneda)) {
                                        if ($remesa->getImporteEntregar() > $saldoMoneda->getSaldo()) {
                                            $form->get('remesas')->addError(new FormError('El usuario no tiene configurado suficiente saldo en la moneda seleccionada para crear la remesa.'));
                                        }
                                    } else {
                                        $form->get('remesas')->addError(new FormError('El usuario no tiene configurado saldo en la moneda seleccionada.'));
                                    }
                                }
                            }
                        } elseif ($form->getData()->getAgente()) {
                            foreach ($form->getData()->getAgente()->getGruposPago() as $grupo) {
                                if ($grupo->getMoneda() != null) {
                                    if (!in_array($grupo->getMoneda()->getSimbolo(), $monedas)) {
                                        $monedas[] = $grupo->getMoneda()->getSimbolo();
                                    }
                                }
                            }
                            $type = "Agente";

                            foreach ($form->getData()->getRemesas() as $remesa) {
                                //Verficando el saldo
                                $tnAgente = $form->getData()->getAgente();
                                $moneda = $remesa->getMoneda();
                                $nmGrupoPago = $this->entityManager->getRepository(NmGrupoPagoAgente::class)->grupoPagoAgente($tnAgente, $moneda);
                                if (!is_null($nmGrupoPago)) {
                                    $saldoMoneda = $this->entityManager->getRepository(TnSaldoAgente::class)->findOneBy(['agente' => $tnAgente, 'grupoPagoAgente' => $nmGrupoPago]);
                                    if (!is_null($saldoMoneda)) {
                                        if ($remesa->getImporteEntregar() > $saldoMoneda->getSaldo()) {
                                            $form->get('remesas')->addError(new FormError('El usuario no tiene configurado suficiente saldo en la moneda seleccionada para crear la remesa.'));
                                        }
                                    } else {
                                        $form->get('remesas')->addError(new FormError('El usuario no tiene configurado saldo en la moneda seleccionada.'));
                                    }
                                }
                            }
                        }
                        foreach ($form->getData()->getRemesas() as $remesa) {
                            if ($remesa->getMoneda() != null) {
                                if (!in_array($remesa->getMoneda()->getSimbolo(), $monedas)) {
                                    if ($type == "Agencia") {
                                        $form->get('remesas')->addError(new FormError('La Agencia no tiene configurada la moneda ' . $remesa->getMoneda()->getSimbolo() . ' para operar.'));
                                    } else {
                                        $form->get('remesas')->addError(new FormError('El Agente no tiene configurada la moneda ' . $remesa->getMoneda()->getSimbolo() . ' para operar.'));
                                    }
                                }
                            } else {
                                $form->get('remesas')->addError(new FormError(' Todas las remesas deben tener seleccionada la moneda de la entrega.'));
                            }
                        }
                    } elseif ($this->security->isGranted('ROLE_AGENCIA')) {//Agencia verificar primero los agentes
                        if ($form->getData()->getAgente() != null) {
                            $monedas = [];
                            foreach ($form->getData()->getAgente()->getGruposPago() as $grupo) {
                                if ($grupo->getMoneda() != null) {
                                    if (!in_array($grupo->getMoneda()->getSimbolo(), $monedas)) {
                                        $monedas[] = $grupo->getMoneda()->getSimbolo();
                                    }
                                }
                            }
                            foreach ($form->getData()->getRemesas() as $remesa) {
                                if ($remesa->getMoneda() != null) {
                                    if (!in_array($remesa->getMoneda()->getSimbolo(), $monedas)) {
                                        $form->get('remesas')->addError(new FormError('El Agente no tiene configurada la moneda ' . $remesa->getMoneda()->getSimbolo() . ' para operar.'));
                                    }
                                } else {
                                    $form->get('remesas')->addError(new FormError(' Todas las remesas deben tener seleccionada la moneda de la entrega.'));
                                }
                            }

                            //Verficando el saldo
                            $tnAgente = $form->getData()->getAgente();
                            $moneda = $remesa->getMoneda();
                            $nmGrupoPago = $this->entityManager->getRepository(NmGrupoPagoAgente::class)->grupoPagoAgente($tnAgente, $moneda);
                            if (!is_null($nmGrupoPago)) {
                                $saldoMoneda = $this->entityManager->getRepository(TnSaldoAgente::class)->findOneBy(['agente' => $tnAgente, 'grupoPagoAgente' => $nmGrupoPago]);
                                if (!is_null($saldoMoneda)) {
                                    if ($remesa->getImporteEntregar() > $saldoMoneda->getSaldo()) {
                                        $form->get('remesas')->addError(new FormError('El usuario no tiene configurado suficiente saldo en la moneda seleccionada para crear la remesa.'));
                                    }
                                } else {
                                    $form->get('remesas')->addError(new FormError('El usuario no tiene configurado saldo en la moneda seleccionada.'));
                                }
                            }
                        } else {
                            $monedas = [];
                            if ($this->user->getAgencia() != null) {
                                foreach ($this->user->getAgencia()->getGruposPago() as $grupo) {
                                    if ($grupo->getMoneda() != null) {
                                        if (!in_array($grupo->getMoneda()->getSimbolo(), $monedas)) {
                                            $monedas[] = $grupo->getMoneda()->getSimbolo();
                                        }
                                    }
                                }
                            }
                            $remesa = null;
                            foreach ($form->getData()->getRemesas() as $remesaFact) {
                                $remesa = $remesaFact;
                                if ($remesa->getMoneda() != null) {
                                    if (!in_array($remesa->getMoneda()->getSimbolo(), $monedas)) {
                                        $form->get('remesas')->addError(new FormError('La Agencia no tiene configurada la moneda ' . $remesa->getMoneda()->getSimbolo() . ' para operar.'));
                                    }
                                } else {
                                    $form->get('remesas')->addError(new FormError(' Todas las remesas deben tener seleccionada la moneda de la entrega.'));
                                }
                            }
                            //Verficando el saldo
                            $tnAgencia = $this->user->getAgencia();
                            $moneda = $remesa->getMoneda();
                            $nmGrupoPago = $this->entityManager->getRepository(NmGrupoPago::class)->grupoPagoAgencia($tnAgencia, $moneda);
                            if (!is_null($nmGrupoPago)) {
                                $saldoMoneda = $this->entityManager->getRepository(TnSaldoAgencia::class)->findOneBy(['agencia' => $tnAgencia, 'grupoPago' => $nmGrupoPago]);
                                if (!is_null($saldoMoneda)) {
                                    if ($remesa->getImporteEntregar() > $saldoMoneda->getSaldo()) {
                                        $form->get('remesas')->addError(new FormError('El usuario no tiene configurado suficiente saldo en la moneda seleccionada para crear la remesa.'));
                                    }
                                } else {
                                    $form->get('remesas')->addError(new FormError('El usuario no tiene configurado saldo en la moneda seleccionada.'));
                                }
                            }
                        }
                    } else {//Agentes verificando sus datos.
                        $monedas = [];
                        foreach ($this->user->getAgente()->getGruposPago() as $grupo) {
                            if ($grupo->getMoneda() != null) {
                                if (!in_array($grupo->getMoneda()->getSimbolo(), $monedas)) {
                                    $monedas[] = $grupo->getMoneda()->getSimbolo();
                                }
                            }
                        }
                        $remesa = null;
                        foreach ($form->getData()->getRemesas() as $remesaFact) {
                            $remesa = $remesaFact;
                            if ($remesa->getMoneda() != null) {
                                if (!in_array($remesa->getMoneda()->getSimbolo(), $monedas)) {
                                    $form->get('remesas')->addError(new FormError('El Agente no tiene configurada la moneda ' . $remesa->getMoneda()->getSimbolo() . ' para operar.'));
                                }
                            } else {
                                $form->get('remesas')->addError(new FormError(' Todas las remesas deben tener seleccionada la moneda de la entrega.'));
                            }
                        }

                        //Verficando el saldo
                        $tnAgente = $this->user->getAgente();
                        $moneda = $remesa->getMoneda();
                        $nmGrupoPago = $this->entityManager->getRepository(NmGrupoPagoAgente::class)->grupoPagoAgente($tnAgente, $moneda);
                        if (!is_null($nmGrupoPago)) {
                            $saldoMoneda = $this->entityManager->getRepository(TnSaldoAgente::class)->findOneBy(['agente' => $tnAgente, 'grupoPagoAgente' => $nmGrupoPago]);
                            if (!is_null($saldoMoneda)) {
                                if ($remesa->getImporteEntregar() > $saldoMoneda->getSaldo()) {
                                    $form->get('remesas')->addError(new FormError('El usuario no tiene configurado suficiente saldo en la moneda seleccionada para la remesa.'));
                                }
                            } else {
                                $form->get('remesas')->addError(new FormError('El usuario no tiene configurado saldo en la moneda seleccionada.'));
                            }
                        }
                    }
                } else {
                    $form->get('remesas')->addError(new FormError(' La factura debe tener al menos 1 remesa, revise.'));
                }
            });
    }

    public
    function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => TnFactura::class,
        ]);
    }
}
