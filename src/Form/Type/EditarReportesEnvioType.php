<?php

namespace App\Form\Type;

use App\Entity\TnDistribuidor;
use App\Entity\TnReporteEnvio;
use App\Model\EditarReporteEnvioModel;
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

class EditarReportesEnvioType extends AbstractType
{
    private $remesasId;
    private $reporte;
    private $distribuidor;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $remesas = $builder->getForm()->getData()->getRemesas();
        foreach ($remesas as $remesa) {
            $this->remesasId[] = $remesa->getId();
        }
        $this->distribuidor = $builder->getForm()->getData()->getReporte()->getDistribuidor()->getId();

        $builder
            ->add('distribuidor', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => TnDistribuidor::class,
                'label' => 'backend.reportes.editar_reporte.distrubuidor',
                'query_builder' => function (EntityRepository $er) {
                    $query = $er->createQueryBuilder('d')
                        ->where("d.id <> :idNo")
                        ->andWhere("d.enabled = true")
                        ->setParameter('idNo', $this->distribuidor);
                    return $query;
                },
                'choice_label' => function (TnDistribuidor $tnDistribuidor) {
                    return $tnDistribuidor->getNombre() . " " . $tnDistribuidor->getApellidos() . "-" . $tnDistribuidor->getPhone();
                },
                'placeholder' => 'backend.comun.form.select_placeholder',
                'required' => true
            ))
            ->add('emisor', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => TnEmisor::class,
                'label' => 'backend.documento.form.fields.emisor',
                'placeholder' => 'backend.comun.form.select_placeholder',
                'required' => true
            ))
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
            'data_class' => EditarReporteEnvioModel::class,
        ]);
    }
}
