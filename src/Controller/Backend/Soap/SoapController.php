<?php
/**
 * Simple SOAP Server - v1
 *
 * Create a simple HelloWorld SOAP server. The main function (hello) is defined in HelloController
 * which is a basic Controller. To publish a method of HelloController we've created this controller
 * (SoapController) where we have one + two methods :
 *  - First one  : how to publish all public methods of HelloController
 *  - Second one : how to generate the WSDL of the service
 *  - Third one  : how to call a method
 *
 * If you have a new controller to publish simply duplicate the first method and update these data :
 *  - The route and it's name
 *  - The url
 *  - The instance of the new controller
 */

namespace App\Controller\Backend\Soap;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

// Needed to generate absolute URL
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

// Needed to create the SOAP server
use Laminas\Soap;
use Laminas\Soap\Server\DocumentLiteralWrapper;

/**
 * @Route("/backend")
 */
class SoapController extends AbstractController
{
    protected $logger;
    protected $c;
    protected $wsdl_file;

    public function __construct(ContainerInterface $c, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->c = $c;
        $this->wsdl_file = $this->c->getParameter('kernel.project_dir') . '/data/qwc.wsdl';
    }

    /**
     *
     * @Route("/soap/qwc", name="soap_qwc")
     */
    public function qwc()
    {
        // This wil generate the absolute URL of the current action (end point) based on it's route name
        $theUri = $this->generateUrl('soap_qwc', [], UrlGeneratorInterface::ABSOLUTE_URL);
        // This is the object to instanciate when the webservice is invoked, use any controller
        $theService = new QWCController($this->container, $this->logger);
        $method = $this->get('request_stack')->getMasterRequest()->getMethod();
        $request = $this->get('request_stack')->getMasterRequest();
        // Check if we should disply WSDL or execute the call
        if ($method == "POST")
            return $this->handleSOAP($theUri, $theService);
        elseif ($request->query->has('wsdl') || $request->query->has('WSDL'))
            return $this->handleWSDL($theUri, $theService);
        else {
            return new Response("");
        }
    }

    /**
     * return the WSDL
     */
    public function handleWSDL($uri, $class)
    {
        // Soap auto discover
        $autodiscover = new Soap\AutoDiscover();
        $autodiscover->setClass($class);
        $autodiscover->setUri($uri);
        $autodiscover->setBindingStyle([
            'style' => 'document'
        ]);
        $autodiscover->setOperationBodyStyle([
            'use' => 'literal'
        ]);

        // Response
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml; charset=UTF-8'); // WSDL is a XML content

        // Start Output Buffering, nothing will be displayed ...
        ob_start();
        // Handle Soap
        $autodiscover->handle();
        if (!is_file($this->wsdl_file)) {
            $autodiscover->dump($this->wsdl_file);
        }
        // ... Stop Output Buffering and get content into variable
        $response->setContent(ob_get_clean());
        return $response;
    }

    /**
     * execute SOAP request
     */
    public function handleSOAP($uri, $class)
    {
        // Soap server
        $soap = new Soap\Server(is_file($this->wsdl_file) ? $this->wsdl_file : null, [
            'location' => $uri,
            'uri' => $uri
        ]);
        //$soap->setClass($class);
        $soap->setClass(new DocumentLiteralWrapper($class));//'http://developer.intuit.com/'
        //$soap->setObject(new DocumentLiteralWrapper($class));
        //$soap->setDebugMode(true);
        $soap->setOptions([
            //'wsdl' => $uri."?wsdl=",
            'cache_wsdl' => WSDL_CACHE_NONE
        ]);

        // Response
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml; charset=ISO-8859-1');

        ob_start();
        // Handle Soap
        $soap->handle();

        $response->setContent(ob_get_clean());
        return $response;
    }
}
