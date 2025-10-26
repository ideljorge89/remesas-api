<?php

namespace App\Controller;

use FOS\UserBundle\Model\UserManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use App\Entity\TnUser;
use App\Form\Auto\TnUserType;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * TnUser controller.
 *
 * @Route("/backend/superadmin/users")
 */
class TnUserController extends AbstractController
{
    /**
     * Lists all TnUser entities.
     *
     * @Route("/", name="home_users_index")
     * @Method("GET")
     */
    public function indexAction(TranslatorInterface $translator, PaginatorInterface $paginator, AuthorizationCheckerInterface $authorizationChecker)
    {
        $em = $this->getDoctrine()->getManager();

        $form = $this->createFormSearch(null, $translator);
        $request = $this->get('request_stack')->getCurrentRequest();
        $tnUser = $this->getUser();
        if ($tnUser instanceof TnUser) {
            if ($authorizationChecker->isGranted("ROLE_SUPER_ADMIN")) {
                $query = $em->getRepository('App:TnUser')->createQueryBuilder('c')
                    ->where('c.enabled = true')
                    ->getQuery();
            } else {
                $query = $em->getRepository('App:TnUser')->createQueryBuilder('c')
                    ->where('c.enabled = true')
                    ->join('c.agente', 'agente')
                    ->andWhere('agente.agencia = :ag')
                    ->setParameter('ag', $tnUser->getAgencia())
                    ->getQuery();
            }
        }

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        $deleteForms = array();
        foreach ($pagination as $p) {
            $deleteForms[] = $this->createDeleteForm($p)->createView();
        }

        return $this->render('backend/tnuser/index.html.twig', array(
            'tnUsers' => $pagination,
            'form' => $form->createView(),
            'delete_forms' => $deleteForms
        ));
    }

    /**
     * @Route("/search", name="users-search")
     * @Method({"GET", "POST"})
     */
    public function searchAction(Request $request, TranslatorInterface $translator)
    {
        $form = $this->createFormSearch(null, $translator);
        $form->handleRequest($request);
        $tnUser = $this->getUser();
        $data = array();
        $checker = $this->get('security.authorization_checker');
        if ($form->isValid() && $form->isSubmitted()) {
            if ($checker->isGranted("ROLE_SUPER_ADMIN")) {
                $data = $this->getDoctrine()->getManager()->getRepository('App:TnUser')->findSelectionsUsers($form->getData());
            } else {
                $data = [];
//                $data = $this->getDoctrine()->getManager()->getRepository('App:TnUser')->findSelectionsUserAgencia($form->getData(), $tnUser);
            }

        }
        $deleteForms = array();
        foreach ($data as $p) {
            $deleteForms[] = $this->createDeleteForm($p)->createView();
        }
        return $this->render('backend/tnuser/search.html.twig', array(
            'form' => $form->createView(),
            'data' => $data,
            'delete_forms' => $deleteForms
        ));
    }

    /**
     * Creates a new TnUser entity.
     *
     * @Route("/new", name="home_users_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request, UserManagerInterface $manager, TranslatorInterface $translator, AuthorizationCheckerInterface $authorizationChecker)
    {
        $tnUser = $manager->createUser();

        $form = $this->createForm(TnUserType::class, $tnUser);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($tnUser instanceof TnUser) {
                if ($authorizationChecker->isGranted("ROLE_SUPER_ADMIN")) {
                    $tnUser->setRoles(array($form->get('user_roles')->getData()));
                } else {
                    $tnUser->setRoles(array("ROLE_AGENTE"));
                }
            }
            $tnUser->setEnabled(true);
            $tnUser->setAuth(true);
            $tnUser->setPorcentaje(0);
            $tnUser->setToken((sha1(uniqid())));
            $manager->updateUser($tnUser, true);

            return $this->redirectToRoute('home_users_index');
        }

        return $this->render('backend/tnuser/new.html.twig', array(
            'tnUser' => $tnUser,
            'form' => $form->createView(),
            'title' => $translator->trans('users.new_user', array(), 'AppBundle')
        ));
    }

    /**
     * Finds and displays a TnUser entity.
     *
     * @Route("/{token}", name="home_users_show")
     * @ParamConverter("tnUser", options={"mapping": {"token": "token"}})
     * @Method("GET")
     */
    public function showAction(TnUser $tnUser, TranslatorInterface $translator)
    {
        $deleteForm = $this->createDeleteForm($tnUser);

        return $this->render('backend/tnuser/show.html.twig', array(
            'tnUser' => $tnUser,
            'delete_form' => $deleteForm->createView(),
            'title' => $translator->trans('users.view_user', array(
                '%username%' => $tnUser->getUsername()
            ), 'AppBundle')
        ));
    }

    /**
     * Displays a form to edit an existing TnUser entity.
     *
     * @Route("/{token}/edit", name="home_users_edit")
     * @ParamConverter("tnUser", options={"mapping": {"token": "token"}})
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, TnUser $tnUser, TranslatorInterface $translator)
    {
        $deleteForm = $this->createDeleteForm($tnUser);


        $editForm = $this->createForm(TnUserType::class, $tnUser);

        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($tnUser);
            $em->flush();

            return $this->redirectToRoute('home_users_index');
        }

        return $this->render('backend/tnuser/edit.html.twig', array(
            'tnUser' => $tnUser,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'title' => $translator->trans('users.edit_user', array(
                '%username%' => $tnUser->getUsername()
            ), 'AppBundle')
        ));
    }

    /**
     * Deletes a TnUser entity.
     *
     * @Route("/{token}/delete", name="home_users_delete")
     * @ParamConverter("tnUser", options={"mapping": {"token": "token"}})
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, TnUser $tnUser)
    {
        $form = $this->createDeleteForm($tnUser);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($tnUser);
            $em->flush();
        }

        return $this->redirectToRoute('home_users_index');
    }

    /**
     * Creates a form to delete a TnUser entity.
     *
     * @param TnUser $tnUser The TnUser entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(TnUser $tnUser)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('home_users_delete', array('token' => $tnUser->getToken())))
            ->setMethod('DELETE')
            ->getForm();
    }

    public function createFormSearch($data = null, $translator)
    {
        $checker = $this->get('security.authorization_checker');
        if ($checker->isGranted("ROLE_SUPER_ADMIN")) {
            $form = $this->createFormBuilder($data, array('csrf_protection' => false))
                ->add('search', SearchType::class, array(
                    'translation_domain' => 'AppBundle',
                    'label' => 'backend.comun.search',
                    'attr' => array('placeholder' => $translator->trans('backend.users.form.placeholder.search', array(), 'AppBundle'))
                ))
                ->add('options', ChoiceType::class, array(
                    'expanded' => true,
                    'multiple' => true,
                    'choices' => array(
                        $translator->trans('backend.users.table.type', array(), 'AppBundle') => 'type',
                        $translator->trans('backend.users.table.username', array(), 'AppBundle') => 'username',
                        $translator->trans('backend.users.table.email', array(), 'AppBundle') => 'email',
                        $translator->trans('backend.users.table.agencia', array(), 'AppBundle') => 'agencia',
                        $translator->trans('backend.users.table.agente', array(), 'AppBundle') => 'agente',
                        $translator->trans('backend.users.table.distrubuidor', array(), 'AppBundle') => 'distribuidor'
                    ),
                    'required' => true,
                    /*'choices_as_values' => true,*/
                ))
                ->getForm();
        } elseif ($checker->isGranted("ROLE_AGENCIA")) {
            $form = $this->createFormBuilder($data, array('csrf_protection' => false))
                ->add('search', SearchType::class, array(
                    'translation_domain' => 'AppBundle',
                    'label' => 'backend.comun.search',
                    'attr' => array('placeholder' => $translator->trans('backend.users.form.placeholder.search', array(), 'AppBundle'))
                ))
                ->add('options', ChoiceType::class, array(
                    'expanded' => true,
                    'multiple' => true,
                    'choices' => array(
                        $translator->trans('backend.users.table.type', array(), 'AppBundle') => 'type',
                        $translator->trans('backend.users.table.username', array(), 'AppBundle') => 'username',
                        $translator->trans('backend.users.table.email', array(), 'AppBundle') => 'email',
                        $translator->trans('backend.users.table.agente', array(), 'AppBundle') => 'agente',
                    ),
                    'required' => true,
                    /*'choices_as_values' => true,*/
                ))
                ->getForm();
        }

        return $form;
    }
}
