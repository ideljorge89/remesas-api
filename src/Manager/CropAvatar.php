<?php
/**
 * Created by PhpStorm.
 * Date: 30/04/15
 * Time: 21:59
 */

namespace App\Manager;


use Symfony\Component\DependencyInjection\ContainerInterface;

class CropAvatar
{
    private $src;
    private $data;
    private $file;
    private $dst;
    private $type;
    private $extension;
    private $msg;
    private $dstW;
    private $dstH;

    private $container;
    private $webRoot;

    private $original;
    private $cropped;
    private $id_original;
    private $id_cropped;
    private $validator_upload_size = 2;   //2       ----> 2 MB
    private $validator_upload_format = 2; // MB     ----> 2 MB
    private $sizess = array(" Bytes", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB");

    function format_size($size)
    {
        $sizes = $this->sizess;
        if ($size == 0) {
            return ('n/a');
        } else {
            return (round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . $sizes[$i]);
        }
    }

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->kernel_root = $this->container->getParameter('kernel.project_dir');
        $web_dir = $this->container->getParameter('web_dir');
        $this->webRoot = realpath($this->kernel_root . '/' . $web_dir) . '/uploads/foto/';
        $this->dstW = 713;
        $this->dstH = 402;
        $iniqid = uniqid();
        $this->id_original = 'original_' . $iniqid;
        $this->id_cropped = 'cropped_' . $iniqid;
    }

    public function init($src, $data, $file, $dstW = null, $dstH = null)
    {
        $format = $this->format_size(filesize($file));

        if ($format != 'n/a')
            list($size, $f) = explode(' ', $format);

        if ($format == 'n/a' || (double)$size > $this->validator_upload_size && array_search(' ' . $f, $this->sizess) >= $this->validator_upload_format) {
            $this->msg = $this->codeToMessage(UPLOAD_ERR_FORM_SIZE);
            return;
        }

        $this->setSrc($src);
        $this->setData($data);
        $this->setFile($file);
        if ($dstW != null)
            $this->dstW = $dstW;
        if ($dstH != null)
            $this->dstH = $dstH;

        $this->crop($this->src, $this->dst, $this->data);
    }

    private function setSrc($src)
    {

        if (!empty($src)) {
            $type = exif_imagetype($src);

            if ($type) {
                $this->src = $src;
                $this->type = $type;
                $this->extension = image_type_to_extension($type);
                $this->setDst();
            }
        }
    }

    private function setData($data)
    {
        if (!empty($data)) {
            $this->data = json_decode(stripslashes($data));
        }
    }

    private function setFile($file)
    {
        $errorCode = $file->getError();

        if ($errorCode === UPLOAD_ERR_OK) {
            $type = exif_imagetype($file->getRealPath());

            if ($type) {
                $extension = image_type_to_extension($type);
                $this->original = $this->id_original . $extension;
                $src = $this->webRoot . $this->original;

                if ($type == IMAGETYPE_GIF || $type == IMAGETYPE_JPEG || $type == IMAGETYPE_PNG) {

                    if (file_exists($src)) {
                        unlink($src);
                    }

                    $result = move_uploaded_file($file->getRealPath(), $src);

                    if ($result) {
                        $this->src = $src;
                        $this->type = $type;
                        $this->extension = $extension;
                        $this->setDst();
                    } else {
                        $this->msg = 'Failed to save file';
                    }
                } else {
                    $this->msg = 'Please upload image with the following types: JPG, PNG, GIF';
                }
            } else {
                $this->msg = 'Please upload image file';
            }
        } else {
            $this->msg = $this->codeToMessage($errorCode);
        }
    }

    private function setDst()
    {
        $this->dst = $this->webRoot . $this->id_cropped . $this->extension;
        $this->cropped = $this->id_cropped . $this->extension;
    }

    /*private function crop($src, $dst, $data)
    {
        if (!empty($src) && !empty($dst) && !empty($data)) {
            switch ($this->type) {
                case IMAGETYPE_GIF:
                    $src_img = imagecreatefromgif($src);
                    break;

                case IMAGETYPE_JPEG:
                    $src_img = imagecreatefromjpeg($src);
                    break;

                case IMAGETYPE_PNG:
                    $src_img = imagecreatefrompng($src);
                    break;
            }

            if (!$src_img) {
                $this->msg = "Failed to read the image file";
                return;
            }

            $size = getimagesize($src);
            $size_w = $size[0]; // natural width
            $size_h = $size[1]; // natural height

            $src_img_w = $size_w;
            $src_img_h = $size_h;

            $degrees = $data->rotate;

            // Rotate the source image
            if (is_numeric($degrees) && $degrees != 0) {
                // PHP's degrees is opposite to CSS's degrees
                $new_img = imagerotate($src_img, -$degrees, imagecolorallocatealpha($src_img, 0, 0, 0, 127));

                imagedestroy($src_img);
                $src_img = $new_img;

                $deg = abs($degrees) % 180;
                $arc = ($deg > 90 ? (180 - $deg) : $deg) * M_PI / 180;

                $src_img_w = $size_w * cos($arc) + $size_h * sin($arc);
                $src_img_h = $size_w * sin($arc) + $size_h * cos($arc);

                // Fix rotated image miss 1px issue when degrees < 0
                $src_img_w -= 1;
                $src_img_h -= 1;
            }

            $tmp_img_w = $data->width;
            $tmp_img_h = $data->height;
            $dst_img_w = 152;
            $dst_img_h = 190;

            $src_x = $data->x;
            $src_y = $data->y;

            if ($src_x <= -$tmp_img_w || $src_x > $src_img_w) {
                $src_x = $src_w = $dst_x = $dst_w = 0;
            } else if ($src_x <= 0) {
                $dst_x = -$src_x;
                $src_x = 0;
                $src_w = $dst_w = min($src_img_w, $tmp_img_w + $src_x);
            } else if ($src_x <= $src_img_w) {
                $dst_x = 0;
                $src_w = $dst_w = min($tmp_img_w, $src_img_w - $src_x);
            }

            if ($src_w <= 0 || $src_y <= -$tmp_img_h || $src_y > $src_img_h) {
                $src_y = $src_h = $dst_y = $dst_h = 0;
            } else if ($src_y <= 0) {
                $dst_y = -$src_y;
                $src_y = 0;
                $src_h = $dst_h = min($src_img_h, $tmp_img_h + $src_y);
            } else if ($src_y <= $src_img_h) {
                $dst_y = 0;
                $src_h = $dst_h = min($tmp_img_h, $src_img_h - $src_y);
            }

            // Scale to destination position and size
            $ratio = $tmp_img_w / $dst_img_w;
            $dst_x /= $ratio;
            $dst_y /= $ratio;
            $dst_w /= $ratio;
            $dst_h /= $ratio;

            $dst_img = imagecreatetruecolor($dst_img_w, $dst_img_h);

            // Add transparent background to destination image
            imagefill($dst_img, 0, 0, imagecolorallocatealpha($dst_img, 0, 0, 0, 127));
            imagesavealpha($dst_img, true);

            $result = imagecopyresampled($dst_img, $src_img, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);

            if ($result) {
                if (!imagepng($dst_img, $dst)) {
                    $this->msg = "Failed to save the cropped image file";
                } else {
                    $border = 2; //aumentar o disminuir los bordes en px
                    $width = imagesx($dst_img);
                    $height = imagesy($dst_img);

                    $img_adj_width = $width + (2 * $border);
                    $img_adj_height = $height + (2 * $border);
                    $newimage = imagecreatetruecolor($img_adj_width, $img_adj_height);

                    $border_color = imagecolorallocate($newimage, 0, 0, 0);
                    imagefilledrectangle($newimage, 0, 0, $img_adj_width, $img_adj_height, $border_color);

                    imagecopyresized($newimage, $dst_img, $border, $border, 0, 0, $width, $height, $width, $height);

                    imagejpeg($newimage, $dst, 100);
                }
            } else {
                $this->msg = "Failed to crop the image file";
            }

            imagedestroy($src_img);
            imagedestroy($dst_img);
        }
    }*/
    function check_jpeg($f, $fix = false)
    {
# [070203]
# check for jpeg file header and footer - also try to fix it
        if (false !== (@$fd = fopen($f, 'r+b'))) {
            if (fread($fd, 2) == chr(255) . chr(216)) {
                fseek($fd, -2, SEEK_END);
                if (fread($fd, 2) == chr(255) . chr(217)) {
                    fclose($fd);
                    return true;
                } else {
                    if ($fix && fwrite($fd, chr(255) . chr(217))) {
                        fclose($fd);
                        return true;
                    }
                    fclose($fd);
                    return false;
                }
            } else {
                fclose($fd);
                return false;
            }
        } else {
            return false;
        }
    }

    private function crop($src, $dst, $data)
    {
        if (!empty($src) && !empty($dst) && !empty($data)) {
            switch ($this->type) {
                case IMAGETYPE_GIF:
                    $src_img = @imagecreatefromgif($src);
                    break;

                case IMAGETYPE_JPEG:
                    if ($this->check_jpeg($src, true))
                        $src_img = @imagecreatefromjpeg($src);
                    break;

                case IMAGETYPE_PNG:
                    $src_img = @imagecreatefrompng($src);
                    break;
            }

            if (!$src_img) {
                $this->msg = "Failed to read the image file";
                return;
            }

            $size = getimagesize($src);
            $size_w = $size[0]; // natural width
            $size_h = $size[1]; // natural height

            $src_img_w = $size_w;
            $src_img_h = $size_h;

            $degrees = $data->rotate;

            // Rotate the source image
            if (is_numeric($degrees) && $degrees != 0) {
                // PHP's degrees is opposite to CSS's degrees
                $new_img = imagerotate($src_img, -$degrees, imagecolorallocatealpha($src_img, 0, 0, 0, 127));

                imagedestroy($src_img);
                $src_img = $new_img;

                $deg = abs($degrees) % 180;
                $arc = ($deg > 90 ? (180 - $deg) : $deg) * M_PI / 180;

                $src_img_w = $size_w * cos($arc) + $size_h * sin($arc);
                $src_img_h = $size_w * sin($arc) + $size_h * cos($arc);

                // Fix rotated image miss 1px issue when degrees < 0
                $src_img_w -= 1;
                $src_img_h -= 1;
            }

            $tmp_img_w = $data->width;
            $tmp_img_h = $data->height;
            $dst_img_w = $this->dstW;
            $dst_img_h = $this->dstH;

            $src_x = $data->x;
            $src_y = $data->y;

            if ($src_x <= -$tmp_img_w || $src_x > $src_img_w) {
                $src_x = $src_w = $dst_x = $dst_w = 0;
            } else if ($src_x <= 0) {
                $dst_x = -$src_x;
                $src_x = 0;
                $src_w = $dst_w = min($src_img_w, $tmp_img_w + $src_x);
            } else if ($src_x <= $src_img_w) {
                $dst_x = 0;
                $src_w = $dst_w = min($tmp_img_w, $src_img_w - $src_x);
            }

            if ($src_w <= 0 || $src_y <= -$tmp_img_h || $src_y > $src_img_h) {
                $src_y = $src_h = $dst_y = $dst_h = 0;
            } else if ($src_y <= 0) {
                $dst_y = -$src_y;
                $src_y = 0;
                $src_h = $dst_h = min($src_img_h, $tmp_img_h + $src_y);
            } else if ($src_y <= $src_img_h) {
                $dst_y = 0;
                $src_h = $dst_h = min($tmp_img_h, $src_img_h - $src_y);
            }

            // Scale to destination position and size
            $ratio = $tmp_img_w / $dst_img_w;
            $dst_x /= $ratio;
            $dst_y /= $ratio;
            $dst_w /= $ratio;
            $dst_h /= $ratio;

            $dst_img = imagecreatetruecolor($dst_img_w, $dst_img_h);

            // Add transparent background to destination image
            imagefill($dst_img, 0, 0, imagecolorallocatealpha($dst_img, 0, 0, 0, 127));
            imagesavealpha($dst_img, true);

            $result = imagecopyresampled($dst_img, $src_img, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);

            if ($result) {
                if (!imagepng($dst_img, $dst)) {
                    $this->msg = "Failed to save the cropped image file";
                }
            } else {
                $this->msg = "Failed to crop the image file";
            }

            imagedestroy($src_img);
            imagedestroy($dst_img);
        }
    }

    private function codeToMessage($code)
    {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
                $message = 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
                break;

            case UPLOAD_ERR_FORM_SIZE:
                $message = $this->container->get('translator')->trans('app.products.max_upload.max', array('%count%' => $this->validator_upload_size, '%format%' => $this->sizess[$this->validator_upload_format]), 'validators');
                break;

            case UPLOAD_ERR_PARTIAL:
                $message = 'The uploaded file was only partially uploaded';
                break;

            case UPLOAD_ERR_NO_FILE:
                $message = 'No file was uploaded';
                break;

            case UPLOAD_ERR_NO_TMP_DIR:
                $message = 'Missing a temporary folder';
                break;

            case UPLOAD_ERR_CANT_WRITE:
                $message = 'Failed to write file to disk';
                break;

            case UPLOAD_ERR_EXTENSION:
                $message = 'File upload stopped by extension';
                break;

            default:
                $message = 'Unknown upload error';
        }

        return $message;
    }

    public function getResult()
    {
        $url_foto = 'uploads/foto/';
        $url_foto .= !empty($this->data) ? $this->cropped : $this->original;
        return $url_foto;
    }

    public function getMsg()
    {
        return $this->msg;
    }

    public function setPhoto($ficha)
    {
        $em = $this->container->get('doctrine')->getManager();
        $url_foto = 'uploads/foto/';
        $url_foto .= !empty($this->data) ? $this->cropped : $this->original;
        $ficha->setFoto($url_foto);

        $em->persist($ficha);
        $em->flush();
    }

    public function addBorder()
    {
        $add = "img.jpg";

        //$add2="images/1.jpg"; // Remove comment if a new image is to be created
        $border = 2; // Change the value to adjust width
        $im = imagecreatefromjpeg($add);
        $width = imagesx($im);
        $height = imagesy($im);


        $img_adj_width = $width + (2 * $border);
        $img_adj_height = $height + (2 * $border);
        $newimage = imagecreatetruecolor($img_adj_width, $img_adj_height);


        $border_color = imagecolorallocate($newimage, 0, 0, 0);
        imagefilledrectangle($newimage, 0, 0, $img_adj_width, $img_adj_height, $border_color);


        imagecopyresized($newimage, $im, $border, $border, 0, 0, $width, $height, $width, $height);

        header("Content-type: image/png");
        imagejpeg($newimage, $add, 100); // change here to $add2 if a new image is to be created
        chmod("$add", 0666); // change here to $add2 if a new image is to be created
    }
}