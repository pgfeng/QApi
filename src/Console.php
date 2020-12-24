<?php


namespace QApi;


use QApi\Command\RouteCommand;
use Symfony\Component\Console\Application;

class Console
{
    public function __construct()
    {
        App::command();
        $application = new Application();
        $application->add(new RouteCommand());
        $application->run();
        dump(App::$routeDir);
    }
}