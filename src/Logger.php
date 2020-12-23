<?php


namespace QApi;


use DateTimeZone;
use JetBrains\PhpStorm\Pure;
use Monolog\Formatter\LineFormatter;
use QApi\Enumeration\CliColor;

class Logger
{
    public static ?\Monolog\Logger $logger = null;

    /**
     * 初始化
     * @param string $name
     */
    public static function init($name = 'QApi'): void
    {
        self::$logger = new \Monolog\Logger('QApi');
        self::$logger->setTimezone(App::$timezone);
        self::$logger->pushHandler(
            (new \Monolog\Handler\StreamHandler(App::$runtimeDir . DIRECTORY_SEPARATOR . 'Log' . DIRECTORY_SEPARATOR
                . date('Y-m-d')
                .DIRECTORY_SEPARATOR.date('H') . '.log',
                \Monolog\Logger::API,
                true, null, true))->setFormatter(
                    new LineFormatter(
                        "%datetime% %channel%.%level_name% > %message%\n",'[Y-m-d H:i:s]'
                    )
            )
        );
    }

    /**
     * @param array|string $message
     */
    public static function info(array|string $message): void
    {
        if (is_array($message)) {
            $message = json_encode($message, JSON_UNESCAPED_UNICODE);
        }
        error_log(self::getData($message, CliColor::INFO));
        self::$logger->info($message);
    }

    /**
     * @param array|string $message
     */
    public static function warning(array|string $message): void
    {
        if (is_array($message)) {
            $message = json_encode($message, JSON_UNESCAPED_UNICODE);
        }
        error_log(self::getData($message, CliColor::WARNING));
        self::$logger->warning($message);
    }

    /**
     * @param array|string $message
     */
    public static function success(array|string $message): void
    {
        if (is_array($message)) {
            $message = json_encode($message, JSON_UNESCAPED_UNICODE);
        }
        error_log(self::getData($message, CliColor::SUCCESS));
        self::$logger->alert($message);
    }

    /**
     * @param array|string $message
     */
    public static function error(array|string $message): void
    {
        if (is_array($message)) {
            $message = json_encode($message, JSON_UNESCAPED_UNICODE);
        }
        error_log(self::getData($message, CliColor::ERROR));
        self::$logger->error($message);
    }

    /**
     * @param array|string $message
     */
    public static function emergency(array|string $message): void
    {
        if (is_array($message)) {
            $message = json_encode($message, JSON_UNESCAPED_UNICODE);
        }
        error_log(self::getData($message, CliColor::ERROR));
        self::$logger->emergency($message);
    }

    /**
     * @param string $message
     * @param string $type
     * @return string
     */
    #[Pure] public static function getData(string $message, string $type): string
    {
        return "\x1b[" . $type . ";1m " . self::$logger->getName() . ":$message\e[0m";
    }
}