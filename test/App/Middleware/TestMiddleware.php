<?php


namespace Test\App\Middleware;


use QApi\Http\MiddlewareHandler;
use QApi\Request;
use QApi\Response;
use Test\Model\usersModel;

class TestMiddleware extends MiddlewareHandler
{
    public function handle(Request $request, Response $response, \Closure $next): Response
    {
        if (!$request->arguments['id']){
            $request->arguments['id'] = 1;
        }
        $user = usersModel::model()->findByPk($request->arguments['id']);
        if (!$user) {
            return $response->setMsg('用户不存在')->fail();
        }
        /**
         * @var Response $response
         */
        $response = $next($request, $response);
        if ($response->getStatus() === false) {
            $response->setMsg('操作失败！');
        } else {
            $response->setMsg('操作成功！');
        }
        return $response;
    }
}