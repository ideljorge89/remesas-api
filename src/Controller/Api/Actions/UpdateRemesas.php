<?php

namespace App\Controller\Api\Actions;

use ApiPlatform\Core\Validator\ValidatorInterface;
use App\Model\Api\RemesaUpdate;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class UpdateRemesas
{

    protected $container;
    protected $facturaManager;
    protected $security;
    protected $user;


    public function __construct(ContainerInterface $container, ValidatorInterface $validator)
    {
        $this->container = $container;
        $this->facturaManager = $this->container->get('factura_manager');
        $this->security = $this->container->get('security.authorization_checker');
        $this->validator = $validator;
    }

    /**
     * @Route(
     *     name="search_remesas_update",
     *     path="/api/search-remesas-update",
     *     methods={"POST"},
     *     schemes={"https"},
     *     defaults={
     *         "_api_resource_class"=RemesaUpdate::class,
     *         "_api_collection_operation_name"="search_remesas_update"
     *     },
     * )
     */
    public function __invoke($data): JsonResponse
    {
        $this->validator->validate($data, ['groups' => 'register']);

        $this->user = $this->container->get('security.token_storage')->getToken()->getUser();

        $response = $this->facturaManager->updateRemesas($this->user, $data);

        return new JsonResponse($response);
    }
}