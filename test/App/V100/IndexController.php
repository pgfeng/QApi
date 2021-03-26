<?php


namespace Test\App\V100;

use QApi\Attribute\Parameter\PathParam;
use QApi\Attribute\Route;
use QApi\Exception\CacheErrorException;
use QApi\Request;
use QApi\Response;
use Test\App\Middleware\TestMiddleware;

#[Route('/user', middleware: TestMiddleware::class, summary: '用户', description: '用户操作')]
class IndexController
{
    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    #[Route(path: '/{id}', methods: ['ALL'], paramPattern: ['id' => '.+'], middleware: TestMiddleware::class)]
    #[PathParam(name:'id',summary:'用户ID')]
    public function indexAction(Request $request, Response $response): Response
    {
        return $response->setMsg('Hello World！')->setData([

        ]);
    }
}