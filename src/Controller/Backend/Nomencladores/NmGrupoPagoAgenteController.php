<?php

namespace App\Controller\Backend\Nomencladores;

use App\Entity\NmGrupoPagoAgente;
use App\Entity\TnUser;
use App\Form\NmGrupoPagoAgenteType;
use App\Repository\NmGrupoPagoAgenteRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @Route("backend/nm/grupo/pago/agente")
 */
class NmGrupoPagoAgenteController extends AbstractController
{
    /**
     * @Route("/", name="nm_grupo_pago_agente_index", methods={"GET"})
     */
    public function index(NmGrupoPagoAgenteRepository $nmGrupoPagoAgenteRepository, AuthorizationCheckerInterface $authorizationChecker): Response
    {
        $tnUser = $this->getUser();
        if ($tnUser instanceof TnUser) {
            if ($authorizationChecker->isGranted("ROLE_AGENCIA")) {
                $query = $nmGrupoPagoAgenteRepository->createQueryBuilder('f')
                    ->where('f.usuario = :ua')
                    ->setParameter('ua', $tnUser)
                    ->orderBy('f.created', "DESC")
                    ->getQuery();
            } else {
                $query = $nmGrupoPagoAgenteRepository->createQueryBuilder('f')->orderBy('f.created', "DESC")->getQuery();
            }
        }

        return $this->render('backend/nm_grupo_pago_agente/index.html.twig', [
            'nm_grupo_pago_agentes' => $query->getResult(),
        ]);
    }

    /**
     * @Route("/new", name="nm_grupo_pago_agente_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $nmGrupoPagoAgente = new NmGrupoPagoAgente();
        $form = $this->createForm(NmGrupoPagoAgenteType::class, $nmGrupoPagoAgente);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $tnUser = $this->getUser();
            if ($tnUser instanceof TnUser) {
                $nmGrupoPagoAgente->setUsuario($tnUser);
            }
            $nmGrupoPagoAgente->setToken((sha1(uniqid())));
            $entityManager->persist($nmGrupoPagoAgente);
            $entityManager->flush();

            return $this->redirectToRoute('nm_grupo_pago_agente_index');
        }

        return $this->render('backend/nm_grupo_pago_agente/new.html.twig', [
            'nm_grupo_pago_agente' => $nmGrupoPagoAgente,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="nm_grupo_pago_agente_show", methods={"GET"})
     */
    public function show(NmGrupoPagoAgente $nmGrupoPagoAgente): Response
    {
        return $this->render('backend/nm_grupo_pago_agente/show.html.twig', [
            'nm_grupo_pago_agente' => $nmGrupoPagoAgente,
        ]);
    }

    /**
     * @Route("/{token}/edit", name="nm_grupo_pago_agente_edit", methods={"GET","POST"})
     * @ParamConverter("nmGrupoPagoAgente", options={"mapping": {"token": "token"}})
     */
    public function edit(Request $request, NmGrupoPagoAgente $nmGrupoPagoAgente): Response
    {
        if ($nmGrupoPagoAgente->getPorcentaje() == null) {
            $nmGrupoPagoAgente->setPorcentaje(0);
        }
        if ($nmGrupoPagoAgente->getUtilidad() == null) {
            $nmGrupoPagoAgente->setUtilidad(0);
        }
        $form = $this->createForm(NmGrupoPagoAgenteType::class, $nmGrupoPagoAgente);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('nm_grupo_pago_agente_index');
        }

        return $this->render('backend/nm_grupo_pago_agente/edit.html.twig', [
            'nm_grupo_pago_agente' => $nmGrupoPagoAgente,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="nm_grupo_pago_agente_delete", methods={"DELETE"})
     */
    public function delete(Request $request, NmGrupoPagoAgente $nmGrupoPagoAgente): Response
    {
        if ($this->isCsrfTokenValid('delete' . $nmGrupoPagoAgente->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($nmGrupoPagoAgente);
            $entityManager->flush();
        }

        return $this->redirectToRoute('nm_grupo_pago_agente_index');
    }
}
