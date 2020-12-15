<?php

use QApi\Router;

Router::ALL(path: '/ClassRoute/{class}/test/{cate_id}-{id}', callback: 'App\V100\Home\ClassRouteController@c')->paramPattern(paramName: 'class', pattern: '[a-zA-Z_]+')->paramPattern(paramName: 'cate_id', pattern: '\d+')->paramPattern(paramName: 'id', pattern: '\d+');
Router::ALL(path: '/aaa', callback: 'App\V100\Home\Test\Home\HomeController@a');
Router::ALL(path: '/Article-{cate_id}-{id}', callback: 'App\V100\Home\Test\HomeController@Article')->paramPattern(paramName: 'id', pattern: '\d+')->paramPattern(paramName: 'cate_id', pattern: '\d+');
Router::ALL(path: '/test/{id}', callback: 'App\V100\TestController@test')->paramPattern(paramName: 'id', pattern: '\d+');
Router::ALL(path: '/', callback: 'App\V100\TestController@index');