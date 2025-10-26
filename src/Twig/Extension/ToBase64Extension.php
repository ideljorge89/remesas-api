<?php

namespace App\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class ToBase64Extension extends AbstractExtension
{
    var $default = null;

    public function __construct($default = null)
    {

        $this->default = $default;
    }

    public function getFilters()
    {
        return array(
            new TwigFilter('tobase64', array($this, 'toBase64Filter')),
        );
    }

    public function toBase64Filter($img_file, $default = null)
    {
        $src = "";
        if (!file_exists($img_file)) {
            if ($default == null || !file_exists($default)) {
                if (!file_exists($this->default)) {
                    return $src;
                } else
                    $img_file = $this->default;
            } else
                $img_file = $default;
        }

        $imgData = @base64_encode(@file_get_contents($img_file));
        // Format the image SRC:  data:{mime};base64,{data};
        $src = 'data: ' . @mime_content_type($img_file) . ';base64,' . $imgData;

        return $src;
    }

    public function getName()
    {
        return 'to_base_64_extension';
    }
}