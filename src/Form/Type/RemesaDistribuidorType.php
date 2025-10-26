<?php

namespace App\Form\Type;

use App\Entity\TnDistribuidor;
use App\Entity\TnRemesa;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RemesaDistribuidorType extends AbstractType
{
    private $remesa;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->remesa = $builder->getForm();

        $builder
            ->add('distribuidor', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => TnDistribuidor::class,
                'query_builder' => function (EntityRepository $er) {
                    $query = $er->createQueryBuilder('d')
                        ->join("d.zonas", 'zona')
                        ->where('zona.id = :zd')
                        ->andWhere('d.enabled = true')
                        ->setParameter('zd', 1);
                    return $query;
                },
                'label' => 'backend.contenedor.proccess.calidad_venta',
                'placeholder' => 'backend.comun.form.select_placeholder',
                'required' => true
            ));

        $builder->addEventListener(FormEvents::POST_SET_DATA, function ($event) {
            $form = $event->getForm();
            $this->remesa = $form->getData();

            $form->remove('distribuidor');
            if($this->remesa->getEntregada() && $this->remesa->getDistribuidor()){
                $form->add('distribuidor', HiddenType::class, ["property_path" => "distribuidor.id"]);
            }else{
                $form->add('distribuidor', EntityType::class, array(
                    'translation_domain' => 'AppBundle',
                    'class' => TnDistribuidor::class,
                    'query_builder' => function (EntityRepository $er) {
                        $query = $er->createQueryBuilder('d')
                            ->join("d.zonas", 'zona')
                            ->where('zona.id = :zd')
                            ->andWhere('d.enabled = true')
                            ->setParameter('zd', $this->remesa->getDestinatario()->getMunicipio()->getId());
                        return $query;
                    },
                    'label' => 'backend.contenedor.proccess.calidad_venta',
                    'placeholder' => 'backend.comun.form.select_placeholder',
                    'required' => true
                ));
            }
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (PreSubmitEvent $event) {
            $form = $event->getForm();
            $this->remesa = $form->getData();

            $form->remove('distribuidor');
            $form->add('distribuidor', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => TnDistribuidor::class,
                'query_builder' => function (EntityRepository $er) {
                    $query = $er->createQueryBuilder('d')
                        ->join("d.zonas", 'zona')
                        ->where('zona.id = :zd')
                        ->andWhere('d.enabled = true')
                        ->setParameter('zd', $this->remesa->getDestinatario()->getMunicipio()->getId());
                    return $query;
                },
                'label' => 'backend.contenedor.proccess.calidad_venta',
                'placeholder' => 'backend.comun.form.select_placeholder',
                'required' => true
            ));
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => TnRemesa::class,
        ]);
    }
}
