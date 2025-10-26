<?php


namespace App\Form\ChangePassword;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormError;


class ChangePasswordFormType extends AbstractType
{
    private $class;
    private $container;

    /**
     * @param string $class The User class name
     */
    public function __construct($class, Container $container)
    {
        $this->class = $class;
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $constraint = new UserPassword();

        $builder->add('current_password', PasswordType::class, array(
            'label' => 'form.current_password',
            'translation_domain' => 'FOSUserBundle',
            'mapped' => false,
            'constraints' => $constraint,
        ));
        $builder->add('plainPassword', RepeatedType::class, array(
            'type' => PasswordType::class,
            'options' => array('translation_domain' => 'FOSUserBundle'),
            'first_options' => array('label' => 'form.new_password'),
            'second_options' => array('invalid_message' => 'fos_user.password.mismatch', 'label' => 'form.new_password_confirmation'),
            'invalid_message' => 'fos_user.password.invalid',
            'mapped' => false
        ));
        $factory = $builder->getFormFactory();
        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($factory) {
                $form = $event->getForm();
                $data = $event->getData();
                $pass = $form->get('plainPassword')->getData();

                if (!preg_match('/^(?=.*\d)(?=.*[a-z])(?=.*[^\w])(?=.*[A-Z]).{6,20}$/', $pass)) {

                    $tranlator = $this->container->get('translator');

                    $form->get('plainPassword')->get('first')->addError(new FormError(
                        $tranlator->trans('change_password.password.invalid', array(), 'FOSUserBundle')
                    ));
                }
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => $this->class,
            'intention' => 'change_password',
        ));
    }

    // BC for SF < 2.7
    public function setDefaultOptions(OptionsResolver $resolver)
    {
        $this->configureOptions($resolver);
    }

    public function getName()
    {
        return 'user_change_password';
    }
}
