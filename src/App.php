<?php


namespace QApi;


use QApi\Config\Application;

class App
{
    public static ?Application $app = null;
    public static ?string $routeDir = 'routes';
    public static ?string $configDir = 'config';

    /**
     * 获取当前版本
     */
    public static function getVersion(): string
    {
        if (!isset($_GET['_version'])) {
            return Config::app()->getDefaultVersion();
        }
        return (string)$_GET['_version'];
    }

    public static function run($routeDir = 'routes', $configDir = 'config'): void
    {
        self::$routeDir = $routeDir;
        define('PROJECT_PATH', $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR);
        set_exception_handler(static function ($e) {
            $message = $e->getMessage();
            $file = $e->getFile();
            $line = $e->getLine();
            $errorType = get_class($e);
            error_log("\x1b[31;1m " . $errorType . "：" . $message . "\e[0m\n\t\t" . " in " . $file . ' on line ' .
                $line, 0);
            $message = [
                'code' => 500,
                'status' => false,
                'msg' => $message,
                'error_msg' => $errorType . '：' . $message . ' in ' . $file . ' on line ' . $line,
                'debug_backtrace' => debug_backtrace(),
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
            error_log("\x1b[31;1m {$errorType}：$msg\e[0m\n\t\t" . " in " . $file . ' on line ' . $line, 0);
            $message = [
                'code' => 500,
                'status' => false,
                'msg' => $msg,
                'error_msg' => $errorType . '：' . $msg . ' in ' . $file . ' on line ' . $line,
                'debug_backtrace' => debug_backtrace(),
            ];
            echo new Data($message);
            exit();
        }, E_ALL);
        self::$app = Config::app();

        Router::init();

        \QApi\Router::run();
    }

}