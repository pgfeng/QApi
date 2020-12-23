<?php

use QApi\Router;

Router::ALL(path: '/user/{id}', callback: 'App\V100\IndexController@indexAction')->paramPattern(paramName: 'id', pattern: '\d+');