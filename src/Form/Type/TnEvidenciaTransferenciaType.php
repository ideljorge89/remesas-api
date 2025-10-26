<?php

namespace App\Form\Type;

use App\Entity\TnTransferencia;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TnEvidenciaTransferenciaType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('evidencia', FileType::class, [
                'label' => 'Evidencia',
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new File([
                        'maxSize' => '20480k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png'
                        ],
                        'mimeTypesMessage' => 'Por favor, debe subir una evidencia válida.',
                    ])
                ],
            ])
            ->add('notas', TextareaType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.transferencia.table.notas',
                'required' => false
            ));

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                $form = $event->getForm();
                if ($form->get('evidencia')->getData() == null) {
                    $form->get('evidencia')->addError(new FormError('Debe especificar el archivo de la evidencia.'));
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
