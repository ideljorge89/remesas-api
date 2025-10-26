<?php

namespace App\Form;

use App\Entity\TnAgencia;
use App\Entity\TnApoderado;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;

class TnApoderadoType extends AbstractType
{

    private $container;
    private $entityManager;


    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->entityManager = $container->get('doctrine')->getManager();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nombre')
            ->add('agencia', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => TnAgencia::class,
                'query_builder' => function (EntityRepository $er) {
                    $query = $er->createQueryBuilder('ag')
                        ->andWhere("ag.enabled = true");
                    return $query;
                },
                'choice_label' => function (TnAgencia $agencia) {
                    return $agencia->getNombre();
                },
                'label' => 'backend.apoderado.form.fields.agencia',
                'placeholder' => 'Seleccione una agencia',
                'required' => true
            ))
            ->add('subordinadas', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => TnAgencia::class,
                'query_builder' => function (EntityRepository $er) {
                    $query = $er->createQueryBuilder('ag')
                        ->andWhere("ag.enabled = true");
                    return $query;
                },
                'label' => 'backend.apoderado.form.fields.subordinadas',
                'placeholder' => 'backend.comun.form.select_placeholder',
                'constraints' => [new Count(['min' => 2])],
                'multiple' => true,
                'required' => true
            ));

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($builder) {
                $form = $event->getForm();
                $data = $form->getData();
                //Validando los datos introducidos, primero que la agencia no esté ya representada y que esté dentro de las subirdinadas
                $apoderado = $this->entityManager->getRepository(TnApoderado::class)->findOneByAgency($form->getData()->getAgencia(), $data->getId());
                if (!is_null($apoderado)) {
                    $form->get('agencia')->addError(new FormError('Ya existe un apoderado representado esta agencia, verifique.'));
                }
                $encontrada = false;
                foreach ($data->getSubordinadas() as $agencia) {
                    $apodSubordinado = $this->entityManager->getRepository(TnApoderado::class)->findOneBySubordinada($agencia, $data->getId());
                    if (!is_null($apodSubordinado)) {
                        $form->get('subordinadas')->addError(new FormError('Ya existen agencias subordinadas a otro apoderado dentro de las seleccionadas, revise.'));
                    }
                    if ($agencia->getId() == $data->getAgencia()->getId()) {
                        $encontrada = true;
                    }
                }

                if ($encontrada == false) {
                    $form->get('subordinadas')->addError(new FormError('La agencia representada, dede estar en el listado de subordinadas.'));
                }
            });

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => TnApoderado::class,
        ]);
    }
}
