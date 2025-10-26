<?php

namespace App\Form\Type;

use App\Entity\NmEstado;
use App\Entity\TnAgencia;
use App\Entity\TnAgente;
use App\Entity\TnDistribuidor;
use App\Entity\TnUser;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DestinatarioSerachType extends AbstractType
{

    private $container;
    private $facturaManager;
    private $security;
    private $user;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->facturaManager = $container->get('factura_manager');
        $this->security = $this->container->get('security.authorization_checker');
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->user = $this->container->get('security.token_storage')->getToken()->getUser();

        $builder
            ->add('emisor', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'attr' => array(
                    'placeholder' => 'Emisor'
                ),
                'required' => false
            ))
            ->add('destinatario', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'attr' => array(
                    'placeholder' => 'Destinatario'
                ),
                'required' => false
            ))
            ->add('phone', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'attr' => array(
                    'placeholder' => 'Telefono'
                ),
                'required' => false
            ))
            ->add('direccion', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'attr' => array(
                    'placeholder' => 'Dirección'
                ),
                'required' => false
            ));

        if ($this->security->isGranted('ROLE_AGENCIA') || $this->security->isGranted('ROLE_SUPER_ADMIN')) {
            $builder->add('usuario', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'class' => TnUser::class,
                'query_builder' => function (EntityRepository $er) {
                    $query = $er->createQueryBuilder('u')
                        ->where('u.roles LIKE :ag or u.roles LIKE :agt')->setParameter('ag', "%ROLE_AGENCIA%")->setParameter('agt', '%ROLE_AGENTE%');
                    if ($this->security->isGranted('ROLE_AGENCIA')) {
                        $usuariosIds = $this->facturaManager->getUsersAgentesByAgencia($this->user->getAgencia());

                        $query->andWhere("u.id IN (:users)")
                            ->setParameter('users', $usuariosIds);
                    }
                    return $query;
                },
                'choice_label' => function (TnUser $tnUser) {
                    return $tnUser->getAgencia() ? $tnUser->getAgencia() : $tnUser->getAgente();
                },
                'multiple' => true,
                'required' => false
            ));
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
