<?php
/**
 * Created by PhpStorm.
 * User: raymond
 * Date: 21/01/20
 * Time: 3:32
 */

namespace App\Controller\Backend;


use App\Entity\TnConfiguration;
use App\Manager\ConfigurationManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\Translator;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/backend/configuration")
 */
class ConfigurationController extends AbstractController
{
    /**
     * @Route("/", name="backend_configurations", methods={"GET","POST"})
     */
    public function configurationsAction(Request $request,ConfigurationManager $configuration)
    {
        $d = new \ReflectionClass(TnConfiguration::class);
        $constants = $d->getConstants();
        $data = array();
        foreach ($constants as $constant => $value) {
            if ($value != TnConfiguration::DEFAULT_LANGUAJE) {
                $data[$value] = $configuration->get($value);
            }
        }
        return $this->render('backend/configurations/configurations.html.twig', array(
            'data' => $data
        ));
    }

    /**
     * @Route("/edit/{config}", name="backend_configurations_edit",methods={"GET","POST"})
     */
    public function editConfigurationAction(Request $request,TranslatorInterface $translator,ConfigurationManager $configuration, $config)
    {
        $value_config = $configuration->get($config);

        if ($request->isMethod("POST")) {
            $value = $request->get($config);
            try {
                $configuration->set($config, $value);

                $this->getDoctrine()->getManager()->flush();
            } catch (\Exception $e) {
                $this->get('session')->getFlashBag()->add('error', $translator->trans('backend.configurations.error', array(), 'AppBundle'));
            }
            return $this->redirectToRoute('backend_configurations');
        }

        return $this->render('backend/configurations/edit_config.html.twig', array(
            'config' => $config,
            'value' => $value_config
        ));
    }
}