<?php


namespace QApi;

use QApi\Config\Application;
use QApi\Config\Cache\FileSystem;
use QApi\Config\Cache\SQLite;
use QApi\Config\Database\MysqliDatabase;
use QApi\Config\Database\PdoMysqlDatabase;
use QApi\Config\Database\PdoSqliteDatabase;
use QApi\Config\Database\PdoSqlServDatabase;
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

    /**
     * @return Application
     */
    public static function &app(): Application
    {
        if (self::$app) {
            return self::$app;
        }
        $configPath = PROJECT_PATH . App::$configDir . DIRECTORY_SEPARATOR . 'app.php';
        if (!file_exists($configPath)) {
            mkPathDir($configPath);
            file_put_contents($configPath, file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'Config'
                . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR . 'app.php'), LOCK_EX);
        }
        $appConfig = include PROJECT_PATH . App::$configDir . DIRECTORY_SEPARATOR . 'app.php';
        $appConfig = array_reverse($appConfig);
        $appHosts = array_keys($appConfig);
        $appHostPattern = str_replace('*', '(.+)', $appHosts);
        foreach ($appHosts as $key => $host) {
            if (preg_match('/' . $appHostPattern[$key] . '/i', $_SERVER['HTTP_HOST'])) {
                $var = self::$app = &$appConfig[$host];
                return $var;
            }
        }
        throw new \ErrorException('host ' . $_SERVER['HTTP_HOST'] . ' not bind app!', 0, 1,
            $configPath);
    }

    /**
     * @param string|null $configName
     * @return FileSystem|SQLite|null
     * @throws \ErrorException
     */
    public static function cache(string $configName = null): FileSystem|SQLite|null
    {
        if (!is_cli()) {
            $runMode = self::app()->getRunMode();
        } else if (defined('DEV_MODE') && DEV_MODE === true) {
            $runMode = RunMode::DEVELOPMENT;
        } else {
            $runMode = RunMode::PRODUCTION;
        }
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
     * @return array|string|null
     */
    public static function command($name): string|array|null
    {
        $configPath = PROJECT_PATH . App::$configDir . DIRECTORY_SEPARATOR . 'command.php';
        if (!file_exists($configPath)) {
            mkPathDir($configPath);
            file_put_contents($configPath, file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'Config'
                . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR . 'command.php'), LOCK_EX);
        }
        $commandConfig = include PROJECT_PATH . App::$configDir . DIRECTORY_SEPARATOR . 'command.php';
        if ($name) {

            return $commandConfig[$name] ?? null;
        }

        return $commandConfig;
    }

    public static function versions()
    {
        if (!self::$versions) {
            $versionConfigPath = PROJECT_PATH . App::$configDir . DIRECTORY_SEPARATOR . Config::app()->getRunMode()
                . DIRECTORY_SEPARATOR . 'version.php';
            if (!self::$version && !file_exists($versionConfigPath)) {
                mkPathDir($versionConfigPath);
                file_put_contents($versionConfigPath, file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'Config'
                    . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR . 'version.php'), LOCK_EX);
            }
            self::$versions = include PROJECT_PATH . App::$configDir . DIRECTORY_SEPARATOR . Config::app()->getRunMode()
                . DIRECTORY_SEPARATOR . 'version.php';
        }
        usort(self::$versions, static function ($a, $b) {
            return ($a->version - $b->version) > 0 ? 1 : -1;
        });
        return self::$versions;
    }

    /**
     * @param string|null $configName
     * @return MysqliDatabase|PdoMysqlDatabase|PdoSqliteDatabase|PdoSqlServDatabase|array|null
     * @throws \ErrorException
     */
    public static function database(string $configName = null):
    MysqliDatabase|PdoMysqlDatabase|PdoSqliteDatabase|PdoSqlServDatabase|null|array
    {
        if (!is_cli()) {
            $runMode = Config::app()->getRunMode();
        } else if (defined('DEV_MODE') && DEV_MODE === true) {
            $runMode = RunMode::DEVELOPMENT;
        } else {
            $runMode = RunMode::PRODUCTION;
        }
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
     * 获取其他配置
     * @param $config_name
     * @param $config_key
     * @return mixed
     */
    public static function other(string $config_name, string $config_key = null): mixed
    {
        if (!is_cli()) {
            $runMode = self::app()->getRunMode();
        } else if (defined('DEV_MODE') && DEV_MODE === true) {
            $runMode = RunMode::DEVELOPMENT;
        } else {
            $runMode = RunMode::PRODUCTION;
        }
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
            return self::$other[$config_name][$config_key] ?? null;
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