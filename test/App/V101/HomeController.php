<?php


namespace App\V101;


use QApi\Attribute\Route;
use QApi\Route\Methods;

class HomeController
{
    #[Route('/',Methods::ALL)]
    public function index()
    {
        echo 'V1.0.1';
    }
}