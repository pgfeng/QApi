<?php

use QApi\Router;

Router::ALL(path: '/user/{id}', callback: 'Test\App\V100\IndexController@indexAction')->addMiddleware(middleware: 'Test\App\Middleware\TestMiddleware',isClass: true)->addMiddleware(middleware: 'Test\App\Middleware\TestMiddleware')->paramPattern(paramName: 'id', pattern: '\d+');
Router::ALL(path: '/test/{id}', callback: 'Test\App\V100\TestController@testAction')->paramPattern(paramName: 'id', pattern: '\d+')->addMiddleware(middleware: 'Test\App\Middleware\TestMiddleware');
Router::ALL(callback: 'Test\App\V100\TestController@indexAction')->addMiddleware(middleware: 'Test\App\Middleware\TestMiddleware',isClass: true)->addMiddleware(middleware: 'Test\App\Middleware\TestMiddleware');