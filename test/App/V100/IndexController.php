<?php


namespace App\V100;


use Gregwar\Captcha\CaptchaBuilder;
use QApi\Attribute\Route;
use QApi\Request;
use QApi\Response;

#[Route(
    '/h'
)]
class IndexController
{
    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    #[Route(
        '/2'
    )]
    public function indexAction(Request $request, Response $response): Response
    {
        $response->setData([
            '测试'
        ])->setMessage('操作成功！');
        return $response;
    }
}