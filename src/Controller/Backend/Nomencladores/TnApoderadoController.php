<?php

namespace App\Controller\Backend\Nomencladores;

use App\Entity\TnApoderado;
use App\Form\TnApoderadoType;
use App\Repository\TnApoderadoRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/super/nom/tn/apoderado")
 */
class TnApoderadoController extends AbstractController
{
    /**
     * @Route("/", name="tn_apoderado_index", methods={"GET"})
     */
    public function index(TnApoderadoRepository $tnApoderadoRepository): Response
    {
        return $this->render('backend/tn_apoderado/index.html.twig', [
            'tn_apoderados' => $tnApoderadoRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="tn_apoderado_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $tnApoderado = new TnApoderado();
        $form = $this->createForm(TnApoderadoType::class, $tnApoderado);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $tnApoderado->setToken((sha1(uniqid())));
            $entityManager->persist($tnApoderado);
            $entityManager->flush();

            return $this->redirectToRoute('tn_apoderado_index');
        }

        return $this->render('backend/tn_apoderado/new.html.twig', [
            'tn_apoderado' => $tnApoderado,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{token}/edit", name="tn_apoderado_edit", methods={"GET","POST"})
     * @ParamConverter("tnApoderado", options={"mapping": {"token": "token"}})
     */
    public function edit(Request $request, TnApoderado $tnApoderado): Response
    {
        $form = $this->createForm(TnApoderadoType::class, $tnApoderado);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('tn_apoderado_index');
        }

        return $this->render('backend/tn_apoderado/edit.html.twig', [
            'tn_apoderado' => $tnApoderado,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="tn_apoderado_delete", methods={"DELETE"})
     */
    public function delete(Request $request, TnApoderado $tnApoderado): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tnApoderado->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($tnApoderado);
            $entityManager->flush();
        }

        return $this->redirectToRoute('tn_apoderado_index');
    }
}
