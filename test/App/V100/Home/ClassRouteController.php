<?php

namespace App\V100\Home;

use QApi\Attribute\Route;
use QApi\Request;
use QApi\Route\Methods;

#[Route('/ClassRoute/{class}', paramPattern: [
    'class'=>'[a-zA-Z_]+',
    'cate_id' => '\d+'
])]
class ClassRouteController
{
    /**
     * 测试
     * @param Request $request
     * @return void
     */
    #[Route('/test/{cate_id}-{id}',methods: Methods::ALL,paramPattern: [
        'id'=>'\d+'
    ])]
    public function c(Request $request):void
    {
        echo $request->arguments;
    }
}