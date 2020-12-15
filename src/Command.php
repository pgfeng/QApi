<?php

namespace QApi;
/**
 * Class Command
 * @package QApi
 */
#[Route("/api/posts/")]
class Command
{

    public function __construct()
    {
    }


    #[Route("{string id}.html", methods: ["GET"])]
    public function test()
    {

    }

    #[Route("test1", methods: ['POST'])]
    public function test1()
    {

    }

    #[Route("test2", methods: ['POST'])]
    public function test2()
    {

    }

    #[Route("test3", methods: ['POST'])]
    public function test3()
    {

    }

    #[Route("test4", methods: ['POST'])]
    public function test4()
    {

    }
}