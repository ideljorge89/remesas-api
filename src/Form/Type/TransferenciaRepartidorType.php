<?php

namespace App\Form\Type;

use App\Entity\TnDistribuidor;
use App\Entity\TnRemesa;
use App\Entity\TnRepartidor;
use App\Entity\TnTransferencia;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TransferenciaRepartidorType extends AbstractType
{
    private $transferencia;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->transferencia = $builder->getForm();

        $builder
            ->add('repartidor', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => TnRepartidor::class,
                'query_builder' => function (EntityRepository $er) {
                    $query = $er->createQueryBuilder('rp')
                        ->where('rp.enabled = true');
                    return $query;
                },
                'label' => 'backend.transferencia.asignar.title',
                'choice_label' => function (TnRepartidor $tnRepartidor) {
                    return $tnRepartidor->getNombre();
                },
                'placeholder' => 'backend.comun.form.select_placeholder',
                'required' => true
            ));

        $builder->addEventListener(FormEvents::POST_SET_DATA, function ($event) {
            $form = $event->getForm();
            $this->transferencia = $form->getData();

        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (PreSubmitEvent $event) {
            $form = $event->getForm();
            $this->transferencia = $form->getData();

        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => TnTransferencia::class,
        ]);
    }
}
