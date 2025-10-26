<?php

namespace App\Form;

use App\Entity\TnEmisor;
use App\Entity\TnUser;
use App\Form\Type\StatesType;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TnEmisorType extends AbstractType
{
    private $container;
    private $user;
    private $facturaManager;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->security = $this->container->get('security.authorization_checker');
        $this->facturaManager = $container->get('factura_manager');
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->user = $this->container->get('security.token_storage')->getToken()->getUser();

        $builder
            ->add('nombre', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.emisor.form.fields.nombre',
                'required' => true
            ))
            ->add('apellidos', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.emisor.form.fields.apellidos',
                'required' => true
            ))
            ->add('phone', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.emisor.form.fields.telefono',
                'required' => false
            ))
            ->add('email', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.emisor.form.fields.email',
                'required' => false
            ))
            ->add('enabled', CheckboxType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.emisor.form.fields.enabled',
                'required' => false
            ))
            ->add('dir_line1', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.emisor.form.fields.direccion1',
                'required' => false
            ))
            ->add('dir_line2', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.emisor.form.fields.direccion2',
                'required' => false
            ))
            ->add('dir_line3', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.emisor.form.fields.direccion3',
                'required' => false
            ))
            ->add('city', TextType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.emisor.form.fields.ciudad',
                'required' => false
            ))
            ->add('state', StatesType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.emisor.form.fields.estado',
                'placeholder' => 'backend.comun.form.select_placeholder',
                'required' => true
            ))
            ->add('zip', NumberType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.emisor.form.fields.zip',
                'required' => false
            ))
            ->add('country', CountryType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.emisor.form.fields.pais',
                'placeholder' => 'backend.comun.form.select_placeholder',
                'required' => true,
                'data' => "US"
            ))
            ->add('handler', HiddenType::class, array(
                'mapped' => false
            ));
        if ($this->security->isGranted('ROLE_AGENCIA') || $this->security->isGranted('ROLE_SUPER_ADMIN')) {
            $builder->add('usuario', EntityType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.emisor.form.fields.user',
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
                'required' => true,
                'placeholder' => 'backend.comun.form.select_placeholder'
            ));
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => TnEmisor::class,
        ]);
    }
}
