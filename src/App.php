<?php


namespace QApi;


use ErrorException;
use QApi\Config\Application;
use QApi\Enumeration\CliColor;
use QApi\Exception\CompileErrorException;
use QApi\Exception\CoreErrorException;
use QApi\Exception\CoreWarningException;
use QApi\Exception\DeprecatedException;
use QApi\Exception\NoticeException;
use QApi\Exception\ParseException;
use QApi\Exception\RecoverableErrorException;
use QApi\Exception\StrictException;
use QApi\Exception\UserDeprecatedException;
use QApi\Exception\UserErrorException;
use QApi\Exception\UserNoticeException;
use QApi\Exception\UserWarningException;
use QApi\Exception\WarningException;
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
    public static ?string $apiPassword = null;

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
     * @param string $apiPassword
     * @param Request|null $request
     * @return Response|string
     * @throws CompileErrorException
     * @throws CoreErrorException
     * @throws CoreWarningException
     * @throws DeprecatedException
     * @throws ErrorException
     * @throws NoticeException
     * @throws ParseException
     * @throws RecoverableErrorException
     * @throws StrictException
     * @throws UserDeprecatedException
     * @throws UserErrorException
     * @throws UserNoticeException
     * @throws UserWarningException
     * @throws WarningException
     */
    public static function run(?string $timezone = 'Asia/Shanghai', string $routeDir = 'routes', string $configDir = 'config', string $runtimeDir =
    'runtime', string                  $uploadDir = 'Upload', ?\Closure $getVersionFunction = null, array $allowMethods = [
        Methods::GET, Methods::POST, Methods::DELETE, Methods::HEAD, Methods::PUT
    ], array                           $allowHeaders = ['*'], string $apiPassword = '', Request $request = null): Response|string
    {
        set_error_handler(callback: static function ($err_severity, $err_msg, $err_file, $err_line) {
            if (0 === error_reporting()) {
                return false;
            }
            match ($err_severity) {
                E_ERROR => throw new ErrorException  ($err_msg, 0, $err_severity, $err_file, $err_line),
                E_WARNING => throw new WarningException  ($err_msg, 0, $err_severity, $err_file, $err_line),
                E_PARSE => throw new ParseException  ($err_msg, 0, $err_severity, $err_file, $err_line),
                E_NOTICE => throw new NoticeException  ($err_msg, 0, $err_severity, $err_file, $err_line),
                E_CORE_ERROR => throw new CoreErrorException  ($err_msg, 0, $err_severity, $err_file, $err_line),
                E_CORE_WARNING, E_COMPILE_WARNING => throw new CoreWarningException  ($err_msg, 0, $err_severity, $err_file, $err_line),
                E_COMPILE_ERROR => throw new CompileErrorException  ($err_msg, 0, $err_severity, $err_file, $err_line),
                E_USER_ERROR => throw new UserErrorException  ($err_msg, 0, $err_severity, $err_file, $err_line),
                E_USER_WARNING => throw new UserWarningException  ($err_msg, 0, $err_severity, $err_file, $err_line),
                E_USER_NOTICE => throw new UserNoticeException  ($err_msg, 0, $err_severity, $err_file, $err_line),
                E_STRICT => throw new StrictException  ($err_msg, 0, $err_severity, $err_file, $err_line),
                E_RECOVERABLE_ERROR => throw new RecoverableErrorException  ($err_msg, 0, $err_severity, $err_file, $err_line),
                E_DEPRECATED => throw new DeprecatedException  ($err_msg, 0, $err_severity, $err_file, $err_line),
                E_USER_DEPRECATED => throw new UserDeprecatedException  ($err_msg, 0, $err_severity, $err_file, $err_line),
            };
        }, error_levels: E_ALL);
        try {
            if (!defined('PROJECT_PATH')) {
                define('PROJECT_PATH', $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR);
            }
            self::$timezone = new \DateTimeZone($timezone);
            date_default_timezone_set($timezone);
            self::$apiPassword = trim($apiPassword);
            self::$routeDir = trim($routeDir, '/');
            self::$runtimeDir = trim($runtimeDir, '/');
            self::$configDir = trim($configDir, '/');
            self::$uploadDir = trim($uploadDir, '/') . DIRECTORY_SEPARATOR;
            try {
                if (!self::$app) {
                    self::$app = Config::app();
                }
            } catch (\ErrorException $e) {
                return json_encode([
                    'status' => false,
                    'code' => 500,
                    'msg' => get_class($e) . '：' . $e->getMessage(),
                    'data' => null,
                ], JSON_THROW_ON_ERROR);
            }
            self::$app->init();
            self::$getVersionFunction = $getVersionFunction;
            Router::init($request);
            return \QApi\Router::run();
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            $file = $e->getFile();
            $line = $e->getLine();
            $errorType = str_replace('QApi\\Exception\\', '', get_class($e));
            Logger::error("\x1b[" . CliColor::ERROR . ";1m " . $errorType . "：" . $msg . "\e[0m\n\t\t" . " in " . $file . ' on line ' .
                $line);
            try {
                $response = new Response();
            } catch (\ErrorException $e) {
                $response = new Response();
            }
            $response->setCode(500)->setExtra([
                'status' => false,
                'msg' => $errorType . '：' . $msg,
                'error_msg' => $errorType . '：' . $msg . ' in ' . $file . ' on line ' . $line,
                'data' => null,
            ]);
            return $response;
        } catch (\Error $e) {
            $msg = $e->getMessage();
            $file = $e->getFile();
            $line = $e->getLine();
            $errorType = str_replace('QApi\\Exception\\', '', get_class($e));
            Logger::error("\x1b[" . CliColor::ERROR . ";1m " . $errorType . "：" . $msg . "\e[0m\n\t\t" . " in " . $file . ' on line ' .
                $line);
            try {
                $response = new Response();
            } catch (\ErrorException $e) {
                $response = new Response();
            }
            $response->setCode(500)->setExtra([
                'status' => false,
                'msg' => $errorType . '：' . $msg,
                'error_msg' => $errorType . '：' . $msg . ' in ' . $file . ' on line ' . $line,
                'data' => null,
            ]);
            return $response;
        }
    }
}