<?php
/**
 * Created by PhpStorm.
 * User: PGF
 * Date: 2017/7/0007
 * Time: 下午 10:09
 */

namespace QApi;


use League\CLImate\CLImate;
use QApi\Command\ClearCacheCommand;
use QApi\Command\ColumnCommand;
use QApi\Command\CommandHandler;
use QApi\Command\DocBuildCommand;
use QApi\Command\RouteCacheClearCommand;
use QApi\Command\RouteCommand;
use QApi\Command\RunCommand;

/**
 * Class Console
 * @package QApi
 */
class Command
{
    protected string $name = " = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =
 .----------------.   .----------------.   .----------------.   .----------------. 
| .--------------. | | .--------------. | | .--------------. | | .--------------. |
| |    ___       | | | |      __      | | | |   ______     | | | |     _____    | |
| |  .'   '.     | | | |     /  \     | | | |  |_   __ \   | | | |    |_   _|   | |
| | /  .-.  \    | | | |    / /\ \    | | | |    | |__) |  | | | |      | |     | |
| | | |   | |    | | | |   / ____ \   | | | |    |  ___/   | | | |      | |     | |
| | \  `-'  \_   | | | | _/ /    \ \_ | | | |   _| |_      | | | |     _| |_    | |
| |  `.___.\__|  | | | ||____|  |____|| | | |  |_____|     | | | |    |_____|   | |
| |              | | | |              | | | |              | | | |              | |
| '--------------' | | '--------------' | | '--------------' | | '--------------' |
 '----------------'   '----------------'   '----------------'   '----------------' 
 = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =
 ";
    protected $stdout;
    protected $stdin;
    protected $stderr;
    protected $argv;
    /**
     * @var CLImate
     */
    public CLImate $cli;
    public array $Handler = [];

    /**
     * Command constructor.
     * @throws \ErrorException
     */
    public function __construct()
    {
        error_reporting(E_ALL ^ E_NOTICE);
        date_default_timezone_set("PRC");
        $this->stdout = fopen('php://stdout', 'wb');
        $this->stdin = fopen('php://stdin', 'rb');
        $this->stderr = fopen('php://stderr', 'wb');
        $this->cli = new CLImate();
        array_shift($_SERVER['argv']);
        $this->argv = $_SERVER['argv'];
        $this->addHandler(new RunCommand($this));
        $this->addHandler(new ColumnCommand($this));
        $this->addHandler(new DocBuildCommand($this));
        $this->addHandler(new ClearCacheCommand($this));
        $this->addHandler(new RouteCommand($this));
        $this->addHandler(new RouteCacheClearCommand($this));
        $Handlers = Config::command('CommandHandlers');

        /**
         * 将配置中的handle导入
         */
        foreach ($Handlers as $handle) {
            $this->addHandler(new $handle($this));
        }
    }

    /**
     * @param CommandHandler $handler
     */
    public function addHandler(CommandHandler $handler): void
    {
        $this->Handler[$handler->name] = $handler;
    }

    /**
     * 执行
     */
    public function execute()
    {
        if (!$this->argv) {
            $this->help();
        } else if ($this->argv[0] === '') {
            $this->argv[0] = $this->cli->cyan()->radio('Please select a command：', array_keys($this->Handler))->prompt();
            $this->execute();
        } else if (isset($this->Handler[$this->argv[0]])) {
            $argv = $this->argv;
            $handle_name = array_shift($argv);
            $this->Handler[$handle_name]->handler($argv);
        } else {
            $this->argv[0] = $this->cli->cyan()->radio('Please select a command：', array_keys($this->Handler))->prompt();
            $this->execute();
        }
    }


    /**
     * 打印基础的使用方法
     */
    public function help()
    {
        $this->success($this->name);
        $this->argv[0] = $this->cli->cyan()->radio('Please select a command：', array_keys($this->Handler))->prompt();
        $this->execute();
    }

    /**
     * 输出一行
     * @param string $message 输出的消息
     */
    public function writeln($message)
    {
        $this->write($message . "\r\n");
    }


    /**
     * @param $message
     */
    public function info($message): void
    {
        $this->cli->info($message);
    }

    public function error($message): void
    {
        $this->cli->error($message);
    }

    /**
     * @param $message
     */
    public function success($message): void
    {
        $this->cli->red($message);
    }

    /**
     * 输出内容
     * @param string $message 输出的消息
     */
    public function write(string $message): void
    {
        if (!is_string($message)) {
            $message = var_export($message, true);
        }
        //        if (strtoupper(substr(PHP_OS,0,3))==='WIN') {
        //            if (mb_detect_encoding($message, 'UTF-8', true))
        //                $message = mb_convert_encoding($message, "GBK", "UTF-8");
        //        }
        fwrite($this->stdout, $message);
    }

    /**
     * 读取一行内容
     * @param string $notice
     * @return array
     */
    public function getStdin($notice = ''): array
    {
        $this->write($notice);
        return explode(' ', str_replace(["\r\n", "\n"], '', fgets($this->stdin)));
    }

}