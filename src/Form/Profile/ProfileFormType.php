<?php

namespace App\Form\Profile;

use App\Entity\TnUser;
use Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\File\File;
use FOS\UserBundle\Form\Type\ProfileFormType as BaseType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

class ProfileFormType extends BaseType
{
    private $container;
    private $security;
    private $username;
    private $email;

    private $webRoot;
    private $translator;

    public function __construct($user, Container $container, $web_dir)
    {
        parent::__construct($user);
        $this->container = $container;
        $this->security = $container->get('security.authorization_checker');
        $this->webRoot = realpath($web_dir);
        $this->translator = $this->container->get('translator');
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $factory = $builder->getFormFactory();
        $builder
            ->add('email', EmailType::class, array('translation_domain' => 'FOSUserBundle',
                'attr' => array('placeholder' => 'form.email'),
                'required' => true,
                'label' => 'form.email',
                'trim' => true
            ))
            ->add('email', EmailType::class, array('translation_domain' => 'FOSUserBundle',
                'attr' => array('placeholder' => 'form.email'),
                'required' => true,
                'label' => 'form.email',
                'trim' => true
            ))
            ->add('avatar')
            ->add('filePath', HiddenType::class);

        if (!$this->security->isGranted("ROLE_DISTRIBUIDOR")) {
            $builder->add('porcentaje', NumberType::class, array(
                'translation_domain' => 'AppBundle',
                'label' => 'backend.grupo_pago.form.fields.porciento',
                'required' => true,
                'attr' => [
                    'class' => 'money',
                    'data-rule-number' => true,
                    'data-rule-minLength' => 1,
                    'data-rule-maxLength' => 5
                ]
            ));
        }
        if ($this->security->isGranted("ROLE_AGENCIA") || $this->security->isGranted("ROLE_AGENTE")){
            $builder->add('auth', CheckboxType::class, array(
                'required' => true
            ));
        }

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($factory) {
                $form = $event->getForm();
                $data = $event->getData();
                if ($this->security->isGranted('ROLE_SUPERADMIN') || $this->security->isGranted('ROLE_AGENCIA') || $this->security->isGranted('ROLE_AGENTE')) {
                    $form->remove('profile');
                }
                $em = $this->container->get('doctrine.orm.entity_manager');
                if ($em->getFilters()->isEnabled('softdeleteable')) {
                    $filter = $em->getFilters()->getFilter('softdeleteable');
                    if ($filter instanceof SoftDeleteableFilter)
                        $filter->disableForEntity('AppBundle\Entity\TnUser');
                }

            }
        );
        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($factory) {
                $form = $event->getForm();
                $data = $event->getData();

                //Verificando el porcentaje que pongan valor
                if (!$this->security->isGranted('ROLE_DISTRIBUIDOR')) {
                    if ($form->getData()->getPorcentaje() == null) {
                        $form->get('porcentaje')->addError(new FormError('Debe especificar el porciento con el que va a operar, no debe ser vacío ni 0.'));
                    }
                }

                $dir = $this->webRoot . '/' . $data->getFilePath();

                if ($data->getFilePath() != '' && file_exists($dir)) {
                    $b64 = $this->container->get('app.twig.extension.base_64');
                    $default = $this->webRoot . '/assets/backend/img/no-logo.jpg';
                    $image = $b64->toBase64Filter($dir, $default);
                    $data->setAvatar($image);

                }

            }
        );


    }

    public function getName()
    {
        return 'user_edit';
    }


    public function valid_twitter_url($field)
    {
        if (!preg_match('/^(https?:\/\/)?((w{3}\.)?)twitter\.com\/(#!\/)?[a-z0-9_]+$/i', $field))
            return false;
        return true;
    }

    public function valid_google_url($field)
    {
        if (!preg_match('/^(http\:\/\/|https\:\/\/)?(?:www\.)?google\.com\/*/', $field))
            return false;
        return true;
    }

    protected function valid_facebook_url($field)
    {
        if (!preg_match('/^(http\:\/\/|https\:\/\/)?(?:www\.)?facebook\.com\/(?:(?:\w\.)*#!\/)?(?:pages\/)?(?:[\w\-\.]*\/)*([\w\-\.]*)/', $field)) {
            return false;
        }
        return true;
    }
}