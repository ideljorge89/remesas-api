<?php
/**
 * Created by PhpStorm.
 * User: raymond
 * Date: 21/01/20
 * Time: 4:32
 */

namespace App\Controller\Backend;


use App\Manager\CropAvatar;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/backend/crop")
 */
class CropPhotoController extends AbstractController
{
    /**
     * @Route("/photo_crop/{avatar}", name="app_photo_crop", defaults={"avatar" = 0})
     */
    public function cropAction(Request $request, $avatar, CropAvatar $crop)
    {
        $url = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath();

        $parameters = $request->request->all();
        $file = $request->files->get('avatar_file');

        if ($avatar == 1) {
            $crop->init($parameters['avatar_src'], $parameters['avatar_data'], $file, 220, 220);   //resolucion para avatar
        }

        $response = array(
            'state' => 200,
            'message' => $crop->getMsg(),
            'result' => ($crop->getMsg() == null) ? $url . '/' . $crop->getResult() : null,
            'img' => ($crop->getMsg() == null) ? $crop->getResult() : null
        );

        return new JsonResponse($response);
    }
}