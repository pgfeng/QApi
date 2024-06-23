<?php

namespace QApi;

use Exception;
use QApi\Console\Command;
use QApi\Console\db\DatabaseBackupCommand;
use QApi\Console\db\DatabaseRestorationCommand;
use QApi\Console\DocumentSystemUpdateCommand;
use QApi\Console\make\ColumnCommand;
use QApi\Console\make\DocumentCommand;
use QApi\Console\make\ModelCommand;
use QApi\Console\RunCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputOption;

class Console
{
    private Application $application;

    public function __construct(?string $timezone = 'Asia/Shanghai', string $routeDir = 'routes', string $configDir = 'config', string $runtimeDir =
    'runtime')
    {
        $this->application = new Application('QApi Console Tool');
        $this->application->getDefinition()->addOption(new InputOption('mode', 'm', InputOption::VALUE_OPTIONAL, '[dev|test|prod|debug|...]Runtime environment mode.', null));
        error_reporting(E_ALL ^ E_NOTICE);
        App::$timezone = new \DateTimeZone($timezone);
        date_default_timezone_set($timezone);
        Logger::init('QApiConsole');
        App::$routeDir = trim($routeDir, '/');
        App::$runtimeDir = trim($runtimeDir, '/');
        App::$configDir = trim($configDir, '/');
        $this->add(new RunCommand());
        $this->add(new ColumnCommand());
        $this->add(new DocumentSystemUpdateCommand());
        $this->add(new DocumentCommand());
        $this->add(new ModelCommand());
        $this->add(new DatabaseBackupCommand());
        $this->add(new DatabaseRestorationCommand());

        $Handlers = Config::command('CommandHandlers');

        /**
         * 将配置中的handle导入
         */
        foreach ($Handlers as $handle) {
            try {
                $handle = new $handle();
                if ($handle instanceof Command) {
                    $this->add($handle);
                }
            } catch (\Error $e) {
            }
        }
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