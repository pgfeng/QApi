<?php


namespace QApi\Config;


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
     * @param string|bool $nameSpace
     */
    public function __construct(private string $appDir,
                                private string $runMode,
                                private string $defaultVersionName,
                                private string|bool $nameSpace = false)
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

    public function getNameSpace(): string
    {
        return $this->nameSpace;
    }

    public function getDefaultVersion(): string
    {
        return $this->defaultVersionName;
    }
}