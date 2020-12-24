<?php


namespace Test\App\Middleware;


use QApi\Http\MiddlewareHandler;
use QApi\Http\Request\MethodsEnum;
use QApi\Request;
use QApi\Response;
use Test\Model\usersModel;

class TestMiddleware extends MiddlewareHandler
{
    public function handle(Request $request, Response $response, \Closure $next): Response
    {
        if ($request->method !== MethodsEnum::METHOD_GET) {
            return $response->fail()->setMessage('请求类型错误！');
        }
        $user = usersModel::model()->findByPk($request->arguments['id']);
        if (!$user) {
            return $response->setMessage('用户不存在')->fail();
        }
        /**
         * @var Response $response
         */
        $response = $next($request, $response);
        if ($response->getStatus() === false) {
            $response->setMessage('操作失败！');
        } else {
            $response->setMessage('操作成功！');
        }
        return $response;
    }
}