<?php


namespace QApi\Command;


use QApi\App;
use QApi\Attribute\Utils;
use QApi\Config;
use QApi\Router;

class RouteBuildCommand extends CommandHandler
{

    /**
     * @var string
     */
    public string $name = 'route:build';

    public function handler(array $argv): mixed
    {
        $apps = Config::apps();
        $this->command->cli->info('Start generating application route!');
        $this->command->cli->info()->border();
        foreach ($apps as $host => $app) {
            $this->command->cli->blue('Start generating [' . $host . '] route!');
            $_SERVER['HTTP_HOST'] = $host;
            Config::$app = App::$app = $app;
            $languages = [
                'Start generating application route......',
                'Application route generation completed!',
            ];
            $progress = $this->command->cli->yellow()->progress()->total(count($languages));
            usleep(80000);
            Router::BuildRoute($app->nameSpace);
            usleep(80000);
            $progress->current(1, $languages[0]);
            usleep(80000);
            $progress->current(2, $languages[1]);
        }
        usleep(80000);
        $this->command->cli->info()->border();
        $this->command->cli->info('All application routes have been generated!');
        (new RouteCacheClearCommand($this->command, []))->handler($argv);
        return null;
    }
}