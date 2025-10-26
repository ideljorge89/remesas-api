<?php

namespace App\Controller\Backend\Nomencladores;

use App\Entity\NmGrupoPagoTransf;
use App\Entity\TnUser;
use App\Form\NmGrupoPagoTransfType;
use App\Repository\NmGrupoPagoTransfRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @Route("/nm/grupo/pago/transf")
 */
class NmGrupoPagoTransfController extends AbstractController
{
    /**
     * @Route("/", name="nm_grupo_pago_transf_index", methods={"GET"})
     */
    public function index(NmGrupoPagoTransfRepository $nmGrupoPagoTransfRepository, AuthorizationCheckerInterface $authorizationChecker): Response
    {
        $tnUser = $this->getUser();
        if ($tnUser instanceof TnUser) {
            if ($authorizationChecker->isGranted("ROLE_AGENCIA")) {
                $query = $nmGrupoPagoTransfRepository->createQueryBuilder('gp')
                    ->join('gp.agencias', 'agencia')
                    ->where('agencia.id = :ag')
                    ->setParameter('ag', $tnUser->getAgencia())
                    ->orderBy('f.created', "DESC")
                    ->getQuery();
            } else {
                $query = $nmGrupoPagoTransfRepository->createQueryBuilder('f')->orderBy('f.created', "DESC")->getQuery();
            }
        }

        return $this->render('backend/nm_grupo_pago_transf/index.html.twig', [
            'nm_grupo_pago_transfs' => $query->getResult(),
        ]);
    }

    /**
     * @Route("/new", name="nm_grupo_pago_transf_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $nmGrupoPagoTransf = new NmGrupoPagoTransf();
        $form = $this->createForm(NmGrupoPagoTransfType::class, $nmGrupoPagoTransf);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $nmGrupoPagoTransf->setToken((sha1(uniqid())));
            $entityManager->persist($nmGrupoPagoTransf);
            $entityManager->flush();


            return $this->redirectToRoute('nm_grupo_pago_transf_index');
        }

        return $this->render('backend/nm_grupo_pago_transf/new.html.twig', [
            'nm_grupo_pago_transf' => $nmGrupoPagoTransf,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="nm_grupo_pago_transf_show", methods={"GET"})
     */
    public function show(NmGrupoPagoTransf $nmGrupoPagoTransf): Response
    {
        return $this->render('backend/nm_grupo_pago_transf/show.html.twig', [
            'nm_grupo_pago_transf' => $nmGrupoPagoTransf,
        ]);
    }

    /**
     * @Route("/{token}/edit", name="nm_grupo_pago_transf_edit", methods={"GET","POST"})
     * @ParamConverter("nmGrupoPagoTransf", options={"mapping": {"token": "token"}})
     */
    public function edit(Request $request, NmGrupoPagoTransf $nmGrupoPagoTransf): Response
    {
        $form = $this->createForm(NmGrupoPagoTransfType::class, $nmGrupoPagoTransf);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('nm_grupo_pago_transf_index');
        }

        return $this->render('backend/nm_grupo_pago_transf/edit.html.twig', [
            'nm_grupo_pago_transf' => $nmGrupoPagoTransf,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="nm_grupo_pago_transf_delete", methods={"DELETE"})
     */
    public function delete(Request $request, NmGrupoPagoTransf $nmGrupoPagoTransf): Response
    {
        if ($this->isCsrfTokenValid('delete' . $nmGrupoPagoTransf->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($nmGrupoPagoTransf);
            $entityManager->flush();
        }

        return $this->redirectToRoute('nm_grupo_pago_transf_index');
    }
}
