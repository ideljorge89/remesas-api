<?php

namespace App\Form;

use App\Entity\NmMoneda;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NmMonedaType extends AbstractType
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
            ->add('simbolo', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.moneda.form.fields.simbolo',
                'required' => true
            ))
            ->add('tasaCambio', NumberType::class, array(
                'scale' => 2,
                'translation_domain' => 'AppBundle',
                'label' => 'backend.moneda.form.fields.tasa',
                'required' => false,
                'attr' => [
                    'data-rule-number' => true,
                ]
            ))
            ->add('codigo', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.moneda.form.fields.codigo',
                'required' => false
            ))
            ->add('enabled', CheckboxType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.moneda.form.fields.enabled',
                'required' => false
            ))
            ->add('aviable', CheckboxType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.moneda.form.fields.aviable',
                'required' => false
            ))
            ->add('contable', CheckboxType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.moneda.form.fields.contable',
                'required' => false
            ))
            ->add('comision', CheckboxType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.moneda.form.fields.comision',
                'required' => false
            ))
            ->add('simbolo');

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($builder) {
                $form = $event->getForm();
                //Verificando si ya existe una moneda registrada que sea contable\
                if ($form->getData()->getContable()) {
                    $moneda = $this->entityManager->getRepository(NmMoneda::class)->findOneBy(['contable' => true]);
                    if ($moneda instanceof NmMoneda && $moneda->getCodigo() != $form->getData()->getCodigo()) {
                        $form->get('contable')->addError(new FormError('Ya existe una moneda contable, solamente debe existir una moneda contable para el sistema.'));
                    }
                }
                //Verificando que no exista otra moneda de comisión.
                if ($form->getData()->getComision()) {
                    $moneda = $this->entityManager->getRepository(NmMoneda::class)->findOneBy(['comision' => true]);
                    if ($moneda instanceof NmMoneda && $moneda->getCodigo() != $form->getData()->getCodigo()) {
                        $form->get('comision')->addError(new FormError('Ya existe una moneda para entregar comisión, solamente debe existir una en el sistema.'));
                    }
                }
            });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => NmMoneda::class,
        ]);
    }
}
