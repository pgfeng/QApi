<?php


namespace QApi;


use QApi\Command\ColumnCommand;
use QApi\Command\RouteCommand;
use Symfony\Component\Console\Application;

class ConsoleApp
{
    public function __construct()
    {
        $application = new Application();
        $application->add(new RouteCommand());
        $application->add(new ColumnCommand());
        $application->run();
    }
}