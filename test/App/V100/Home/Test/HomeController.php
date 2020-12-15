<?php


namespace App\V100\Home\Test;


use QApi\Attribute\Route;
use QApi\Request;
use QApi\Route\Methods;

class HomeController
{
    /**
     * @param Request $request
     * @return mixed
     */
    #[
        Route('/Article-{cate_id}-{id}', Methods::ALL, paramPattern: [
        'id' => '\d+',
        'cate_id' => '\d+'
        ]),
    ]
    public function Article(Request $request): mixed
    {
        echo $request->arguments;
        return null;
    }
}