<?php


namespace QApi\Command;

use QApi\Config;

/**
 * Class RouteCacheClearCommand
 * @package QApi\Command
 */
class RouteCacheClearCommand extends CommandHandler
{

    /**
     * @var string
     */
    public string $name = 'route:clearCache';

    /**
     * @param array $argv
     * @return mixed
     */
    public function handler(array $argv): mixed
    {
        $route = Config::route();
        if (!$route['cache']) {
            $this->command->error('Route Cache is not opened!');
            return null;
        }
        $languages = [
            'Initialize route cache......',
            'Ready to clean......',
            'Clean routing cache......',
            'Routing cache cleanup completed!',
        ];
        $this->command->info('Start cleaning up routing cache!');
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

    /**
     * @return mixed
     */
    public function help(): mixed
    {
        return null;
        // TODO: Implement help() method.
    }
}