<?php

namespace App\Form;

use App\Entity\NmMunicipio;
use App\Entity\NmProvincia;
use App\Entity\TnDistribuidor;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;

class TnDistribuidorType extends AbstractType
{
    private $provincia;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nombre', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.distribuidor.form.fields.nombre',
                'required' => true
            ))
            ->add('apellidos', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.distribuidor.form.fields.apellidos',
                'required' => true
            ))
            ->add('ci', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.distribuidor.form.fields.ci',
                'required' => true
            ))
            ->add('phone', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.distribuidor.form.fields.telefono',
                'required' => false
            ))
            ->add('email', EmailType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.distribuidor.form.fields.email',
                'required' => false
            ))
            ->add('enabled', CheckboxType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.distribuidor.form.fields.enabled',
                'required' => false
            ))
            ->add('direccion', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.distribuidor.form.fields.direccion',
                'required' => true
            ))
            ->add('direccion1', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.distribuidor.form.fields.direccion1',
                'required' => false
            ))
            ->add('datosLicencia', TextareaType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.distribuidor.form.fields.licencia',
                'required' => true
            ))
            ->add('municipio', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => NmMunicipio::class,
                'label' => 'backend.distribuidor.form.fields.municipio',
                'placeholder'=>'backend.comun.form.select_placeholder',
                'required' => true
            ))
            ->add('provincia', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => NmProvincia::class,
                'label' => 'backend.distribuidor.form.fields.provincia',
                'placeholder'=>'backend.comun.form.select_placeholder',
                'required' => true
            ))
            ->add('country', CountryType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.distribuidor.form.fields.pais',
                'placeholder' => 'backend.comun.form.select_placeholder',
                'required' => true,
                'data' => "CU"
            ))
            ->add('zonas', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => NmMunicipio::class,
                'label' => 'backend.distribuidor.form.fields.zonas',
                'placeholder' => 'backend.comun.form.select_placeholder',
                'constraints' => [new Count(['min' => 1])],
                'multiple' => true,
                'required' => true
            ));

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

                $form ->add('municipio', EntityType::class, array(
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
            'data_class' => TnDistribuidor::class,
        ]);
    }
}
