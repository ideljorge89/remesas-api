<?php

namespace App\Controller\Backend\Nomencladores;

use App\Entity\NmGrupoPagoTransfAgente;
use App\Entity\TnUser;
use App\Form\NmGrupoPagoTransfAgenteType;
use App\Repository\NmGrupoPagoTransfAgenteRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @Route("/nm/grupo/pago/transf/agente")
 */
class NmGrupoPagoTransfAgenteController extends AbstractController
{
    /**
     * @Route("/", name="nm_grupo_pago_transf_agente_index", methods={"GET"})
     */
    public function index(NmGrupoPagoTransfAgenteRepository $nmGrupoPagoTransfAgenteRepository, AuthorizationCheckerInterface $authorizationChecker): Response
    {
        $tnUser = $this->getUser();
        if ($tnUser instanceof TnUser) {
            if ($authorizationChecker->isGranted("ROLE_AGENCIA")) {
                $query = $nmGrupoPagoTransfAgenteRepository->createQueryBuilder('f')
                    ->where('f.usuario = :ua')
                    ->setParameter('ua', $tnUser)
                    ->orderBy('f.created', "DESC")
                    ->getQuery();
            } else {
                $query = $nmGrupoPagoTransfAgenteRepository->createQueryBuilder('f')->orderBy('f.created', "DESC")->getQuery();
            }
        }

        return $this->render('backend/nm_grupo_pago_transf_agente/index.html.twig', [
            'nm_grupo_pago_transf_agentes' => $query->getResult(),
        ]);
    }

    /**
     * @Route("/new", name="nm_grupo_pago_transf_agente_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $nmGrupoPagoTransfAgente = new NmGrupoPagoTransfAgente();
        $form = $this->createForm(NmGrupoPagoTransfAgenteType::class, $nmGrupoPagoTransfAgente);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $tnUser = $this->getUser();
            if ($tnUser instanceof TnUser) {
                $nmGrupoPagoTransfAgente->setUsuario($tnUser);
            }
            $nmGrupoPagoTransfAgente->setToken((sha1(uniqid())));
            $entityManager->persist($nmGrupoPagoTransfAgente);
            $entityManager->flush();

            return $this->redirectToRoute('nm_grupo_pago_transf_agente_index');
        }

        return $this->render('backend/nm_grupo_pago_transf_agente/new.html.twig', [
            'nm_grupo_pago_transf_agente' => $nmGrupoPagoTransfAgente,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{token}", name="nm_grupo_pago_transf_agente_show", methods={"GET"})
     * @ParamConverter("nmGrupoPagoTransfAgente", options={"mapping": {"token": "token"}})
     */
    public function show(NmGrupoPagoTransfAgente $nmGrupoPagoTransfAgente): Response
    {
        return $this->render('backend/nm_grupo_pago_transf_agente/show.html.twig', [
            'nm_grupo_pago_transf_agente' => $nmGrupoPagoTransfAgente,
        ]);
    }

    /**
     * @Route("/{token}/edit", name="nm_grupo_pago_transf_agente_edit", methods={"GET","POST"})
     * @ParamConverter("nmGrupoPagoTransfAgente", options={"mapping": {"token": "token"}})
     */
    public function edit(Request $request, NmGrupoPagoTransfAgente $nmGrupoPagoTransfAgente): Response
    {
        $form = $this->createForm(NmGrupoPagoTransfAgenteType::class, $nmGrupoPagoTransfAgente);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('nm_grupo_pago_transf_agente_index');
        }

        return $this->render('backend/nm_grupo_pago_transf_agente/edit.html.twig', [
            'nm_grupo_pago_transf_agente' => $nmGrupoPagoTransfAgente,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="nm_grupo_pago_transf_agente_delete", methods={"DELETE"})
     */
    public function delete(Request $request, NmGrupoPagoTransfAgente $nmGrupoPagoTransfAgente): Response
    {
        if ($this->isCsrfTokenValid('delete' . $nmGrupoPagoTransfAgente->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($nmGrupoPagoTransfAgente);
            $entityManager->flush();
        }

        return $this->redirectToRoute('nm_grupo_pago_transf_agente_index');
    }
}
