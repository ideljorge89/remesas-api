<?php

namespace App\Controller;


use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        return $this->redirectToRoute('homepage_backend');
    }

    /**
     * @Route("/change_language/{_locale}", name="app_default_change_language",
     * requirements={"_locale":"en|es"})
     * @Method("GET")
     */
    public function changeLanguage(Request $request, $_locale)
    {

        $locale = $request->getLocale();
        if ($_locale)
            $locale = $_locale;

        $locales = $this->getParameter('global_params');
        //si esta el locale
        if (array_key_exists($locale, $locales['locales'])) {
            $this->get('session')->set('_locale', $locale);
        }

        try {
            $referer = $request->headers->get('referer');
            $lastPath = substr($referer, strpos($referer, $request->getBaseUrl()));
            $path = str_replace($request->getBaseUrl(), '', $lastPath);

            $matcher = $this->get('router')->getMatcher();
            $parameters = $matcher->match($path);

            $ruta = $parameters['_route'];
            return $this->redirectToRoute($ruta, $parameters, 302);

        } catch (MethodNotAllowedException $exc) {

        } catch (ResourceNotFoundException $exc) {

        } catch (\Exception $exc) {

        }

        return $this->redirect($request->headers->get('referer') ? $request->headers->get('referer') : $this->generateUrl('homepage'));
    }
}
