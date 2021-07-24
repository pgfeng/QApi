<?php


namespace QApi\Command;


use ErrorException;
use QApi\Cache\Cache;
use QApi\Command;
use QApi\Config;
use QApi\Exception\CacheErrorException;

class ClearCacheCommand extends CommandHandler
{

    public string $name = 'clear:cache';
    protected array $cacheConfigKeys;
    protected array $cacheConfig;

    /**
     * ClearCacheCommand constructor.
     * @param Command $command
     * @param array $argv
     * @throws ErrorException
     */
    public function __construct(Command $command, $argv = [])
    {
        parent::__construct($command, $argv);
        Config::cache();
        $this->cacheConfig = Config::$cache;
    }

    /**
     * @param $argv
     * @return mixed
     */
    public function handler($argv): mixed
    {
        $this->argv = $argv;
        if (isset($argv[0]) && $argv[0] === '--all') {
            $this->cacheConfigKeys = array_keys($this->cacheConfig);
        } else if (!isset($argv[0]) || (string)$argv[0] === '') {
            $this->cacheConfigKeys = $this->choseConfig();
        }
        $this->clear();
        return null;
    }


    /**
     * @throws ErrorException
     * @throws CacheErrorException
     */
    public function clear(): void
    {
        if (!$this->cacheConfigKeys) {
            $this->command->error('* You have not selected the cache space to clean up!');
        }
        $languages = [
            'Initialize cache......',
            'Ready to clean......',
            'Clean cache......',
            'Clean up completed!',
        ];
        foreach ($this->cacheConfigKeys as $configName) {
            $this->command->info('Start cleaning ' . $configName);
            usleep(80000);
            $progress = $this->command->cli->blue()->progress()->total(count($languages));
            usleep(80000);
            $progress->current(1, $languages[0]);
            usleep(80000);
            $cache = Cache::initialization($configName);
            usleep(80000);
            $progress->current(2, $languages[1]);
            usleep(80000);
            $progress->current(3, $languages[2]);
            usleep(80000);
            $cache->clear();
            usleep(80000);
            $progress->current(4, $languages[3]);
        }
    }

    /**
     * @param string $msg
     * @return array
     */
    protected function choseConfig($msg = 'Please select the cache space to be cleaned up:'): array
    {
        $cacheConfigKeys = array_keys($this->cacheConfig);
        $input = $this->command->cli->cyan()->checkboxes($msg, $cacheConfigKeys);
        return $input->prompt();
    }

    public function help(): mixed
    {
        $this->command->cli->tab()->blue('Select the cache space to be cleaned up to clean up the corresponding cache space.');
        return null;
    }
}