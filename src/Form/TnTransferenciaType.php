<?php

namespace App\Form;

use App\Entity\NmEstadoTransferencia;
use App\Entity\NmGrupoPagoTransf;
use App\Entity\NmGrupoPagoTransfAgente;
use App\Entity\NmMoneda;
use App\Entity\TnAgencia;
use App\Entity\TnAgente;
use App\Entity\TnConfiguration;
use App\Entity\TnOperacionAgenciaTransf;
use App\Entity\TnOperacionAgenteTransf;
use App\Entity\TnTransferencia;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

class TnTransferenciaType extends AbstractType
{

    private $container;
    private $facturaManager;
    private $entityManager;
    private $webRoot;
    private $translator;
    private $security;
    private $user;

    private $transferencia = null;

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

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->user = $this->container->get('security.token_storage')->getToken()->getUser();

        if (!is_null($builder->getForm()->getData()->getId())) {
            $this->transferencia = $builder->getForm()->getData()->getId();
        }

        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            $builder->add('estado', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.transferencia.form.fields.estado',
                'class' => NmEstadoTransferencia::class,
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
                        'data-porciento' => $tnAgente->getUsuario()->getPorcentaje()
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
                        'data-porciento' => $tnAgencia->getUsuario()->getPorcentaje()
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
                        'data-porciento' => $tnAgente->getUsuario()->getPorcentaje()
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

        if ($this->user->getAuth()) {
            $builder
                ->add('auth', CheckboxType::class, array(
                    'required' => true
                ))
                ->add('totalCobrar', NumberType::class, array(
                    'translation_domain' => 'AppBundle',
                    'scale' => 2,
                    'label' => 'backend.transferencia.form.fields.importe',
                    'required' => false,
                    'attr' => [
                        'data-rule-number' => true
                    ], 'constraints' => [
                        new NotBlank()
                    ]
                ));
        }

        $builder
            ->add('titularTarjeta', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.transferencia.form.fields.titular',
                'required' => true
            ))
            ->add('emisor', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.transferencia.form.fields.emisor',
                'required' => false
            ))
            ->add('numeroTarjeta', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.transferencia.form.fields.tarjeta',
                'required' => true
            ))
            ->add('phone', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.transferencia.form.fields.phone',
                'required' => true
            ))
            ->add('importe', NumberType::class, array(
                'translation_domain' => 'AppBundle',
                'scale' => 2,
                'label' => 'backend.transferencia.form.fields.entrega',
                'required' => false,
                'attr' => [
                    'data-rule-number' => true
                ], 'constraints' => [
                    new NotNull(),
                    new NotBlank()
                ]))
            ->add('moneda', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => NmMoneda::class,
                'query_builder' => function (EntityRepository $er) {
                    if ($this->security->isGranted("ROLE_SUPER_ADMIN")) {
                        $query = $er->createQueryBuilder('m')
                            ->where("m.enabled = true")
                            ->andWhere('m.codigo = :cod')
                            ->setParameter('cod', NmMoneda::CURRENCY_USD);
                    } else {
                        $query = $er->createQueryBuilder('m')
                            ->where("m.aviable = true")
                            ->andWhere('m.codigo = :cod')
                            ->setParameter('cod', NmMoneda::CURRENCY_USD);
                    }
                    return $query;
                },
                'choice_label' => function (NmMoneda $nmMoneda) {
                    return $nmMoneda->getSimbolo() . " - (1 x " . $nmMoneda->getTasaCambio() . ")";
                },
                'choice_attr' => function (NmMoneda $nmMoneda) {
                    $porciento = 'Sin definir';
                    if ($this->security->isGranted("ROLE_SUPER_ADMIN")) { //Admin
                        $porciento = $this->container->get('configuration')->get(TnConfiguration::PORCENTAJE);
                    }
                    if ($this->security->isGranted('ROLE_AGENCIA')) { //Agencia
                        $tnAgencia = $this->user->getAgencia();
                        $nmGrupoPago = $this->entityManager->getRepository(NmGrupoPagoTransf::class)->grupoPagoAgencia($tnAgencia, $nmMoneda);
                        $porcentajeOperacion = $this->entityManager->getRepository(TnOperacionAgenciaTransf::class)->findOneBy(['agencia' => $tnAgencia, 'grupoPago' => $nmGrupoPago]);
                        if (!is_null($porcentajeOperacion) && !is_null($porcentajeOperacion->getPorcentaje())) {
                            $porciento = $porcentajeOperacion->getPorcentaje();
                        } else {
                            $porciento = $this->user->getPorcentaje();
                        }
                    }
                    if ($this->security->isGranted('ROLE_AGENTE')) { //Agente
                        $tnAgente = $this->user->getAgente();
                        $nmGrupoPago = $this->entityManager->getRepository(NmGrupoPagoTransfAgente::class)->grupoPagoAgente($tnAgente, $nmMoneda);
                        $porcentajeOperacion = $this->entityManager->getRepository(TnOperacionAgenteTransf::class)->findOneBy(['agente' => $tnAgente, 'grupoPago' => $nmGrupoPago]);
                        if (!is_null($porcentajeOperacion) && !is_null($porcentajeOperacion->getPorcentaje())) {
                            $porciento = $porcentajeOperacion->getPorcentaje();
                        } else {
                            $porciento = $this->user->getPorcentaje();
                        }
                    }
                    return [
                        'data-tasa' => $nmMoneda->getTasaCambio(),
                        'data-porciento' => $porciento,
                        'data-minimo' => $nmMoneda->getMinimoTransferencia(),
                        'data-maximo' => $nmMoneda->getMaximoTransferencia(),
                        'data-multiplo' => 1 // Solo los múltiplos de 50 para USD
                    ];
                },
                'label' => 'backend.factura.form.fields.moneda',
                'placeholder' => 'Seleccione una moneda',
                'required' => true
            ));

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                $form = $event->getForm();
                if ($this->user->getAuth()) {
                    if (!$this->security->isGranted("ROLE_SUPER_ADMIN")) {
                        if ($form->getData()->getTotalCobrar() == null || $form->getData()->getTotalCobrar() <= 0) {
                            $form->get('totalCobrar')->addError(new FormError('El importe a cobrar debe ser mayor que 0 y no puede estar vacío.'));
                        }
                    }
                } else {
                    if ($form->getData()->getImporte() == null || $form->getData()->getImporte() <= 0) {
                        $form->get('importe')->addError(new FormError('El importe a entregar debe ser mayor que 0 y no puede estar vacío.'));
                    }
                }
                if ($form->getData()->getTitularTarjeta() == null) {
                    $form->get('titularTarjeta')->addError(new FormError('Debe especificar un titular de la tarjeta.'));
                }
                if ($form->getData()->getNumeroTarjeta() == null) {
                    $form->get('numeroTarjeta')->addError(new FormError('Debe especificar un número de tarjeta.'));
                }else{
                    $sinEspacio = str_replace(' ', '', $form->getData()->getNumeroTarjeta());
                    $formato = substr($sinEspacio, 0, 8);
                    if($formato != '92259598' && $formato != '92251299' && $formato != '92250699'){
                        $form->get('numeroTarjeta')->addError(new FormError('Debe especificar un número válido de tarjeta.'));
                    }
                }
                if ($form->getData()->getMoneda() == null) {
                    $form->get('moneda')->addError(new FormError('Debe especificar la moneda de la entrega.'));
                }

                if (!$this->security->isGranted("ROLE_SUPER_ADMIN")) {
                    if ($form->getData()->getMoneda() != null && ($form->getData()->getImporte() < $form->getData()->getMoneda()->getMinimoTransferencia() || $form->getData()->getImporte() > $form->getData()->getMoneda()->getMaximoTransferencia())) {
                        $form->get('importe')->addError(new FormError('El importe a entregar en la moneda seleccionada no es válido. Mínimo - '.$form->getData()->getMoneda()->getMinimoTransferencia().', Máximo - '.$form->getData()->getMoneda()->getMaximoTransferencia().'.'));
                    }
                    if ($this->security->isGranted("ROLE_AGENCIA")) {
                        if ($form->getData()->getAgente() != null) {
                            $grupoPagoAgente = $this->entityManager->getRepository(NmGrupoPagoTransfAgente::class)->grupoPagoAgente($form->getData()->getAgente(), $form->getData()->getMoneda());
                            if ($grupoPagoAgente == null) {
                                $form->get('agente')->addError(new FormError('El Agente debe tener configurado un grupo de pago de tranferencias para poder operar.'));
                            }
                        } else {
                            $grupoPagoAgencia = $this->entityManager->getRepository(NmGrupoPagoTransf::class)->grupoPagoAgencia($this->user->getAgencia(), $form->getData()->getMoneda());
                            if ($grupoPagoAgencia == null) {
                                $form->get('importe')->addError(new FormError('La Agencia debe tener configurado un grupo de pago de tranferencias para poder operar.'));
                            }
                        }
                    } else {
                        $grupoPagoAgente = $this->entityManager->getRepository(NmGrupoPagoTransfAgente::class)->grupoPagoAgente($this->user->getAgente(), $form->getData()->getMoneda());
                        if ($grupoPagoAgente == null) {
                            $form->get('importe')->addError(new FormError('El Agente debe tener configurado un grupo de pago de tranferencias para poder operar.'));
                        }
                    }
                } else {
                    if ($form->getData()->getAgencia() != null) {
                        $grupoPagoAgencia = $this->entityManager->getRepository(NmGrupoPagoTransf::class)->grupoPagoAgencia($form->getData()->getAgencia(), $form->getData()->getMoneda());
                        if ($grupoPagoAgencia == null) {
                            $form->get('agencia')->addError(new FormError('La Agencia debe tener configurado un grupo de pago de tranferencias para poder operar.'));
                        }
                    }
                    if ($form->getData()->getAgente() != null) {
                        $grupoPagoAgente = $this->entityManager->getRepository(NmGrupoPagoTransfAgente::class)->grupoPagoAgente($form->getData()->getAgente(), $form->getData()->getMoneda());
                        if ($grupoPagoAgente == null) {
                            $form->get('agente')->addError(new FormError('El Agente debe tener configurado un grupo de pago de tranferencias para poder operar.'));
                        }
                    }
                }
            });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => TnTransferencia::class,
        ]);
    }
}
