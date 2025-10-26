<?php

namespace App\Controller\Backend\Nomencladores;

use App\Entity\NmGrupoPago;
use App\Entity\TnUser;
use App\Form\NmGrupoPagoType;
use App\Repository\NmGrupoPagoRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @Route("/super/nom/grupo/pago")
 */
class NmGrupoPagoController extends AbstractController
{
    /**
     * @Route("/", name="nm_grupo_pago_index", methods={"GET"})
     */
    public function index(NmGrupoPagoRepository $nmGrupoPagoRepository, AuthorizationCheckerInterface $authorizationChecker): Response
    {
        $tnUser = $this->getUser();
        if ($tnUser instanceof TnUser) {
            if ($authorizationChecker->isGranted("ROLE_AGENCIA")) {
                $query = $nmGrupoPagoRepository->createQueryBuilder('f')
                    ->where('f.agencia = :ag')
                    ->setParameter('ag', $tnUser->getAgencia())
                    ->orderBy('f.created', "DESC")
                    ->getQuery();
            } else {
                $query = $nmGrupoPagoRepository->createQueryBuilder('f')->orderBy('f.created', "DESC")->getQuery();
            }
        }

        return $this->render('backend/nm_grupo_pago/index.html.twig', [
            'nm_grupo_pagos' => $query->getResult(),
        ]);
    }

    /**
     * @Route("/new", name="nm_grupo_pago_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $nmGrupoPago = new NmGrupoPago();
        $form = $this->createForm(NmGrupoPagoType::class, $nmGrupoPago);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $nmGrupoPago->setToken((sha1(uniqid())));

            $entityManager->persist($nmGrupoPago);
            $entityManager->flush();

            return $this->redirectToRoute('nm_grupo_pago_index');
        }

        return $this->render('backend/nm_grupo_pago/new.html.twig', [
            'nm_grupo_pago' => $nmGrupoPago,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="nm_grupo_pago_show", methods={"GET"})
     */
    public function show(NmGrupoPago $nmGrupoPago): Response
    {
        return $this->render('backend/nm_grupo_pago/show.html.twig', [
            'nm_grupo_pago' => $nmGrupoPago,
        ]);
    }

    /**
     * @Route("/{token}/edit", name="nm_grupo_pago_edit", methods={"GET","POST"})
     * @ParamConverter("nmGrupoPago", options={"mapping": {"token": "token"}})
     */
    public function edit(Request $request, NmGrupoPago $nmGrupoPago): Response
    {
        $form = $this->createForm(NmGrupoPagoType::class, $nmGrupoPago);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('nm_grupo_pago_index');
        }

        return $this->render('backend/nm_grupo_pago/edit.html.twig', [
            'nm_grupo_pago' => $nmGrupoPago,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="nm_grupo_pago_delete", methods={"DELETE"})
     */
    public function delete(Request $request, NmGrupoPago $nmGrupoPago): Response
    {
        if ($this->isCsrfTokenValid('delete'.$nmGrupoPago->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($nmGrupoPago);
            $entityManager->flush();
        }

        return $this->redirectToRoute('nm_grupo_pago_index');
    }
}
