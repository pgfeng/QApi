<?php


namespace QApi\Config;


use Monolog\Formatter\LineFormatter;
use Monolog\Handler\Handler;
use Monolog\Handler\StreamHandler;
use QApi\App;
use QApi\Enumeration\RunMode;
use QApi\Logger;
use QApi\Response;

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
     * @param string $applicationName
     * @param string $scheme
     */
    public function __construct(public string $appDir,
                                public string $runMode,
                                public string $defaultVersionName,
                                public ?array $logHandler = null,
                                public string|bool $nameSpace = false, public string|array $allowOrigin = '*',
                                public string $applicationName = '', public string $scheme = 'http')
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
        if (!is_cli()) {
            header('X-Powered-By: QApi');
            if (is_string($this->allowOrigin) && $this->allowOrigin) {
                header('Access-Control-Allow-Origin:' . $this->allowOrigin);
            } else if (is_array($this->allowOrigin)) {
                if (in_array('*', $this->allowOrigin, true)) {
                    header('Access-Control-Allow-Origin: * ');
                } else if (in_array($_SERVER['HTTP_HOST'], $this->allowOrigin, true)) {
                    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_HOST']);
                }
            }
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