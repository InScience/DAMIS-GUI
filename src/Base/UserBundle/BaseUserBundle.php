<?php

namespace Base\UserBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class BaseUserBundle extends Bundle
{
    public function getParent(){
        return 'FOSUserBundle';
    }
}
