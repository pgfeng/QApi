<?php


namespace QApi\Config;


use Monolog\Formatter\LineFormatter;
use Monolog\Handler\Handler;
use Monolog\Handler\StreamHandler;
use QApi\App;
use QApi\Enumeration\RunMode;
use QApi\Logger;

/**
 * Class Application
 * @package QApi\Config
 */
class Application
{

    /**
     * Application constructor.
     * @param string $appDir
     * @param string $runMode
     * @param string $defaultVersionName
     * @param Handler[]|null $logHandler
     * @param string|bool $nameSpace
     * @param string|array $allowOrigin
     */
    public function __construct(private string $appDir,
                                private string $runMode,
                                private string $defaultVersionName,
                                public ?array $logHandler = null,
                                private string|bool $nameSpace = false, private string|array $allowOrigin = '*')
    {
        $this->appDir = trim($this->appDir, '/');
        if ($this->nameSpace === false) {
            $this->nameSpace = str_replace('/', '\\', $this->appDir);
        }
    }

    /**
     * 初始化项目
     */
    public function init(): void
    {
        Logger::init();
        header('X-Powered-By: QApi');
        if (is_string($this->allowOrigin) && $this->allowOrigin) {
            header('Access-Control-Allow-Origin:' . $this->allowOrigin);
        } else if (is_array($this->allowOrigin)) {
            header('Access-Control-Allow-Origin:' . implode($this->allowOrigin));
        }
        if ($this->runMode === RunMode::DEVELOPMENT) {
            error_reporting(E_ALL);
        } else {
            error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
        }
    }

    public function getDir(): string
    {
        return $this->appDir;
    }

    public function getRunMode(): string
    {
        return $this->runMode;
    }

    /**
     * @return bool
     */
    public function isDev(): bool
    {
        return $this->runMode === RunMode::DEVELOPMENT;
    }

    public function getNameSpace(): string
    {
        return $this->nameSpace;
    }

    public function getDefaultVersion(): string
    {
        return $this->defaultVersionName;
    }
}