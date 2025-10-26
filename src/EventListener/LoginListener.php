<?php

namespace App\EventListener;

use App\Session\Security\Exceptions\ReCaptchaLoginException;
use EWZ\Bundle\RecaptchaBundle\Form\Type\EWZRecaptchaType;
use EWZ\Bundle\RecaptchaBundle\Validator\Constraints\IsTrue;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Authentication\Token\JWTUserToken;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Guard\JWTTokenAuthenticator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\SecurityEvents;

class LoginListener implements EventSubscriberInterface
{
    private $em, $session, $usuario, $container, $redireccionar;

    public function __construct(EntityManagerInterface $em, SessionInterface $session, ContainerInterface $container)
    {
        $this->em = $em;
        $this->session = $session;
        $this->container = $container;
        $this->redireccionar = '';
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $loginEvent)
    {

        $token = $loginEvent->getAuthenticationToken();

        $route = $this->container->get('request_stack')->getMasterRequest()->attributes->get('_route');

        if ($token instanceof UsernamePasswordToken && $route!="api_login") {
            $autorization = $this->container->get('security.authorization_checker');
            $this->usuario = $this->em->getRepository("App:TnUser")->findOneBy(array('username' => $token->getUsername()));

            if (isset($this->usuario)) {
                if ($autorization->isGranted('ROLE_SUPER_ADMIN') || $autorization->isGranted('ROLE_AGENCIA') || $autorization->isGranted('ROLE_AGENTE')) {
                    $this->redireccionar = 'tn_factura_index';
                } elseif ($autorization->isGranted('ROLE_DISTRIBUIDOR')) {
                    $this->redireccionar = 'entrega_distribuidor_index';
                }
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::RESPONSE => 'manejarRespuesta',
            SecurityEvents::INTERACTIVE_LOGIN => 'onSecurityInteractiveLogin'
        );
    }

    public function manejarRespuesta(ResponseEvent $filterResponseEvent)
    {
        if ($this->redireccionar !== '') {
            $filterResponseEvent->setResponse(new RedirectResponse(($this->container->get('router')->generate($this->redireccionar))));
        }
    }
}