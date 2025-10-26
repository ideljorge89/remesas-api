<?php

namespace App\Filter\Configurator;

use App\Entity\NmCompania;
use App\Manager\ReservaManager;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Annotations\Reader;

class CompanyNameConfigurator
{
    protected $router;
    protected $em;
    protected $tokenStorage;
    protected $reader;
    protected $manager;
    private $array_routes;

    public function __construct(Router $router, EntityManager $em, TokenStorageInterface $tokenStorage, Reader $reader, ReservaManager $reservaManager)
    {
        $this->router = $router;
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
        $this->reader = $reader;
        $this->manager = $reservaManager;
        $this->array_routes = array(
            '/cars/',
            '/cars/details/',
            '/cars/select/',
            '/offices',
            '/find-result'
        );
    }

    public function onKernelRequest()
    {
        if (!(
            $this->getUser() != null &&
            (
                $this->getUser()->hasRole("ROLE_COORDINADOR")
                || $this->getUser()->hasRole("ROLE_FACTURADOR")
                || $this->getUser()->hasRole("ROLE_ADMIN")
                || $this->getUser()->hasRole("ROLE_SUPER_ADMIN")
            )
        )) {
            $param_route = $this->router->getContext()->getPathInfo();
            if ($this->manager->isFromCountry("US") && $this->compareRoute($param_route)) {
                $filter = $this->em->getFilters()->enable('not_company_filter');
                $filter->setParameter('nombre', NmCompania::CUBACAR_NAME);
                $filter->setAnnotationReader($this->reader);
            }
        }
    }

    private function compareRoute($route)
    {
        if ($route == '/') {
            return true;
        } else {
            foreach ($this->array_routes as $item) {
                if (strpos($route, $item) !== false) {
                    return true;
                }
            }
            return false;
        }
    }

    private function getUser()
    {
        $token = $this->tokenStorage->getToken();

        if (!$token) {
            return null;
        }

        $user = $token->getUser();

        if (!($user instanceof UserInterface)) {
            return null;
        }

        return $user;
    }
}
