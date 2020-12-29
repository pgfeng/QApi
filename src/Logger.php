<?php


namespace QApi;


use JetBrains\PhpStorm\Pure;
use QApi\Enumeration\CliColor;
use QApi\Enumeration\RunMode;

class Logger
{
    public static ?\Monolog\Logger $logger = null;

    /**
     * 初始化
     * @param string $name
     */
    public static function init($name = 'QApi'): void
    {
        if (Logger::$logger === null) {
            self::$logger = new \Monolog\Logger('QApi');
            self::$logger->setTimezone(App::$timezone);
            foreach (Config::$app->logHandler as $item) {
                self::$logger->pushHandler($item);
            }
        }
    }

    /**
     * @param array|string $message
     */
    public static function info(array|string $message): void
    {
        if (is_array($message)) {
            $message = json_encode($message, JSON_UNESCAPED_UNICODE);
        }
        if (Config::$app->getRunMode() !== RunMode::PRODUCTION) {
            error_log(self::getData($message, CliColor::INFO));
        }
        self::$logger->info($message);
    }

    /**
     * @param array|string $message
     */
    public static function sql(array|string $message): void
    {
        if (is_array($message)) {
            $message = json_encode($message, JSON_UNESCAPED_UNICODE);
        }
        if (Config::$app && Config::$app->getRunMode() !== RunMode::PRODUCTION) {
            error_log(self::getData(' SQL => ' . $message, CliColor::WARNING));
            self::$logger->warning(' SQL => ' . $message);
        }
    }


    /**
     * @param array|string $message
     */
    public static function warning(array|string $message): void
    {
        if (is_array($message)) {
            $message = json_encode($message, JSON_UNESCAPED_UNICODE);
        }
        if (Config::$app->getRunMode() !== RunMode::PRODUCTION) {

            error_log(self::getData($message, CliColor::WARNING));
        }
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
        if (Config::$app->getRunMode() !== RunMode::PRODUCTION) {
            error_log(self::getData($message, CliColor::SUCCESS));
        }

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

        if (Config::$app->getRunMode() !== RunMode::PRODUCTION) {
            error_log(self::getData($message, CliColor::ERROR));
        }

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

        if (Config::$app->getRunMode() !== RunMode::PRODUCTION) {

            error_log(self::getData($message, CliColor::ERROR));
        }

        self::$logger->error($message);

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