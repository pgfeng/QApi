<?php

namespace App\V102;

use QApi\App;
use QApi\Attribute\Route;
use QApi\Route\Methods;

class HomeController
{

    #[Route('/', Methods::ALL)]
    public function test()
    {
        echo 'V1.0.2';
    }

    /**
     *
     */
    #[Route('/test2', Methods::ALL)]
    public function test2(){
        echo App::getVersion().' test2';
    }
}