<?php

namespace App\Form\Registration;

use App\Entity\TnBussinesProfile;
use App\Entity\TnProfileCliente;
use App\Form\Profile\ProfileFormType;
use App\Form\Type\TnAgenciaProfileType;
use App\Form\Type\TnProfileType;
use App\Repository\TnProfileAgenciaRepository;
use Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use FOS\UserBundle\Form\Type\RegistrationFormType as BaseType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Form\FormError;


class RegistrationFormType extends BaseType
{
    private $container;

    public function __construct($user, Container $container)
    {
        parent::__construct($user);
        $this->container = $container;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'App\Entity\TnUser',
            'validation_groups' => array('Registration'),
            'cascade_validation' => true
        ));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
//        parent::buildForm($builder, $options);

        $request = $this->container->get('request_stack')->getCurrentRequest();


        if ($request->getMethod() == "GET") {
            switch ($request->get('element')) {
                case 'register-company':
                    $form_profile = TnAgenciaProfileType::class;
                    break;
                case 'register-client':
                default:
                    $form_profile = TnProfileType::class;
                    break;
            }
        } else {

            $form = $request->request->get('fos_user_registration_form');
            if (@$form['type'])
                switch ($form['type']) {
                    case TnAgenciaProfileType::class:
                    case 'user_agencia_profile':
                        $form_profile = TnAgenciaProfileType::class;
                        break;
                    case TnProfileType::class:
                    case 'user_cliente_profile':
                    default:
                        $form_profile = TnProfileType::class;
                        break;
                }

        }

//        $form_request = $request->request->get('fos_user_registration_form');
        $factory = $builder->getFormFactory();
        $translator = $this->container->get('translator');
        // add your custom field
        $builder
            ->add('email', EmailType::class, array('translation_domain' => 'FOSUserBundle',
                'attr' => array('placeholder' => 'form.email'),
                'required' => true,
                'label' => 'form.email'
                //'trim'=>true
            ))
            ->add('username', null, array(
                'label' => 'form.username', 'translation_domain' => 'FOSUserBundle',
                'attr' => array('placeholder' => 'form.username'),
                'required' => true,
                //'trim'=>true
            ))
            ->add('plainPassword', RepeatedType::class, array(
                'type' => PasswordType::class,
                'label' => 'Password', 'translation_domain' => 'FOSUserBundle',
                //'invalid_message' => 'fos_user.password.invalid',
                'first_options' => array('label' => 'form.password',
                    'attr' => array('placeholder' => 'form.password.title', 'title' => 'form.password.title')
                ),
                'second_options' => array('invalid_message' => 'fos_user.password.mismatch', 'label' => 'form.password_confirmation', 'attr' => array('placeholder' => 'form.password_confirmation')),
            ))->add('profile', $form_profile, array(
                'cascade_validation' => true,
                'validation_groups' => array('Default', 'Registration')
            ))
            ->add('type', HiddenType::class, array(
                'mapped' => false,
                'required' => false,
                'data' => $form_profile == TnAgenciaProfileType::class?'user_agencia_profile':'user_cliente_profile'
            ))
            ->add('terms', CheckboxType::class, array('translation_domain' => 'FOSUserBundle',
                'mapped' => false,
                'required' => true,
                'label' => 'form.terms&condition'
            ));

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($factory) {
                $form = $event->getForm();
                $data = $event->getData();
                $em = $this->container->get('doctrine.orm.entity_manager');
                if ($em->getFilters()->isEnabled('softdeleteable')) {
                    $filter = $em->getFilters()->getFilter('softdeleteable');
                    if ($filter instanceof SoftDeleteableFilter)
                        $filter->disableForEntity('App\Entity\TnUser');
                }

            }
        );

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($factory) {
                $form = $event->getForm();
                $data = $event->getData();
                $pass = $form->get('plainPassword')->getData();

                if (!preg_match('/^(?=.*\d)(?=.*[a-z])(?=.*[^\w])(?=.*[A-Z]).{6,20}$/', $pass)) {
                    $tranlator = $this->container->get('translator');

                    $form->get('plainPassword')->get('first')->addError(new FormError(
                        $tranlator->trans('form.password.invalid', array(), 'FOSUserBundle')
                    ));
                }
            }
        );
    }

    public function getName()
    {
        return 'user_registration';
    }
}