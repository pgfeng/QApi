<?php


namespace QApi;

use JetBrains\PhpStorm\ArrayShape;
use QApi\Config\Application;
use QApi\Config\Version;

class Config
{
    public static ?Application $app = null;
    public static ?Version $version = null;
    public static ?array $versions = [];

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
     *
     */
    public static function version(): Version
    {
        if (self::$version) {
            return self::$version;
        }
        $versions = self::versions();
        $versionNumber = App::getVersion();
        foreach ($versions as $version) {
            if ($versionNumber === $version->versionName) {
                return self::$version = $version;
            }
        }
        throw new \ErrorException('version ' . $versionNumber . ' not config!', 0, 1,
            PROJECT_PATH . App::$configDir . DIRECTORY_SEPARATOR . Config::app()->getRunMode()
            . DIRECTORY_SEPARATOR . 'version.php');
        //        return self::$version;
    }
}