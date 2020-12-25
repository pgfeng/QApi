<?php


namespace Test\App\V100;

use QApi\Attribute\Middleware;
use QApi\Attribute\Route;
use QApi\Request;
use QApi\Response;
use Test\App\Middleware\TestMiddleware;
use Test\Model\usersModel;

#[Route('/user',middleware: TestMiddleware::class)]
class IndexController
{
    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    #[Route(path: '/{id}', methods: ['ALL'], paramPattern: ['id' => '\d+'],middleware: TestMiddleware::class)]
    public function indexAction(Request $request, Response $response): Response
    {
        $response->setData(usersModel::model()->findByPk($request->arguments['id']));
        return $response;
    }
}