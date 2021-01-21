<?php


namespace QApi;


use QApi\Config\Application;
use QApi\Enumeration\CliColor;
use QApi\Route\Methods;

class App
{
    public static ?Application $app = null;
    public static ?string $routeDir = 'routes';
    public static ?string $configDir = 'config';
    public static ?string $runtimeDir = 'runtime';
    public static ?\DateTimeZone $timezone = null;
    public static ?string $uploadDir = null;
    public static ?\Closure $getVersionFunction = null;

    /**
     * 获取当前版本
     */
    public static function getVersion(): string
    {
        if (self::$getVersionFunction === null) {
            if (!isset($_GET['_ver'])) {
                return Config::app()->getDefaultVersion();
            }
            return (string)$_GET['_ver'];
        }
        $callback = self::$getVersionFunction;
        return $callback(Config::app()->getDefaultVersion());
    }

    /**
     * @param string|null $timezone
     * @param string $routeDir
     * @param string $configDir
     * @param string $runtimeDir
     * @param string $uploadDir
     * @param \Closure|null $getVersionFunction
     * @param array $allowMethods
     * @param array $allowHeaders
     * @return Response|string
     */
    public static function run(?string $timezone = 'Asia/Shanghai', $routeDir = 'routes', $configDir = 'config', $runtimeDir =
    'runtime', $uploadDir = 'Upload', ?\Closure $getVersionFunction = null, array $allowMethods = [
        Methods::GET, Methods::POST, Methods::DELETE, Methods::HEAD, Methods::PUT
    ], array $allowHeaders = ['*']): Response|string
    {
        try {
            if (!defined('PROJECT_PATH')) {
                define('PROJECT_PATH', $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR);
            }
            date_default_timezone_set($timezone);
            self::$routeDir = trim($routeDir, '/');
            self::$runtimeDir = trim($runtimeDir, '/');
            self::$uploadDir = trim($uploadDir, '/') . DIRECTORY_SEPARATOR;
            self::$app = Config::app();
            self::$timezone = new \DateTimeZone('Asia/Shanghai');
            self::$app->init();
            self::$getVersionFunction = $getVersionFunction;
            if (PHP_SAPI !== 'cli') {
                header('Access-Control-Allow-Headers: ' . implode(',', $allowHeaders));
                header('Access-Control-Allow-Methods: ' . implode(',', $allowMethods));
            }
            Router::init();
            return \QApi\Router::run();
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            $file = $e->getFile();
            $line = $e->getLine();
            $errorType = get_class($e);
            Logger::error("\x1b[" . CliColor::ERROR . ";1m " . $errorType . "：" . $msg . "\e[0m\n\t\t" . " in " . $file . ' on line ' .
                $line);
            $response = new Response(Config::version()->versionName);
            $response->setCode(500)->setExtra([
                'status' => false,
                'msg' => $msg,
                'error_msg' => $errorType . '：' . $msg . ' in ' . $file . ' on line ' . $line,
                'data' => null,
            ]);
            return $response;
        } catch (\Error $e) {
            $msg = $e->getMessage();
            $file = $e->getFile();
            $line = $e->getLine();
            $errorType = get_class($e);
            Logger::error("\x1b[" . CliColor::ERROR . ";1m " . $errorType . "：" . $msg . "\e[0m\n\t\t" . " in " . $file . ' on line ' .
                $line);
            $response = new Response(Config::version()->versionName);
            $response->setCode(500)->setExtra([
                'status' => false,
                'msg' => $msg,
                'error_msg' => $errorType . '：' . $msg . ' in ' . $file . ' on line ' . $line,
                'data' => null,
            ]);
            return $response;
        }
    }
}