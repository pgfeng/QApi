<?php


namespace App\V101;


use QApi\Attribute\Route;
use QApi\Request;
use QApi\Response;

class IndexController
{
    #[Route(
        '/aaa'
    )]
    public function indexAction(Request $request,Response $response): Response
    {
        return $response->setData($request->get)->fail();
    }
}