<?php

/*
 * This file is part of the Login Recaptcha Bundle.
 *
 * (c) Gabriel Caruana <gabb1995@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace App\Session\Security;

use App\Session\Security\Exceptions\ReCaptchaLoginException;
use EWZ\Bundle\RecaptchaBundle\Form\Type\EWZRecaptchaType;
use EWZ\Bundle\RecaptchaBundle\Validator\Constraints\IsTrue;
use EWZ\Bundle\RecaptchaBundle\Validator\Constraints\IsTrueValidator;
use Psr\Log\LoggerInterface;
use ReCaptcha\ReCaptcha;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Firewall\AbstractAuthenticationListener;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;
use Symfony\Component\Security\Http\Firewall\UsernamePasswordFormAuthenticationListener;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\ParameterBagUtils;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategyInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;


/**
 * Class CaptchaFormAuthenticationListener.
 */
class CaptchaFormAuthenticationListener
{
    /**
     * @var EWZRecaptchaType
     */
    private $type;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var UsernamePasswordFormAuthenticationListener
     */
    private $authenticationListener;

    /**
     * @var HttpUtils
     */
    protected $httpUtils;

    /**
     * @bool
     */
    private $captcha_enabled;

    public function __construct(
        EWZRecaptchaType $type,
        UsernamePasswordFormAuthenticationListener $authenticationListener,
        ContainerInterface $container,
        $enable
    )
    {
        $this->captcha_enabled = $enable;

        $this->container = $container;
        $this->authenticationListener = $authenticationListener;
    }

    public function __invoke(RequestEvent $event)
    {
        $this->verifyRecaptcha($event);
        $this->authenticationListener->__invoke($event);
    }

    public function verifyRecaptcha(RequestEvent $event)
    {
        if ($this->captcha_enabled){
            if (!in_array($event->getRequest()->attributes->get('_route'), array('fos_user_backend_security_check', 'fos_user_security_check'))) {
                return;
            }

            $request = $this->container->get('request_stack')->getMasterRequest();

            if ($request->getMethod() == "POST") {

                $form = $this->container->get('form.factory')->create(EWZRecaptchaType::class, null, array(
                    'attr' => array(
                        'options' => array(
                            'theme' => 'clean',
                            'size' => 'normal',              // set size to invisible
                            'type' => 'image',
                            'defer' => false,
                            'async' => false
                        )
                    ),
                    'mapped' => false,
                    'required' => true,
                    'constraints' => array(
                        new IsTrue()
                    )
                ));

                $form->submit(
                    $request->request->get('g-recaptcha-response')
                );

                if (!$form->isValid()) {
                    $request->getSession()->set(Security::AUTHENTICATION_ERROR, new ReCaptchaLoginException());
                    throw new ReCaptchaLoginException();
                } else {
                    $request->getSession()->remove(Security::AUTHENTICATION_ERROR);
                }
            }
        }

    }
}
