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
     * @throws \ErrorException
     */
    public static function run(?string $timezone = 'Asia/Shanghai', $routeDir = 'routes', $configDir = 'config', $runtimeDir =
    'runtime', $uploadDir = 'Upload', ?\Closure $getVersionFunction = null, array $allowMethods = [
        Methods::GET, Methods::POST, Methods::DELETE, Methods::HEAD, Methods::PUT
    ], array $allowHeaders = ['*']): void
    {
        if (!defined('PROJECT_PATH')) {
            define('PROJECT_PATH', $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR);
        }
        date_default_timezone_set($timezone);
        self::$routeDir = trim($routeDir, '/');
        self::$runtimeDir = trim($runtimeDir, '/');
        self::$uploadDir = PROJECT_PATH . DIRECTORY_SEPARATOR . trim($uploadDir, '/') . DIRECTORY_SEPARATOR;
        self::$app = Config::app();
        self::$timezone = new \DateTimeZone('Asia/Shanghai');
        self::$app->init();
        header('Access-Control-Allow-Headers', implode(',', $allowHeaders));
        header('Access-Control-Allow-Methods:' . implode(',', $allowMethods));
        self::$getVersionFunction = $getVersionFunction;
        set_exception_handler(static function ($e) {
            $message = $e->getMessage();
            $file = $e->getFile();
            $line = $e->getLine();
            $errorType = get_class($e);
            error_log("\x1b[" . CliColor::ERROR . ";1m " . $errorType . "：" . $message . "\e[0m\n\t\t" . " in " . $file . ' on line ' .
                $line, 0);
            $message = [
                'code' => 500,
                'version' => Config::version()->versionName,
                'status' => false,
                'message' => $message,
                'error_msg' => $errorType . '：' . $message . ' in ' . $file . ' on line ' . $line,
                //                'debug_backtrace' => debug_backtrace(),
            ];
            echo new Data($message);
            exit();
        });
        set_error_handler(static function ($no, $msg, $file, $line) {
            $errorType = match ($no) {
                E_ERROR => 'E_ERROR',
                E_WARNING => 'E_WARNING',
                E_PARSE => 'E_PARSE',
                E_NOTICE => 'E_NOTICE',
                E_CORE_ERROR => 'E_CORE_ERROR',
                E_CORE_WARNING => 'E_CORE_WARNING',
                E_COMPILE_ERROR => 'E_COMPILE_ERROR',
                E_COMPILE_WARNING => 'E_COMPILE_WARNING',
                E_USER_ERROR => 'E_USER_ERROR',
                E_USER_WARNING => 'E_USER_WARNING',
                E_USER_NOTICE => 'E_USER_NOTICE',
                E_STRICT => 'E_STRICT',
                E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
                E_DEPRECATED => 'E_DEPRECATED',
                E_USER_DEPRECATED => 'E_USER_DEPRECATED',
                E_ALL => 'E_ALL',
            };
            error_log("\x1b[" . CliColor::ERROR . ";1m {$errorType}：$msg\e[0m\n\t\t" . " in " . $file . ' on line ' . $line, 0);
            $message = [
                'code' => 500,
                'version' => Config::version()->versionName,
                'status' => false,
                'message' => $msg,
                'error_msg' => $errorType . '：' . $msg . ' in ' . $file . ' on line ' . $line,
                //                'debug_backtrace' => debug_backtrace(),
            ];
            echo new Data($message);
            exit();
        }, E_ALL);

        Router::init();

        \QApi\Router::run();
    }

}