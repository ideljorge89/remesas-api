<?php

namespace App\Controller\Backend\Nomencladores;

use App\Entity\TnListaNegra;
use App\Form\TnListaNegraType;
use App\Repository\TnListaNegraRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/super/nom/lista/negra")
 */
class TnListaNegraController extends AbstractController
{
    /**
     * @Route("/", name="tn_lista_negra_index", methods={"GET"})
     */
    public function index(TnListaNegraRepository $tnListaNegraRepository, PaginatorInterface $paginator): Response
    {
        $query = $tnListaNegraRepository->createQueryBuilder('ln')->orderBy('ln.created', "DESC")->getQuery();

        $request = $this->get('request_stack')->getCurrentRequest();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            100
        );
        return $this->render('backend/tn_lista_negra/index.html.twig', [
            'tn_lista_negras' => $pagination,
        ]);
    }

    /**
     * @Route("/new", name="tn_lista_negra_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $tnListaNegra = new TnListaNegra();
        $form = $this->createForm(TnListaNegraType::class, $tnListaNegra);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($tnListaNegra);
            $entityManager->flush();

            return $this->redirectToRoute('tn_lista_negra_index');
        }

        return $this->render('backend/tn_lista_negra/new.html.twig', [
            'tn_lista_negra' => $tnListaNegra,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="tn_lista_negra_show", methods={"GET"})
     */
    public function show(TnListaNegra $tnListaNegra): Response
    {
        return $this->render('backend/tn_lista_negra/show.html.twig', [
            'tn_lista_negra' => $tnListaNegra,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="tn_lista_negra_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, TnListaNegra $tnListaNegra): Response
    {
        $form = $this->createForm(TnListaNegraType::class, $tnListaNegra);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('tn_lista_negra_index');
        }

        return $this->render('backend/tn_lista_negra/edit.html.twig', [
            'tn_lista_negra' => $tnListaNegra,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="tn_lista_negra_delete", methods={"DELETE"})
     */
    public function delete(Request $request, TnListaNegra $tnListaNegra): Response
    {
        if ($this->isCsrfTokenValid('delete' . $tnListaNegra->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($tnListaNegra);
            $entityManager->flush();
        }

        return $this->redirectToRoute('tn_lista_negra_index');
    }
}
