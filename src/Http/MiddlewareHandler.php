<?php


namespace QApi\Http;


use QApi\Request;
use QApi\Response;

abstract class MiddlewareHandler
{
    abstract public function handle(Request $request, Response $response, \Closure $next): Response|\Closure;
}