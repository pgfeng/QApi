<?php


namespace QApi\Command;


use QApi\App;
use QApi\Attribute\Route;
use QApi\Attribute\Utils;
use QApi\Cache\Cache;
use QApi\Config;
use Test\App\V100\IndexController;

class DocBuildCommand extends CommandHandler
{
    public string $name = 'doc:build';

    public function handler($argv): mixed
    {
        return Utils::rebuild($this->command);
    }
}