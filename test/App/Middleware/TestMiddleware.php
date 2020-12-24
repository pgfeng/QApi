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
        $user = usersModel::model()->findByPk($request->arguments['id']);
        if (!$user){
            return $response->setMessage('用户不存在')->fail();
        }
        return $next($request, $response);
    }
}