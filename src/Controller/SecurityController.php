<?php

namespace App\Controller;

use EWZ\Bundle\RecaptchaBundle\Form\Type\EWZRecaptchaType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use FOS\UserBundle\Controller\SecurityController as BaseController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Validator\Constraints\IsTrue;

/**
 * @Route("/backend")
 * @Route("/")
 *
 * Class SecurityController
 * @package App\Controller
 */
class SecurityController extends BaseController
{
    /**
     * @Route("/login")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function loginAction(Request $request)
    {
        /** @var $session \Symfony\Component\HttpFoundation\Session\Session */
        $session = $request->getSession();

        $authErrorKey = Security::AUTHENTICATION_ERROR;
        $lastUsernameKey = Security::LAST_USERNAME;


        // get the error if any (works with forward and redirect -- see below)
        if ($request->attributes->has($authErrorKey)) {
            $error = $request->attributes->get($authErrorKey);
        } elseif (null !== $session && $session->has($authErrorKey)) {
            $error = $session->get($authErrorKey);
            $session->remove($authErrorKey);
        } else {
            $error = null;
        }

        if (!$error instanceof AuthenticationException) {
            $error = null; // The value does not come from the security component.
        }

        // last username entered by the user
        $lastUsername = (null === $session) ? '' : $session->get($lastUsernameKey);

        if ($this->has('security.csrf.token_manager')) {
            $csrfToken = $this->get('security.csrf.token_manager')->getToken('authenticate')->getValue();
        } else {
            // BC for SF < 2.4
            $csrfToken = $this->has('form.csrf_provider')
                ? $this->get('form.csrf_provider')->generateCsrfToken('authenticate')
                : null;
        }

//        $recaptcha = $this->createForm(EWZRecaptchaType::class, null, array(
//            'attr' => array(
//                'options' => array(
//                    'theme' => 'clean',
//                    'size' => 'normal',              // set size to invisible
//                    'type' => 'image',
//                    'defer' => false,
//                    'async' => false,
//                )
//            ),
//            'mapped' => false,
//            'required' => true,
//            'constraints' => array(
//                new IsTrue()
//            )
//        ));

        return $this->renderLogin(array(
            'last_username' => $lastUsername,
            'error' => $error,
            'csrf_token' => $csrfToken,
//            'recaptcha' => $recaptcha->createView()
        ));
    }

    public function renderLogin(array $data)
    {
        $requestAttributes = $this->container->get('request_stack')->getCurrentRequest()->attributes;
        if ($requestAttributes->get('_route') == 'app_security_login') {
            $template = sprintf('@FOSUser\Security\admin_login.html.twig');
        } else {
            $template = sprintf('@FOSUser\Security\login.html.twig');
            $template = sprintf('@FOSUser\Security\login.html.twig');
        }
        return $this->container->get('templating')->renderResponse($template, $data);
    }

}