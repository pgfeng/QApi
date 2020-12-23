<?php


namespace App\V100;

use Model\usersModel;
use QApi\Attribute\Route;
use QApi\Request;
use QApi\Response;

#[Route(
    '/user'
)]
class IndexController
{
    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    #[Route(
        '/{id}'
    , paramPattern: [
        'id' => '\d+'
    ])]
    public function indexAction(Request $request, Response $response): Response
    {
        $response->setData(usersModel::model()->findByPk($request->arguments['id']));
        return $response;
    }


}