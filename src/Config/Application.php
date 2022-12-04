<?php


namespace QApi\Config;


use Monolog\Formatter\LineFormatter;
use Monolog\Handler\Handler;
use Monolog\Handler\StreamHandler;
use QApi\App;
use QApi\Enumeration\RunMode;
use QApi\Logger;
use QApi\Response;
use QApi\Route\Methods;

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
     * @param array $allowOrigin
     * @param array $allowHeaders
     * @param string $applicationName
     * @param string $scheme
     * @param string $docPassword
     */
    public function __construct(public string      $appDir,
                                public string      $runMode,
                                public string      $defaultVersionName,
                                public ?array      $logHandler = null,
                                public string|bool $nameSpace = false, public array $allowOrigin = ['*'],
                                public array       $allowHeaders = ['Request-Sign', 'Request-Time', 'Content-Type', 'Authorization'],
                                public array       $allowMethods = [
                                    Methods::GET, Methods::POST, Methods::DELETE, Methods::HEAD, Methods::PUT
                                ],
                                public string      $applicationName = '', public string $scheme = 'http', public string $docPassword = '', public array $disabledLogType = [], public array $disabledLogLevel = [])
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
        Logger::$disabledType = $this->disabledLogType;
        Logger::$disabledLevel = $this->disabledLogLevel;
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