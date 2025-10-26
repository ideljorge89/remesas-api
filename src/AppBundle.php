<?php

namespace App;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class AppBundle extends Bundle
{
    public function getParent() {
        return 'FOSUserBundle';
    }
}
