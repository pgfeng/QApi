<?php


namespace QApi;


use JetBrains\PhpStorm\Pure;
use QApi\DI\Container;
use QApi\Enumeration\CliColor;
use QApi\Enumeration\RunMode;
use QApi\Logger\LogLevel;
use QApi\Logger\LogType;

class Logger
{
    public static ?\Monolog\Logger $logger = null;

    /**
     * @var array
     */
    public static array $disabledType = [];

    /**
     * @var array
     */
    public static array $disabledLevel = [];

    /**
     * @var array
     */
    public static array $disabledLoggerUri = [
        '__status.json',
        '__apis.json',
        '__router.json',
        '__apiResponse.json',
        '__apiResponses.json',
    ];

    /**
     * 初始化
     * @param string $name
     * @param bool $force
     * @throws \ErrorException
     */
    public static function init(string $name = 'QApi', bool $force = false): void
    {
        if (self::$logger === null || $force) {
            if (App::$app){
                $disabledLoggerUri = App::$app->disabledLoggerUri;
                if (is_array($disabledLoggerUri)) {
                    array_walk($disabledLoggerUri, function (&$value) {
                        $value = trim($value, '/');
                    });
                    self::$disabledLoggerUri = array_merge(self::$disabledLoggerUri, $disabledLoggerUri);
                }
            }
            self::$logger = new \Monolog\Logger($name);
            self::$logger->setTimezone(App::$timezone ?? new \DateTimeZone('Asia/Shanghai'));
            if (is_cli()) {
                $logHandler = Config::command('logHandler');
                if (is_array($logHandler)) {
                    foreach ($logHandler as $item) {
                        self::$logger->pushHandler($item);
                    }
                } elseif (is_object($logHandler)) {
                    self::$logger->pushHandler($logHandler);
                }
            } else if (Config::$app) {
                foreach (Config::$app->logHandler as $item) {
                    self::$logger->pushHandler($item);
                }
            } else {
                $app = Config::app();
                foreach ($app->logHandler as $item) {
                    self::$logger->pushHandler($item);
                }
            }
        }
    }

    /**
     * @return string
     */
    #[Pure(true)] private static function getRunMode(): string
    {
        if (is_cli()) {
            if (Command::$showLogger) {
                if (App::$app) {
                    return App::$app->getRunMode();
                }
                if ((defined('DEV_MODE') && DEV_MODE === true)) {
                    return defined('RUN_MODE') ? RUN_MODE : (RunMode::DEVELOPMENT);
                }
                return defined('RUN_MODE') ? RUN_MODE : (RunMode::PRODUCTION);
            } else {
                return RunMode::PRODUCTION;
            }
        }

        return App::$app->getRunMode();
    }

    public static function log($level, array|string $message, array $context = []): void
    {
        if (self::disabledLoggerUriStatus()) {
            return;
        }
        if (!in_array(LogLevel::INFO, self::$disabledLevel)) {
            if (is_array($message)) {
                $message = json_encode($message, JSON_UNESCAPED_UNICODE);
            }
            if (self::getRunMode() === RunMode::DEVELOPMENT) {
                error_log(self::getData($message, CliColor::INFO), 4);
            }
            self::$logger?->log($level, preg_replace('/\\x1b(.+)\s/iUs', '', $message), $context);
        }
    }

    /**
     * @param array|string $message
     */
    public static function info(array|string $message, array $context = []): void
    {
        if (self::disabledLoggerUriStatus()) {
            return;
        }
        if (!in_array(LogLevel::INFO, self::$disabledLevel)) {
            if (is_array($message)) {
                $message = json_encode($message, JSON_UNESCAPED_UNICODE);
            }
            if (self::getRunMode() === RunMode::DEVELOPMENT) {
                error_log(self::getData($message, CliColor::INFO), 4);
            }
            self::$logger?->info(preg_replace('/\\x1b(.+)\s/iUs', '', $message), $context);
        }
    }

    public static function alert(array|string $message, array $context = []): void
    {
        if (self::disabledLoggerUriStatus()) {
            return;
        }
        if (!in_array(LogLevel::INFO, self::$disabledLevel)) {
            if (is_array($message)) {
                $message = json_encode($message, JSON_UNESCAPED_UNICODE);
            }
            if (self::getRunMode() === RunMode::DEVELOPMENT) {
                error_log(self::getData($message, CliColor::INFO), 4);
            }
            self::$logger?->alert(preg_replace('/\\x1b(.+)\s/iUs', '', $message), $context);
        }
    }

    /**
     * @param array|string $message
     */
    public static function sql(array|string $message, array $context = []): void
    {
        if (self::disabledLoggerUriStatus()) {
            return;
        }
        if (!in_array(LogType::SQL, self::$disabledType)) {
            if (is_array($message)) {
                $message = json_encode($message, JSON_UNESCAPED_UNICODE);
            }
            if (self::getRunMode() === RunMode::DEVELOPMENT) {
                error_log(self::getData(' SQL => ' . $message, CliColor::WARNING), 4);
            }
            self::$logger?->info(' SQL => ' . preg_replace('/\\x1b(.+)\s/iUs', '', $message), $context);
        }
    }

    /**
     * @param array|string $message
     */
    public static function cache(array|string $message): void
    {
        if (self::disabledLoggerUriStatus()) {
            return;
        }
        if (!in_array(LogType::CACHE, self::$disabledType)) {
            if (is_array($message)) {
                $message = json_encode($message, JSON_UNESCAPED_UNICODE);
            }
            if (self::getRunMode() === RunMode::DEVELOPMENT) {
                error_log(self::getData(' CACHE => ' . $message, CliColor::SUCCESS), 4);
            }
            self::$logger?->info(' CACHE => ' . preg_replace('/\\x1b(.+)\s/iUs', '', $message));
        }
    }


    /**
     * @param array|string $message
     */
    public static function warning(array|string $message, array $context = []): void
    {
        if (self::disabledLoggerUriStatus()) {
            return;
        }
        if (!in_array(LogLevel::WARNING, self::$disabledLevel)) {
            if (is_array($message)) {
                $message = json_encode($message, JSON_UNESCAPED_UNICODE);
            }
            if (self::getRunMode() === RunMode::DEVELOPMENT) {

                error_log(self::getData($message, CliColor::WARNING), 4);
            }
            self::$logger?->warning(preg_replace('/\\x1b(.+)\s/iUs', '', $message), $context);
        }
    }


    private static function disabledLoggerUriStatus(): bool
    {
        /**
         * @var Request $request
         */
        $request = Container::G()->get(Request::class);
        if (!$request) {
            return false;
        }
        $uri = Container::G()->get(Request::class)->server->get('REQUEST_URI');
        $uri = trim(explode('?', $uri)[0],'/');
        return in_array($uri, self::$disabledLoggerUri);
    }

    /**
     * @param array|string $message
     */
    public static function router(array|string $message): void
    {
        if (self::disabledLoggerUriStatus()) {
            return;
        }
        if (!in_array(LogType::ROUTER, self::$disabledType)) {
            if (is_array($message)) {
                $message = json_encode($message, JSON_UNESCAPED_UNICODE);
            }
            $message = ' Router => ' . $message;
            if (self::getRunMode() === RunMode::DEVELOPMENT) {
                error_log(self::getData($message, CliColor::SUCCESS), 4);
            }
            self::$logger?->alert(preg_replace('/\\x1b(.+)\s/iUs', '', $message));

        }
    }


    /**
     * @param array|string $message
     */
    public static function request(array|string $message): void
    {
        if (self::disabledLoggerUriStatus()) {
            return;
        }
        if (!in_array(LogType::REQUEST, self::$disabledType)) {
            if (is_array($message)) {
                $message = json_encode($message, JSON_UNESCAPED_UNICODE);
            }
            $message = ' Request => ' . $message;
            if (self::getRunMode() === RunMode::DEVELOPMENT) {
                error_log(self::getData($message, CliColor::INFO), 4);
            }
            self::$logger?->alert(preg_replace('/\\x1b(.+)\s/iUs', '', $message));
        }
    }

    /**
     * @param array|string $message
     */
    public static function response(array|string $message): void
    {
        if (self::disabledLoggerUriStatus()) {
            return;
        }
        if (!in_array(LogType::RESPONSE, self::$disabledType)) {
            if (is_array($message)) {
                $message = json_encode($message, JSON_UNESCAPED_UNICODE);
            }
            $message = ' Response => ' . $message;
            if (self::getRunMode() === RunMode::DEVELOPMENT) {
                error_log(self::getData($message, CliColor::INFO), 4);
            }
            self::$logger?->alert(preg_replace('/\\x1b(.+)\s/iUs', '', $message));
        }
    }

    /**
     * @param array|string $message
     */
    public static function success(array|string $message): void
    {
        if (self::disabledLoggerUriStatus()) {
            return;
        }
        if (!in_array(LogLevel::SUCCESS, self::$disabledLevel)) {
            if (is_array($message)) {
                $message = json_encode($message, JSON_UNESCAPED_UNICODE);
            }
            if (self::getRunMode() === RunMode::DEVELOPMENT) {
                error_log(self::getData($message, CliColor::SUCCESS), 4);
            }
            self::$logger?->alert(preg_replace('/\\x1b(.+)\s/iUs', '', $message));
        }
    }

    /**
     * @param array|string $message
     */
    public static function error(array|string $message): void
    {
        if (self::disabledLoggerUriStatus()) {
            return;
        }
        if (!in_array(LogLevel::ERROR, self::$disabledLevel)) {
            self::init();
            if (is_array($message)) {
                $message = json_encode($message, JSON_UNESCAPED_UNICODE);
            }

            if (self::getRunMode() === RunMode::DEVELOPMENT) {
                error_log(self::getData($message, CliColor::ERROR), 4);
            }
            self::$logger?->error(preg_replace('/\\x1b(.+)\s/iUs', '', $message));
        }
    }

    /**
     * @param array|string $message
     */
    public static function notice(array|string $message, array $context = []): void
    {
        if (self::disabledLoggerUriStatus()) {
            return;
        }
        if (!in_array(LogLevel::NOTICE, self::$disabledLevel)) {
            self::init();
            if (is_array($message)) {
                $message = json_encode($message, JSON_UNESCAPED_UNICODE);
            }

            if (self::getRunMode() === RunMode::DEVELOPMENT) {
                error_log(self::getData($message, CliColor::ERROR), 4);
            }
            self::$logger?->notice(preg_replace('/\\x1b(.+)\s/iUs', '', $message), $context);
        }
    }


    /**
     * @param array|string $message
     */
    public static function emergency(array|string $message): void
    {
        if (self::disabledLoggerUriStatus()) {
            return;
        }
        if (!in_array(LogLevel::EMERGENCY, self::$disabledLevel)) {
            if (is_array($message)) {
                $message = json_encode($message, JSON_UNESCAPED_UNICODE);
            }
            if (self::getRunMode() === RunMode::DEVELOPMENT) {

                error_log(self::getData($message, CliColor::ERROR), 4);
            }
            self::$logger?->emergency(preg_replace('/\\x1b(.+)\s/iUs', '', $message));
        }
    }

    /**
     * @param string $message
     * @param string $type
     * @return string
     */
    public static function getData(string $message, string $type): string
    {
        return (App::$logTime ? '[' . date('Y-m-d H:i:s') . ']' : '') . "\x1b[" . $type . ";1m " . self::$logger->getName() . ":$message\e[0m";
    }
}