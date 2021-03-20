<?php


namespace QApi\Http;


use QApi\Request;
use QApi\Response;

interface MiddlewareInterface
{
    public function handle(Request $request, Response $response, \Closure $next): Response|\Closure;
}