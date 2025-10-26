<?php

namespace App\Controller\Backend\Nomencladores;

use App\Entity\Moneda;
use App\Entity\NmMoneda;
use App\Form\MonedaType;
use App\Form\NmMonedaType;
use App\Repository\MonedaRepository;
use App\Repository\NmMonedaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/super/nom/nm/moneda")
 */
class NmMonedaController extends AbstractController
{
    /**
     * @Route("/", name="moneda_index", methods={"GET"})
     */
    public function index(NmMonedaRepository $monedaRepository): Response
    {
        return $this->render('backend/nmmoneda/index.html.twig', [
            'monedas' => $monedaRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="moneda_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $moneda = new NmMoneda();
        $form = $this->createForm(NmMonedaType::class, $moneda);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($moneda);
            $entityManager->flush();

            return $this->redirectToRoute('moneda_index');
        }

        return $this->render('backend/nmmoneda/new.html.twig', [
            'moneda' => $moneda,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="moneda_show", methods={"GET"})
     */
    public function show(NmMoneda $moneda): Response
    {
        return $this->render('backend/nmmoneda/show.html.twig', [
            'moneda' => $moneda,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="moneda_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, NmMoneda $moneda): Response
    {
        $form = $this->createForm(NmMonedaType::class, $moneda);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('moneda_index');
        }

        return $this->render('backend/nmmoneda/edit.html.twig', [
            'moneda' => $moneda,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="moneda_delete", methods={"DELETE"})
     */
    public function delete(Request $request, NmMoneda $moneda): Response
    {
        if ($this->isCsrfTokenValid('delete'.$moneda->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($moneda);
            $entityManager->flush();
        }

        return $this->redirectToRoute('moneda_index');
    }
}
