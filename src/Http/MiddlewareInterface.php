<?php


namespace QApi\Http;


use Closure;
use QApi\Request;
use QApi\Response;

interface MiddlewareInterface
{
    // Middleware execution order priority, executed from low to high, if they are the same, executed in the order of addition
    public const PRIORITY = 0;

    /**
     * Middleware processing method
     * @param Request $request
     * @param Response $response
     * @param Closure(Request $request,Response $response):Response $next
     * @return Response|Closure
     */
    public function handle(Request $request, Response $response, Closure $next): Response|Closure;
}