<?php


namespace QApi;

use ErrorException;
use QApi\Config\Abstracts\Cache;
use QApi\Config\Application;
use QApi\Config\Cache\FileSystem;
use QApi\Config\Cache\SQLite;
use QApi\Config\Database\MysqliDatabase;
use QApi\Config\Database\PdoMysqlDatabase;
use QApi\Config\Database\PdoSqliteDatabase;
use QApi\Config\Database\PdoSqlServDatabase;
use QApi\Config\Database\SqlServDatabase;
use QApi\Config\Version;
use QApi\Enumeration\RunMode;

class Config
{
    public static ?Application $app = null;
    public static ?Version $version = null;
    public static ?array $versions = [];
    public static ?array $databases = [];
    public static ?array $cache = [];
    public static ?array $other = [];
    public static ?array $command = [];
    /**
     * @var Application[]
     */
    public static ?array $apps = null;

    /**
     * @return string
     * @throws ErrorException
     */
    public static function getRunMode(): string
    {
        if (!is_cli()) {
            $runMode = self::app()->getRunMode();
        } elseif (App::$app) {
            $runMode = App::$app->getRunMode();
        } else if (defined('RUN_MODE')) {
            $runMode = RUN_MODE;
        } else if (defined('DEV_MODE') && DEV_MODE === true) {
            $runMode = RunMode::DEVELOPMENT;
        } else {
            $runMode = RunMode::PRODUCTION;
        }
        return $runMode;
    }

    /**
     * @param bool $force 是否重新获取配置
     * @return Application[]
     */
    public static function apps(bool $force = false): array
    {
        if (!self::$apps || $force) {
            $configPath = PROJECT_PATH . App::$configDir . DIRECTORY_SEPARATOR . 'app.php';
            if (!file_exists($configPath)) {
                mkPathDir($configPath);
                file_put_contents($configPath, file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'Config'
                    . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR . 'app.php'), LOCK_EX);
            }
            self::$apps = include $configPath;
            return self::$apps;
        }
        return self::$apps;

    }

    /**
     * @return Application
     * @throws ErrorException
     */
    public static function &app(): Application
    {
        if (self::$app) {
            return self::$app;
        }
        if (App::$app) {
            self::$app = App::$app;
            return self::$app;
        }
        $configPath = PROJECT_PATH . App::$configDir . DIRECTORY_SEPARATOR . 'app.php';
        $appConfig = self::apps();
        $appConfig = array_reverse($appConfig);
        $appHosts = array_keys($appConfig);
        $appHostPattern = str_replace('*', '(.+)', $appHosts);
        foreach ($appHosts as $key => $host) {
            if (preg_match('/^' . $appHostPattern[$key] . '$/i', $_SERVER['HTTP_HOST'])) {
                self::$app = App::$app = &$appConfig[$host];
                return App::$app;
            }
        }
        throw new ErrorException('host ' . $_SERVER['HTTP_HOST'] . ' not bind app!', 0, 1,
            $configPath);
    }

    /**
     * @param string|null $configName
     * @return FileSystem|SQLite|array|null
     * @throws ErrorException
     */
    public static function cache(string $configName = null): Cache|null|array
    {
        $runMode = self::getRunMode();
        if (!self::$cache) {
            $versionConfigPath = PROJECT_PATH . App::$configDir . DIRECTORY_SEPARATOR . $runMode
                . DIRECTORY_SEPARATOR . 'cache.php';
            if (!self::$cache && !file_exists($versionConfigPath)) {
                mkPathDir($versionConfigPath);
                file_put_contents($versionConfigPath, file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'Config'
                    . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR . 'cache.php'), LOCK_EX);
            }
            self::$cache = include PROJECT_PATH . App::$configDir . DIRECTORY_SEPARATOR . $runMode
                . DIRECTORY_SEPARATOR . 'cache.php';
        }
        if (!$configName) {
            return self::$cache;
        }
        return self::$cache[$configName] ?? null;

    }

    /**
     * @param $name
     * @return mixed
     */
    public static function command($name): mixed
    {
        if (self::$command) {
            return self::$command[$name] ?? null;
        }

        $configPath = PROJECT_PATH . App::$configDir . DIRECTORY_SEPARATOR . 'command.php';
        if (!file_exists($configPath)) {
            mkPathDir($configPath);
            file_put_contents($configPath, file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'Config'
                . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR . 'command.php'), LOCK_EX);
        }
        $commandConfig = include PROJECT_PATH . App::$configDir . DIRECTORY_SEPARATOR . 'command.php';
        self::$command = $commandConfig;
        if ($name) {
            return $commandConfig[$name] ?? null;
        }
        return self::$command;
    }

    /**
     * @param string|null $runMode
     * @return Version[]
     * @throws ErrorException
     */
    public static function versions(string $runMode = null): mixed
    {
        if (!self::$versions) {
            $versionConfigPath = PROJECT_PATH . App::$configDir . DIRECTORY_SEPARATOR .
                ($runMode ?: Config::app()->getRunMode())
                . DIRECTORY_SEPARATOR . 'version.php';
            if (!self::$version && !file_exists($versionConfigPath)) {
                mkPathDir($versionConfigPath);
                file_put_contents($versionConfigPath, file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'Config'
                    . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR . 'version.php'), LOCK_EX);
            }
            self::$versions = include PROJECT_PATH . App::$configDir . DIRECTORY_SEPARATOR . ($runMode ?: Config::app()->getRunMode())
                . DIRECTORY_SEPARATOR . 'version.php';
        }
        usort(self::$versions, static function ($a, $b) {
            return ($a->version - $b->version) > 0 ? 1 : -1;
        });
        return self::$versions;
    }

    /**
     * @param string|null $configName
     * @return MysqliDatabase|PdoMysqlDatabase|PdoSqliteDatabase|PdoSqlServDatabase|SqlServDatabase|array|null
     * @throws ErrorException
     */
    public static function database(string $configName = null):
    MysqliDatabase|PdoMysqlDatabase|PdoSqliteDatabase|PdoSqlServDatabase|SqlServDatabase|null|array
    {
        $runMode = self::getRunMode();
        if (!self::$databases) {
            $versionConfigPath = PROJECT_PATH . App::$configDir . DIRECTORY_SEPARATOR . $runMode
                . DIRECTORY_SEPARATOR . 'databases.php';
            if (!self::$databases && !file_exists($versionConfigPath)) {
                mkPathDir($versionConfigPath);
                file_put_contents($versionConfigPath, file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'Config'
                    . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR . 'database.php'), LOCK_EX);
            }
            self::$databases = include PROJECT_PATH . App::$configDir . DIRECTORY_SEPARATOR . $runMode
                . DIRECTORY_SEPARATOR . 'databases.php';
        }
        if (!$configName) {
            return self::$databases;
        }
        return self::$databases[$configName] ?? null;
    }

    /**
     * @return Version
     */
    public static function version(): Version
    {
        if (self::$version) {
            return self::$version;
        }
        $versions = self::versions();
        $versionNumber = App::getVersion();
        /**
         * @var Version $version
         */
        foreach ($versions as $version) {
            if ($versionNumber === $version->versionName) {
                return self::$version = $version;
            }
        }
        Logger::error('version ' . $versionNumber . ' not config!');
        return $version;
    }

    /**
     * 获取路由配置
     * @param string|null $config_key
     * @return mixed
     */
    public static function route(string $config_key = null, $default = null): mixed
    {
        $runMode = self::getRunMode();
        if (!isset(self::$other['route'])) {
            $otherConfigPath = PROJECT_PATH . App::$configDir . DIRECTORY_SEPARATOR . $runMode
                . DIRECTORY_SEPARATOR . 'route.php';
            if (!file_exists($otherConfigPath)) {
                mkPathDir($otherConfigPath);
                file_put_contents($otherConfigPath, file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'Config'
                    . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR . 'route.php'), LOCK_EX);
            }
            self::$other['route'] = include $otherConfigPath;
        }
        if ($config_key) {
            return self::$other['route'][$config_key] ?? $default;
        }
        return self::$other['route'];
    }


    /**
     * 获取其他配置
     * @param string $config_name
     * @param string|null $config_key
     * @param null $default
     * @return mixed
     */
    public static function other(string $config_name, string $config_key = null, $default = null): mixed
    {
        $runMode = self::getRunMode();
        if (!isset(self::$other[$config_name])) {
            $otherConfigPath = PROJECT_PATH . App::$configDir . DIRECTORY_SEPARATOR . $runMode
                . DIRECTORY_SEPARATOR . $config_name . '.php';
            if (!file_exists($otherConfigPath)) {
                mkPathDir($otherConfigPath);
                file_put_contents($otherConfigPath, file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'Config'
                    . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR . 'config.php'), LOCK_EX);
            }
            self::$other[$config_name] = include $otherConfigPath;
        }
        if ($config_key) {
            return self::$other[$config_name][$config_key] ?? $default;
        }
        return self::$other[$config_name];
    }

    /**
     * clear config
     */
    public static function flush(): void
    {
        self::$app = null;
        self::$version = null;
        self::$databases = [];
        self::$other = [];
    }
}