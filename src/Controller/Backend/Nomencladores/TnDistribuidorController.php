<?php

namespace App\Controller\Backend\Nomencladores;

use App\Entity\TnDistribuidor;
use App\Form\TnDistribuidorType;
use App\Repository\TnDistribuidorRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/super/nom/tn/distribuidor")
 */
class TnDistribuidorController extends AbstractController
{
    /**
     * @Route("/", name="tn_distribuidor_index", methods={"GET"})
     */
    public function index(TnDistribuidorRepository $tnDistribuidorRepository): Response
    {
        return $this->render('backend/tn_distribuidor/index.html.twig', [
            'tn_distribuidors' => $tnDistribuidorRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="tn_distribuidor_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $tnDistribuidor = new TnDistribuidor();
        $form = $this->createForm(TnDistribuidorType::class, $tnDistribuidor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $tnDistribuidor->setToken((sha1(uniqid())));
            $entityManager->persist($tnDistribuidor);
            $entityManager->flush();

            return $this->redirectToRoute('tn_distribuidor_index');
        }

        return $this->render('backend/tn_distribuidor/new.html.twig', [
            'distribuidor' => $tnDistribuidor,
            'form' => $form->createView(),
        ]);
    }


    /**
     * @Route("/{token}/edit", name="tn_distribuidor_edit", methods={"GET","POST"})
     * @ParamConverter("tnDistribuidor", options={"mapping": {"token": "token"}})
     */
    public function edit(Request $request, TnDistribuidor $tnDistribuidor): Response
    {
        $form = $this->createForm(TnDistribuidorType::class, $tnDistribuidor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('tn_distribuidor_index');
        }

        return $this->render('backend/tn_distribuidor/edit.html.twig', [
            'distribuidor' => $tnDistribuidor,
            'form' => $form->createView(),
        ]);
    }
}
