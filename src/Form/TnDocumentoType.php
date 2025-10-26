<?php

namespace App\Form;

use App\Entity\TnAgencia;
use App\Entity\TnDocumento;
use App\Entity\TnEmisor;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TnDocumentoType extends AbstractType
{
    private $container;
    private $facturaManager;
    private $entityManager;
    private $security;
    private $user;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->entityManager = $container->get('doctrine')->getManager();
        $this->facturaManager = $container->get('factura_manager');
        $this->security = $this->container->get('security.authorization_checker');
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->user = $this->container->get('security.token_storage')->getToken()->getUser();

        $builder
            ->add('agencia', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => TnAgencia::class,
                'label' => 'backend.documento.table.agencia',
                'query_builder' => function (EntityRepository $er) {
                    if ($this->security->isGranted('ROLE_AGENCIA')) {
                        $query = $er->createQueryBuilder('d')
                            ->join('d.usuario', 'usuario')
                            ->where('d.id = :agc')
                            ->andWhere("d.enabled = true")
                            ->setParameter('agc', $this->user->getAgencia());
                        return $query;
                    } else {
                        $query = $er->createQueryBuilder('d')
                            ->join('d.usuario', 'usuario')
                            ->where("d.enabled = true");
                        return $query;
                    }

                },
                'choice_attr' => function (TnAgencia $tnAgencia) {
                    return [
                        'data-user-id' => $tnAgencia->getUsuario()->getId()
                    ];
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
            ->add('url', FileType::class, [
                'label' => 'Fichero',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [

                        ],
                        'mimeTypesMessage' => 'Por favor, debe subir una fichero válido, extensión .csv, máximo 5MB',
                    ])
                ],
            ]);
        if ($this->security->isGranted('ROLE_AGENCIA')) {
            $tipoFiheros = [];
            if ($this->user->getAgencia()->getFichero() != null) {
                $tipoFiheros = [
                    'Fichero ' . $this->user->getAgencia()->getNombre() => $this->user->getAgencia()->getFichero()
                ];
            }
            $builder->add('tipo', ChoiceType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.documento.form.fields.tipo',
                'placeholder' => 'backend.comun.form.select_placeholder',
                'required' => true,
                'choices' => $tipoFiheros
            ));
        } else {
            $builder->add('tipo', ChoiceType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.documento.form.fields.tipo',
                'placeholder' => 'backend.comun.form.select_placeholder',
                'required' => true,
                'choices' => TnDocumento::getAllTipos()
            ));
        }

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => TnDocumento::class,
        ]);
    }
}
