<?php
namespace App\Twig\Extension\Radley;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TruncateHtmlExtension extends AbstractExtension {
    public function getName() {
        return 'truncatehtml';
    }
    public function getFilters() {
        return array('truncatehtml' => new TwigFunction('truncateHtml',array($this, 'truncatehtml')));
    }
    public function truncatehtml($html, $limit, $endchar = '&hellip;') {
        $output = new TruncateHtmlString($html, $limit);
        return $output->cut() . $endchar;
    }
}