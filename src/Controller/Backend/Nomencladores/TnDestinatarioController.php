<?php

namespace App\Controller\Backend\Nomencladores;

use App\Entity\NmMunicipio;
use App\Entity\TnAgente;
use App\Entity\TnDestinatario;
use App\Entity\TnEmisor;
use App\Entity\TnUser;
use App\Form\TnDestinatarioType;
use App\Form\Type\DestinatarioSerachType;
use App\Repository\TnDestinatarioRepository;
use App\Repository\TnEmisorRepository;
use Knp\Component\Pager\Paginator;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @Route("/remesa/tn/destinatario")
 */
class TnDestinatarioController extends AbstractController
{
    /**
     * @Route("/", name="tn_destinatario_index", methods={"GET"})
     */
    public function index(TnDestinatarioRepository $tnDestinatarioRepository, PaginatorInterface $paginator, AuthorizationCheckerInterface $authorizationChecker): Response
    {
        $em = $this->getDoctrine()->getManager();
        $tnUser = $this->getUser();
        if ($tnUser instanceof TnUser) {
            if ($authorizationChecker->isGranted("ROLE_AGENCIA") || $authorizationChecker->isGranted("ROLE_AGENTE")) {
                if ($authorizationChecker->isGranted("ROLE_AGENCIA")) {
                    $usuariosIds = $em->getRepository(TnUser::class)->findUsuariosAgentesAgenciaIds($tnUser->getAgencia());

                    $query = $tnDestinatarioRepository->createQueryBuilder('f')
                        ->where('f.usuario = :ua or f.usuario IN (:users)')
                        ->setParameter('ua', $tnUser)
                        ->setParameter('users', $usuariosIds)
                        ->orderBy('f.created', "DESC")
                        ->getQuery();
                } else {
                    $tnAgente = $tnUser->getAgente();
                    if ($tnAgente->getTipoAgente() == TnAgente::TIPO_EXTERNO) {
                        $query = $tnDestinatarioRepository->createQueryBuilder('f')
                            ->where('f.usuario = :ua')
                            ->setParameter('ua', $tnUser)
                            ->orderBy('f.created', "DESC")
                            ->getQuery();
                    } else {
                        $userAgencia = $tnAgente->getAgencia()->getUsuario()->getId();
                        $query = $tnDestinatarioRepository->createQueryBuilder('f')
                            ->where('f.usuario = :ua or f.usuario = :uagc')
                            ->setParameter('ua', $tnUser)
                            ->setParameter('uagc', $userAgencia)
                            ->orderBy('f.created', "DESC")
                            ->getQuery();
                    }
                }
            } else {
                $query = $tnDestinatarioRepository->createQueryBuilder('f')->orderBy('f.created', "DESC")->getQuery();
            }
        }

        $request = $this->get('request_stack')->getCurrentRequest();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            100
        );

        $form = $this->createForm(DestinatarioSerachType::class);

        return $this->render('backend/tn_destinatario/index.html.twig', [
            'tn_destinatarios' => $pagination,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/destinatario_search", name="tn_destinatario_advanced_search")
     * @Method({"POST"})
     */
    public function advancedSearch(Request $request, TnDestinatarioRepository $tnDestinatarioRepository)
    {
        $tnUser = $this->getUser();
        $data = $request->request->all();

        $destinatarios = [];
        if ($data['destinatario_serach']['emisor'] != "" || $data['destinatario_serach']['destinatario'] != "" || $data['destinatario_serach']['phone'] != "" || $data['destinatario_serach']['direccion'] != "" || (isset($data['destinatario_serach']['usuario']) && $data['destinatario_serach']['usuario'] != "")) {
            $authorizationChecker = $this->get('security.authorization_checker');
            if ($authorizationChecker->isGranted("ROLE_AGENCIA")) {
                $destinatarios = $tnDestinatarioRepository->advancedSearchDestinatario($data['destinatario_serach'], $tnUser);
            } elseif ($authorizationChecker->isGranted("ROLE_AGENTE")) {
                $destinatarios = $tnDestinatarioRepository->advancedSearchDestinatario($data['destinatario_serach'], $tnUser);
            } else {
                $destinatarios = $tnDestinatarioRepository->advancedSearchDestinatario($data['destinatario_serach']);
            }
        }


        return $this->render('backend/tn_destinatario/search_results.html.twig', [
            'tn_destinatarios' => $destinatarios
        ]);
    }

    /**
     * @Route("/new/{modal}", name="tn_destinatario_new", methods={"GET","POST"},defaults={"modal":""})
     * @Route("/new/{token}/emisor/{modal}", name="tn_destinatario_new_emisor", methods={"GET","POST"},defaults={"modal":"" })
     * @ParamConverter("tnEmisor", options={"mapping": {"token": "token"}})
     */
    public function new(Request $request, TnEmisor $tnEmisor = null, $modal = "", TnEmisorRepository $tnEmisorRepository): Response
    {
        ini_set('memory_limit', -1);

        if ($modal == true) {
            $idEmisor = $request->get('idEmisor');
            if (isset($idEmisor) && $idEmisor != "") {
                $tnEmisor = $tnEmisorRepository->find($idEmisor);
            }
        }
        $tnDestinatario = new TnDestinatario();
        if ($tnEmisor != null) {
            $tnDestinatario->setEmisor($tnEmisor);
        }
        $form = $this->createForm(TnDestinatarioType::class, $tnDestinatario);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $security = $this->get('security.authorization_checker');
            if ($security->isGranted('ROLE_AGENCIA') || $security->isGranted('ROLE_AGENTE')) {
                $tnUser = $this->getUser();
                if ($security->isGranted('ROLE_AGENCIA')) {
                    if ($form->get('usuario')->getData() == null || $form->get('usuario')->getData() == "") {
                        if ($tnUser instanceof TnUser) {
                            $tnDestinatario->setUsuario($tnUser);
                        }
                    }
                } else {
                    $tnUser = $this->getUser();
                    if ($tnUser instanceof TnUser) {
                        $tnAgente = $tnUser->getAgente();
                        if ($tnAgente->getTipoAgente() == TnAgente::TIPO_EXTERNO) {
                            $tnDestinatario->setUsuario($tnUser);
                        } else {
                            $userAgencia = $tnAgente->getAgencia()->getUsuario();
                            $tnDestinatario->setUsuario($userAgencia);
                        }
                    }
                }
            }

            $tnDestinatario->setToken((sha1(uniqid())));
            $entityManager->persist($tnDestinatario);
            $entityManager->flush();

            if ($modal != true) {
                return $this->redirectToRoute('tn_destinatario_index');
            } else {
                switch ($form->get('handler')->getData()) {
                    case 'save':
                        return $this->render('backend/tn_destinatario/close_modal.html.twig', array(
                            'elem' => $tnEmisor, 'form' => $form->createView()
                        ));
                        break;
                }
            }
        }
        if ($modal == true) {
            return $this->render('backend/tn_destinatario/new_modal.html.twig', [
                'destinatario' => $tnDestinatario,
                'form' => $form->createView(),
            ]);
        } else {

            return $this->render('backend/tn_destinatario/new.html.twig', [
                'destinatario' => $tnDestinatario,
                'form' => $form->createView(),
            ]);
        }
    }

    /**
     * @Route("/{token}/edit", name="tn_destinatario_edit", methods={"GET","POST"})
     * @ParamConverter("tnDestinatario", options={"mapping": {"token": "token"}})
     */
    public function edit(Request $request, TnDestinatario $tnDestinatario): Response
    {
        ini_set('memory_limit', -1);

        $form = $this->createForm(TnDestinatarioType::class, $tnDestinatario);
        $tnUser = $this->getUser();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $security = $this->get('security.authorization_checker');
            if ($security->isGranted('ROLE_AGENCIA') || $security->isGranted('ROLE_AGENTE')) {
                if ($security->isGranted('ROLE_AGENTE')) {
                    $tnAgente = $tnUser->getAgente();
                    if ($tnAgente->getTipoAgente() == TnAgente::TIPO_EXTERNO) {
                        $tnDestinatario->setUsuario($tnUser);
                    } else {
                        $userAgencia = $tnAgente->getAgencia()->getUsuario();
                        $tnDestinatario->setUsuario($userAgencia);
                    }
                } else {
                    if ($form->get('usuario')->getData() == null || $form->get('usuario')->getData() == "") {
                        if ($security->isGranted('ROLE_AGENCIA')) {
                            if ($tnUser instanceof TnUser) {
                                $tnDestinatario->setUsuario($tnUser);
                            }
                        }
                    }
                }
            }
            $em->persist($tnDestinatario);
            $em->flush();

            return $this->redirectToRoute('tn_destinatario_index');
        }

        return $this->render('backend/tn_destinatario/edit.html.twig', ['destinatario' => $tnDestinatario,
            'form' => $form->createView(),]);
    }

    /**
     * @Route("/oficinas/provincia", name="admin_find_municipios_provincia")
     * @Method({"GET", "POST"})
     */
    public
    function findMunicipiosProvinciaAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        if ($request->isXmlHttpRequest()) {
            $idProvincia = $request->get('idProvincia');
            $municipios = $em->getRepository(NmMunicipio::class)->findBy(array('provincia' => $idProvincia));

            $result = array();
            foreach ($municipios as $municipio) {
                $temp['value'] = $municipio->getId();
                $temp['text'] = $municipio->getName();
                $result[] = $temp;
            }
            $response = new Response();
            $response->setContent(json_encode(array('municipios' => $result)));

            return $response;
        }
    }

    /**
     * @Route("/detele/{token}", name="tn_destinatario_delete", methods={"DELETE"})
     * @ParamConverter("tnDestinatario", options={"mapping": {"token": "token"}})
     */
    public
    function delete(Request $request, TnDestinatario $tnDestinatario): Response
    {
        if ($this->isCsrfTokenValid('delete' . $tnDestinatario->getToken(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($tnDestinatario);
            $entityManager->flush();

            $this->get('session')->getFlashBag()->add('info', "Destinatario eliminado correctamente");
        }

        return $this->redirectToRoute('tn_destinatario_index');
    }
}
