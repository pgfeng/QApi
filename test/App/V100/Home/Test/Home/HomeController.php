<?php


namespace Test\App\V100\Home\Test\Home;


use QApi\Attribute\Route;
use QApi\Route\Methods;

class HomeController
{
    #[Route('/aaa',Methods::ALL)]
    public function a(): void
    {
        echo 'App\V100\Home\Test\Home';
    }
}