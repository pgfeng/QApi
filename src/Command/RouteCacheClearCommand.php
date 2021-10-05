<?php


namespace QApi\Command;


use QApi\App;
use QApi\Cache\Cache;
use QApi\Cache\CacheInterface;
use QApi\Command;
use QApi\Config;
use QApi\Config\Application;
use QApi\Config\Version;
use QApi\Router;

class RouteCacheClearCommand extends CommandHandler
{


    public string $name = 'route:clearCache';
    private string $baseDir;
    /**
     * @var Application[]
     */
    private array $apps;
    private Application $app;
    /**
     * @var Version[]
     */
    private array $versions;
    private CacheInterface $cache;
    private Version $version;

    public function __construct(Command $command, $argv = [])
    {
        parent::__construct($command, $argv);
        $this->baseDir = PROJECT_PATH . App::$routeDir;
        $this->apps = Config::apps();
    }

    public function handler(array $argv): mixed
    {
        $route = Config::route();
        if (!$route['cache']){
            return $this->command->error('Route Cache is not opened!');
        }
        $languages = [
            'Initialize cache......',
            'Ready to clean......',
            'Clean cache......',
            'Clean up completed!',
        ];
        $this->command->info('Start cleaning route cache!');
        usleep(80000);
        $progress = $this->command->cli->blue()->progress()->total(count($languages));
        usleep(80000);
        $progress->current(1, $languages[0]);
        usleep(80000);
        $cache = new ($route['cacheDriver']->driver)($route['cacheDriver']);
        usleep(80000);
        $progress->current(2, $languages[1]);
        usleep(80000);
        $progress->current(3, $languages[2]);
        usleep(80000);
        $cache->clear();
        usleep(80000);
        $progress->current(4, $languages[3]);
        return null;
    }


    public function help(): mixed
    {
        return null;
        // TODO: Implement help() method.
    }
}