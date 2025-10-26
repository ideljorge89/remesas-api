<?php

namespace App\Controller\Backend\Nomencladores;

use App\Entity\NmProvincia;
use App\Form\NmProvinciaType;
use App\Repository\NmProvinciaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/backend/nm/provincia")
 */
class NmProvinciaController extends AbstractController
{
    /**
     * @Route("/", name="nm_provincia_index", methods={"GET"})
     */
    public function index(NmProvinciaRepository $nmProvinciaRepository): Response
    {
        return $this->render('backend/nm_provincia/index.html.twig', [
            'nm_provincias' => $nmProvinciaRepository->findAll(),
        ]);
    }

    /**
     * @Route("/{id}/edit", name="nm_provincia_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, NmProvincia $nmProvincia): Response
    {
        $form = $this->createForm(NmProvinciaType::class, $nmProvincia);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('nm_provincia_index');
        }

        return $this->render('backend/nm_provincia/edit.html.twig', [
            'nm_provincia' => $nmProvincia,
            'form' => $form->createView(),
        ]);
    }
}
