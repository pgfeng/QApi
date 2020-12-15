<?php


namespace QApi\Config;


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