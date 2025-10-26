<?php

namespace App\Controller\Api\Actions;

use ApiPlatform\Core\Validator\ValidatorInterface;
use App\Model\Api\Transferencia;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class CreateTransferencias
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
     *     name="new_transferencias_bulk",
     *     path="/api/transferencias-bulk",
     *     methods={"POST"},
     *     schemes={"https"},
     *     defaults={
     *         "_api_resource_class"=Transferencia::class,
     *         "_api_collection_operation_name"="new_transferencias_bulk"
     *     },
     * )
     */
    public function __invoke($data): JsonResponse
    {
        $this->validator->validate($data, ['groups' => 'register']);

        $this->user = $this->container->get('security.token_storage')->getToken()->getUser();

        $response = $this->facturaManager->newTransferencias($this->user, $data);

        return new JsonResponse($response);
    }
}