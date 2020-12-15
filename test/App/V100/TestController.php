<?php

namespace App\V100;

use QApi\App;
use QApi\Attribute\Route;
use QApi\Request;
use QApi\Route\Methods;

/**
 * Class TestController
 */
#[Route('/', paramPattern: [])]
class TestController
{
    /**
     * @param Request $request
     */
    #[Route('test/{id}', [Methods::ALL], paramPattern: [
        'id' => '\d+'
    ])]
    public function test(Request $request): void
    {
        echo $request->arguments;
        echo $request->method;
        echo 'V1.0.0';
    }

    /**
     * 首页
     */
    #[Route('')]
    public function index(Request $request):void
    {
        echo $request->method;
        echo App::getVersion();
    }
}