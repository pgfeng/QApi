<?php

use QApi\Router;

Router::ALL(path: '/', callback: 'App\V100\TestController@indexAction');