<?php

namespace Test\App\V100;

use QApi\App;
use QApi\Attribute\Route;
use QApi\Request;
use QApi\Response;
use QApi\Route\Methods;
use Test\App\Middleware\TestMiddleware;

/**
 * Class TestController
 */
#[Route(middleware: TestMiddleware::class)]
class TestController
{
    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    #[Route(path:'/test/{id}', methods:Methods::ALL, paramPattern: [
        'id' => '\d+'
    ])]
    public function testAction(Request $request,Response $response): Response
    {
        $response->setData($request->arguments['id']);
        return $response;
    }

    /**
     * 首页
     */
    public function indexAction(Request $request): void
    {
        echo $request->method;
        echo App::getVersion();
    }
}