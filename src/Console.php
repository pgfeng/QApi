<?php

namespace QApi;

use Exception;
use QApi\Console\make\ColumnCommand;
use QApi\Console\RunCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use function Co\run;

class Console
{
    private Application $application;

    public function __construct(?string $timezone = 'Asia/Shanghai', string $routeDir = 'routes', string $configDir = 'config', string $runtimeDir =
    'runtime')
    {
        $this->application = new Application('QApi Console Tool');
        error_reporting(E_ALL ^ E_NOTICE);
        App::$timezone = new \DateTimeZone($timezone);
        date_default_timezone_set($timezone);
        Logger::init('QApi-CLI');
        App::$routeDir = trim($routeDir, '/');
        App::$runtimeDir = trim($runtimeDir, '/');
        App::$configDir = trim($configDir, '/');
        $this->add(new RunCommand());
        $this->add(new ColumnCommand());
    }

    /**
     * @param Command $command
     * @return $this
     */
    public function add(Command $command): self
    {
        $this->application->add($command);
        return $this;
    }

    /**
     * @param string|null $namespace
     * @return Command[]
     */
    public function all(string $namespace = null): array
    {
        return $this->application->all($namespace);
    }

    /**
     * @throws Exception
     */
    public function run(): int
    {
        return $this->application->run();
    }
}