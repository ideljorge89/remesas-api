<?php

namespace App\Form\Type;

use App\Entity\Contenedor;
use App\Entity\Factura;
use App\Entity\NmGrupoPago;
use App\Entity\NmGrupoPagoAgente;
use App\Entity\NmMoneda;
use App\Entity\NmMotivoReclamacion;
use App\Entity\TnConfiguration;
use App\Entity\TnDestinatario;
use App\Entity\TnFactura;
use App\Entity\TnOperacionAgencia;
use App\Entity\TnOperacionAgente;
use App\Entity\TnReclamacionContenedorMotivo;
use App\Entity\TnRemesa;
use App\Entity\TnSaldoAgencia;
use App\Entity\TnSaldoAgente;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

class TnRemesaType extends AbstractType
{
    private $container;
    private $security;
    private $user;
    private $entityManager;

    private $factura = null;
    private $destinatario = null;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->entityManager = $container->get('doctrine')->getManager();;
        $this->security = $this->container->get('security.authorization_checker');
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->user = $this->container->get('security.token_storage')->getToken()->getUser();
        //Pasando la factura a las remesas
        $this->factura = $options['attr_translation_parameters']['factura'];

        if ($this->user->getAuth()) {
            $builder
                ->add('total_pagar', NumberType::class, array(
                    'translation_domain' => 'AppBundle',
                    'scale' => 2,
                    'label' => 'backend.factura.form.fields.importe',
                    'required' => false,
                    'attr' => [
                        'data-rule-number' => true
                    ], 'constraints' => [
                        new NotBlank()
                    ]
                ));
        }
        $builder->add('importe_entregar', NumberType::class, array(
            'translation_domain' => 'AppBundle',
            'scale' => 2,
            'label' => 'backend.factura.form.fields.entrega',
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
                            ->where("m.enabled = true");
                    } else {
                        $query = $er->createQueryBuilder('m')
                            ->where("m.aviable = true");
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
                        $nmGrupoPago = $this->entityManager->getRepository(NmGrupoPago::class)->grupoPagoAgencia($tnAgencia, $nmMoneda);
                        $porcentajeOperacion = $this->entityManager->getRepository(TnOperacionAgencia::class)->findOneBy(['agencia' => $tnAgencia, 'grupoPago' => $nmGrupoPago]);
                        if (!is_null($porcentajeOperacion) && !is_null($porcentajeOperacion->getPorcentaje())) {
                            $porciento = $porcentajeOperacion->getPorcentaje();
                        } else {
                            $porciento = $this->user->getPorcentaje();
                        }
                    }
                    if ($this->security->isGranted('ROLE_AGENTE')) { //Agente
                        $tnAgente = $this->user->getAgente();
                        $nmGrupoPago = $this->entityManager->getRepository(NmGrupoPagoAgente::class)->grupoPagoAgente($tnAgente, $nmMoneda);
                        $porcentajeOperacion = $this->entityManager->getRepository(TnOperacionAgente::class)->findOneBy(['agente' => $tnAgente, 'grupoPagoAgente' => $nmGrupoPago]);
                        if (!is_null($porcentajeOperacion) && !is_null($porcentajeOperacion->getPorcentaje())) {
                            $porciento = $porcentajeOperacion->getPorcentaje();
                        } else {
                            $porciento = $this->user->getPorcentaje();
                        }
                    }

                    $multiplo = 1;
                    if($nmMoneda->getSimbolo() == NmMoneda::CURRENCY_USD){// Solo los múltiplos de 100 para USD
                        $multiplo = 100;
                    }
                    if($nmMoneda->getSimbolo() == NmMoneda::CURRENCY_EUR){// Solo los múltiplos de 50 para EUR
                        $multiplo = 50;
                    }

                    return [
                        'data-tasa' => $nmMoneda->getTasaCambio(),
                        'data-porciento' => $porciento,
                        'data-minimo' => $nmMoneda->getMinimo(),
                        'data-maximo' => $nmMoneda->getMaximo(),
                        'data-multiplo' => $multiplo
                    ];
                },
                'label' => 'backend.factura.form.fields.moneda',
                'placeholder' => 'Seleccione una moneda',
                'required' => true
            ));

        if ($this->security->isGranted('ROLE_AGENCIA') || $this->security->isGranted('ROLE_AGENTE')) {
            $builder->add('destinatario', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => TnDestinatario::class,
                'query_builder' => function (EntityRepository $er) {
                    $query = $er->createQueryBuilder('d')
                        ->where("d.enabled = true")
                        ->andWhere("d.usuario = :ua")
                        ->setParameter('ua', $this->user);
                    return $query;
                },
                'choice_label' => function (TnDestinatario $tnDestinatario) {
                    return $tnDestinatario->getNombre() . " " . $tnDestinatario->getApellidos();
                },
                'label' => 'backend.factura.form.fields.destinatario',
                'placeholder' => 'Seleccione un destinatario',
                'required' => true
            ));
        } else {
            $builder->add('destinatario', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => TnDestinatario::class,
                'query_builder' => function (EntityRepository $er) {
                    if ($this->factura != null) {
                        $tnFactura = $this->entityManager->getRepository(TnFactura::class)->find($this->factura);
                        $user = null;
                        if ($tnFactura->getAgente() != null && $tnFactura->getAgente()->getUsuario() != null) {
                            $user = $tnFactura->getAgente()->getUsuario();
                        } elseif ($tnFactura->getAgencia() != null && $tnFactura->getAgencia()->getUsuario() != null) {
                            $user = $tnFactura->getAgencia()->getUsuario();
                        }
                        if ($user != null) {
                            $query = $er->createQueryBuilder('d')
                                ->where("d.enabled = true")
                                ->andWhere("d.usuario = :ua")
                                ->setParameter('ua', $user);
                        } else {
                            $query = $er->createQueryBuilder('d')
                                ->where("d.enabled = true")
                                ->andWhere("d.usuario = :ua")
                                ->setParameter('ua', $this->user);
                        }
                    } else {
                        $query = $er->createQueryBuilder('d')
                            ->where("d.enabled = true")
                            ->andWhere("d.usuario = :ua")
                            ->setParameter('ua', $this->user);
                    }
                    return $query;
                },
                'label' => 'backend.factura.form.fields.destinatario',
                'choice_label' => function (TnDestinatario $tnDestinatario) {
                    return $tnDestinatario->getNombre() . " " . $tnDestinatario->getApellidos();
                },
                'placeholder' => 'Seleccione un destinatario',
                'required' => true
            ));
        }

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (PreSubmitEvent $event) {
            $form = $event->getForm();
            $data = $event->getData();

            $this->destinatario = isset($data['destinatario']) ? $data['destinatario'] : '';
            if ($this->destinatario != "") {
                $form->remove("destinatario");
                $form->add('destinatario', EntityType::class, array(
                    'translation_domain' => 'AppBundle',
                    'class' => TnDestinatario::class,
                    'query_builder' => function (EntityRepository $er) {
                        $query = $er->createQueryBuilder('d')
                            ->where("d.id = :dest")
                            ->setParameter('dest', $this->destinatario);
                        return $query;
                    },
                    'choice_label' => function (TnDestinatario $tnDestinatario) {
                        return $tnDestinatario->getNombre() . " " . $tnDestinatario->getApellidos();
                    },
                    'label' => 'backend.factura.form.fields.destinatario',
                    'placeholder' => 'Seleccione un destinatario',
                    'required' => true
                ));
            }

        });

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                $form = $event->getForm();
                if ($this->user->getAuth()) {
                    if (!$this->security->isGranted("ROLE_SUPER_ADMIN")) {
                        if ($form->getData()->getTotalPagar() == null || $form->getData()->getTotalPagar() <= 0) {
                            $form->get('total_pagar')->addError(new FormError('El importe debe ser mayor que 0 y no puede estar vacío.'));
                        }
                    }
                } else {
                    if ($form->getData()->getImporteEntregar() == null || $form->getData()->getImporteEntregar() <= 0) {
                        $form->get('importe_entregar')->addError(new FormError('El importe debe ser mayor que 0 y no puede estar vacío.'));
                    }
                }
                if ($form->getData()->getDestinatario() == null) {
                    $form->get('destinatario')->addError(new FormError('Debe especificar un destinatario.'));
                }

                if (!$this->security->isGranted("ROLE_SUPER_ADMIN")) {
                    if ($this->security->isGranted("ROLE_AGENCIA") && $this->user->getAgencia()->getUnlimited()) {
                        if ($form->getData()->getMoneda() != null && $form->getData()->getMoneda()->getSimbolo() == NmMoneda::CURRENCY_USD && $form->getData()->getImporteEntregar() % 100 != 0) {
                            $form->get('importe_entregar')->addError(new FormError('Si la moneda es USD solo puede enviar remesas con importe múltiplo de 100. Ej 100, 150, 200 etc.'));
                        }
                        if ($form->getData()->getMoneda() != null && $form->getData()->getMoneda()->getSimbolo() == NmMoneda::CURRENCY_EUR && $form->getData()->getImporteEntregar() % 50 != 0) {
                            $form->get('importe_entregar')->addError(new FormError('Si la moneda es EUR solo puede enviar remesas con importe múltiplo de 50. Ej 100, 150, 200 etc.'));
                        }
                    } else {
                        if ($form->getData()->getMoneda() != null && ($form->getData()->getImporteEntregar() < $form->getData()->getMoneda()->getMinimo() || $form->getData()->getImporteEntregar() > $form->getData()->getMoneda()->getMaximo())) {
                            $form->get('importe_entregar')->addError(new FormError('El total a entregar en la moneda seleccionada no es válido.'));
                        }

                        if ($form->getData()->getMoneda() != null && $form->getData()->getMoneda()->getSimbolo() == NmMoneda::CURRENCY_USD && $form->getData()->getImporteEntregar() % 100 != 0) {
                            $form->get('importe_entregar')->addError(new FormError('Si la moneda es USD solo puede enviar remesas con importe múltiplo de 100. Ej 100, 150, 200 etc.'));
                        }

                        if ($form->getData()->getMoneda() != null && $form->getData()->getMoneda()->getSimbolo() == NmMoneda::CURRENCY_EUR && $form->getData()->getImporteEntregar() % 50 != 0) {
                            $form->get('importe_entregar')->addError(new FormError('Si la moneda es EUR solo puede enviar remesas con importe múltiplo de 50. Ej 100, 150, 200 etc.'));
                        }
                    }

                    if ($form->getData()->getMoneda() == null) {
                        $form->get('moneda')->addError(new FormError('Debe especificar la moneda de la entrega.'));
                    }

                    if ($form->getData()->getMoneda()->getSimbolo() == NmMoneda::CURRENCY_EUR && !$this->container->get('factura_manager')->validarDestinatarioMonedaProvincia($form->getData()->getDestinatario(), $form->getData()->getMoneda())) {
                        $form->get('destinatario')->addError(new FormError('No se está entregando remesas en la moneda EUR en el municipio del destinatario seleccionado.'));
                    }

                    if ($form->getData()->getMoneda()->getSimbolo() == NmMoneda::CURRENCY_USD && $this->container->get('factura_manager')->validarDestinatario($this->user, $form->getData()->getDestinatario())) {
                        if ($form->getData()->getMoneda()->getMaximo() == $form->getData()->getImporteEntregar()) {
                            $form->get('destinatario')->addError(new FormError('Un mismo destinatario solo puede recibir 1 remesa en el día en la moneda USD en el monto máximo permitido.'));
                        }
                    }
                }

//                //Verificar el tengo saldo para regitrar la remesa
//                if ($this->security->isGranted("ROLE_AGENCIA")) {//Agencias
//                    $tnAgencia = $this->user->getAgencia();
//                    $moneda = $form->getData()->getMoneda();
//                    $nmGrupoPago = $this->entityManager->getRepository(NmGrupoPago::class)->grupoPagoAgencia($tnAgencia, $moneda);
//                    if (!is_null($nmGrupoPago)) {
//                        $saldoMoneda = $this->entityManager->getRepository(TnSaldoAgencia::class)->findOneBy(['agencia' => $tnAgencia, 'grupoPago' => $nmGrupoPago]);
//                        if (!is_null($saldoMoneda)) {
//                            if ($form->getData()->getImporteEntregar() > $saldoMoneda->getSaldo()) {
//                                $form->get('moneda')->addError(new FormError('El usuario no tiene configurado suficiente saldo en la moneda seleccionada para crear la remesa.'));
//                            }
//                        } else {
//                            $form->get('moneda')->addError(new FormError('El usuario no tiene configurado saldo en la moneda seleccionada.'));
//                        }
//                    }
//                } elseif ($this->security->isGranted("ROLE_AGENTE")) {//Agentes
//                    $tnAgente = $this->user->getAgente();
//                    $moneda = $form->getData()->getMoneda();
//                    $nmGrupoPago = $this->entityManager->getRepository(NmGrupoPagoAgente::class)->grupoPagoAgente($tnAgente, $moneda);
//                    if (!is_null($nmGrupoPago)) {
//                        $saldoMoneda = $this->entityManager->getRepository(TnSaldoAgente::class)->findOneBy(['agente' => $tnAgente, 'grupoPagoAgente' => $nmGrupoPago]);
//                        if (!is_null($saldoMoneda)) {
//                            if ($form->getData()->getImporteEntregar() > $saldoMoneda->getSaldo()) {
//                                $form->get('importe_entregar')->addError(new FormError('El usuario no tiene configurado suficiente saldo en la moneda seleccionada para crear la remesa.'));
//                            }
//                        } else {
//                            $form->get('importe_entregar')->addError(new FormError('El usuario no tiene configurado saldo en la moneda seleccionada.'));
//                        }
//                    }
//                }
            });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => TnRemesa::class
        ]);
    }

    public function getName()
    {
        return 'tn_factura_remesa';
    }
}
