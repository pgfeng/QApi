<?php

use QApi\Router;

Router::ALL(path: '/user/{id}', callback: 'Test\App\V100\IndexController@indexAction')->addMiddleware(middleware: 'Test\App\Middleware\TestMiddleware')->addMiddleware(middleware: 'Test\App\Middleware\TestMiddleware')->paramPattern(paramName: 'id', pattern: '\d+');