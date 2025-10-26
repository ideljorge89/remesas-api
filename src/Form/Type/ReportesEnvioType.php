<?php

namespace App\Form\Type;

use App\Entity\TnReporteEnvio;
use App\Model\ReporteEnvioModel;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;

class ReportesEnvioType extends AbstractType
{
    private $reportesId;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $reportes = $builder->getForm()->getData()->getReportes();
        foreach ($reportes as $reporte) {
            $this->reportesId[] = $reporte->getId();
        }

        $builder
            ->add('reportes', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => TnReporteEnvio::class,
                'query_builder' => function (EntityRepository $er) {
                    $query = $er->createQueryBuilder('r')
                        ->where("r.id IN (:reportes)")
                        ->orderBy('r.created', "DESC")
                        ->setParameter('reportes', $this->reportesId);
                    return $query;
                },
                'constraints' => [new Count(['min' => 1])],
                'multiple' => true,
                'expanded' => true,
                'required' => true
            ));

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                $form = $event->getForm();
                if (count($form->getData()->getReportes()) == 0) {
                    $form->get('reportes')->addError(new FormError('Para ver o exportar debe seleccionar al menos 1 reporte, revise.'));
                }
            });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ReporteEnvioModel::class,
        ]);
    }
}
