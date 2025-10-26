<?php

namespace App\Controller\Backend\Nomencladores;

use App\Entity\TnAgencia;
use App\Entity\TnAgente;
use App\Entity\TnEmisor;
use App\Entity\TnUser;
use App\Form\TnEmisorType;
use App\Repository\TnEmisorRepository;
use App\Repository\TnUserRepository;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @Route("remesa/tn/emisor")
 */
class TnEmisorController extends AbstractController
{
    /**
     * @Route("/", name="tn_emisor_index", methods={"GET"})
     */
    public function index(TnEmisorRepository $tnEmisorRepository,PaginatorInterface $paginator, AuthorizationCheckerInterface $authorizationChecker): Response
    {
        $em = $this->getDoctrine()->getManager();
        $tnUser = $this->getUser();
        if ($tnUser instanceof TnUser) {
            if ($authorizationChecker->isGranted("ROLE_AGENCIA") || $authorizationChecker->isGranted("ROLE_AGENTE")) {
                if ($authorizationChecker->isGranted("ROLE_AGENCIA")) {
                    $usuariosIds = $em->getRepository(TnUser::class)->findUsuariosAgentesAgenciaIds($tnUser->getAgencia());

                    $query = $tnEmisorRepository->createQueryBuilder('f')
                        ->where('f.usuario = :ua or f.usuario IN (:users)')
                        ->setParameter('ua', $tnUser)
                        ->setParameter('users', $usuariosIds)
                        ->orderBy('f.created', "DESC")
                        ->getQuery();
                } else {
                    $tnAgente = $tnUser->getAgente();
                    if ($tnAgente->getTipoAgente() == TnAgente::TIPO_EXTERNO) {
                        $query = $tnEmisorRepository->createQueryBuilder('f')
                            ->where('f.usuario = :ua')
                            ->setParameter('ua', $tnUser)
                            ->orderBy('f.created', "DESC")
                            ->getQuery();
                    } else {
                        $userAgencia = $tnAgente->getAgencia()->getUsuario()->getId();
                        $query = $tnEmisorRepository->createQueryBuilder('f')
                            ->where('f.usuario = :ua or f.usuario = :uagc')
                            ->setParameter('ua', $tnUser)
                            ->setParameter('uagc', $userAgencia)
                            ->orderBy('f.created', "DESC")
                            ->getQuery();
                    }
                }
            } else {
                $query = $tnEmisorRepository->createQueryBuilder('f')->orderBy('f.created', "DESC")->getQuery();
            }
        }

        $request = $this->get('request_stack')->getCurrentRequest();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            100
        );


        return $this->render('backend/tn_emisor/index.html.twig', [
            'tn_emisors' => $pagination
        ]);
    }

    /**
     * @Route("/new/{modal}", name="tn_emisor_new", methods={"GET","POST"},defaults={"modal":"" })
     */
    public function new(Request $request, $modal = ""): Response
    {
        $tnEmisor = new TnEmisor();
        $form = $this->createForm(TnEmisorType::class, $tnEmisor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $security = $this->get('security.authorization_checker');
            if ($security->isGranted('ROLE_AGENCIA') || $security->isGranted('ROLE_AGENTE')) {
                $tnUser = $this->getUser();
                if ($security->isGranted('ROLE_AGENCIA')) {
                    if ($form->get('usuario')->getData() == null || $form->get('usuario')->getData() == "") {
                        if ($tnUser instanceof TnUser) {
                            $tnEmisor->setUsuario($tnUser);
                        }
                    }
                } else {
                    $tnUser = $this->getUser();
                    if ($tnUser instanceof TnUser) {
                        $tnAgente = $tnUser->getAgente();
                        if ($tnAgente->getTipoAgente() == TnAgente::TIPO_EXTERNO) {
                            $tnEmisor->setUsuario($tnUser);
                        } else {
                            $userAgencia = $tnAgente->getAgencia()->getUsuario();
                            $tnEmisor->setUsuario($userAgencia);
                        }
                    }
                }
            }

            $tnEmisor->setToken((sha1(uniqid())));
            $entityManager->persist($tnEmisor);
            $entityManager->flush();

            if ($modal != true) {
                return $this->redirectToRoute('tn_emisor_index');
            }else{
                switch ($form->get('handler')->getData()) {
                    case 'save':
                        return $this->render('backend/tn_emisor/close_modal.html.twig', array(
                            'elem' => $tnEmisor, 'form' => $form->createView()
                        ));
                        break;
                }
            }
        }

        if ($modal == true) {
            return $this->render('backend/tn_emisor/new_modal.html.twig', [
                'tn_emisor' => $tnEmisor,
                'form' => $form->createView(),
            ]);
        }else{
            return $this->render('backend/tn_emisor/new.html.twig', [
                'tn_emisor' => $tnEmisor,
                'form' => $form->createView(),
            ]);
        }


    }

    /**
     * @Route("/{token}/edit", name="tn_emisor_edit", methods={"GET","POST"})
     * @ParamConverter("tnEmisor", options={"mapping": {"token": "token"}})
     */
    public function edit(Request $request, TnEmisor $tnEmisor): Response
    {
        $form = $this->createForm(TnEmisorType::class, $tnEmisor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $security = $this->get('security.authorization_checker');
            if ($security->isGranted('ROLE_AGENCIA') || $security->isGranted('ROLE_AGENTE')) {
                if ($form->get('usuario')->getData() == null || $form->get('usuario')->getData() == "") {
                    $tnUser = $this->getUser();
                    if ($security->isGranted('ROLE_AGENCIA')) {
                        if ($tnUser instanceof TnUser) {
                            $tnEmisor->setUsuario($tnUser);
                        }
                    } else {
                        $tnUser = $this->getUser();
                        if ($tnUser instanceof TnUser) {
                            $tnAgente = $tnUser->getAgente();
                            if ($tnAgente->getTipoAgente() == TnAgente::TIPO_EXTERNO) {
                                $tnEmisor->setUsuario($tnUser);
                            } else {
                                $userAgencia = $tnAgente->getAgencia()->getUsuario();
                                $tnEmisor->setUsuario($userAgencia);
                            }
                        }
                    }
                }
            }
            $em->persist($tnEmisor);
            $em->flush();
            return $this->redirectToRoute('tn_emisor_index');
        }

        return $this->render('backend/tn_emisor/edit.html.twig', [
            'emisor' => $tnEmisor,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/detele/{token}", name="tn_emisor_delete", methods={"DELETE"})
     * @ParamConverter("tnEmisor", options={"mapping": {"token": "token"}})
     */
    public function delete(Request $request, TnEmisor $tnEmisor): Response
    {
        if ($this->isCsrfTokenValid('delete' . $tnEmisor->getToken(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($tnEmisor);
            $entityManager->flush();

            $this->get('session')->getFlashBag()->add('info', "Emisor eliminado correctamente");
        }

        return $this->redirectToRoute('tn_emisor_index');
    }

}
