<?php

namespace App\Controller\Backend\Nomencladores;

use App\Entity\NmMunicipio;
use App\Form\NmMunicipioType;
use App\Repository\NmMonedaRepository;
use App\Repository\NmMunicipioRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/backend/nm/municipio")
 */
class NmMunicipioController extends AbstractController
{
    /**
     * @Route("/", name="nm_municipio_index", methods={"GET"})
     */
    public function index(NmMunicipioRepository $nmMunicipioRepository, NmMonedaRepository $nmMonedaRepository): Response
    {
        $monedaComision = $nmMonedaRepository->findOneBy(['comision' => true]);

        return $this->render('backend/nm_municipio/index.html.twig', [
            'nm_municipios' => $nmMunicipioRepository->findAll(),
            'moneda' => !is_null($monedaComision) ? $monedaComision->getSimbolo() : ''
        ]);
    }


    /**
     * @Route("/{id}/edit", name="nm_municipio_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, NmMunicipio $nmMunicipio): Response
    {
        $form = $this->createForm(NmMunicipioType::class, $nmMunicipio);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('nm_municipio_index');
        }

        return $this->render('backend/nm_municipio/edit.html.twig', [
            'nm_municipio' => $nmMunicipio,
            'form' => $form->createView(),
        ]);
    }

}
