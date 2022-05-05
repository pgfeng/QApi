<?php
/**
 * Created by PhpStorm.
 * User: PGF
 * Date: 2017/7/0008
 * Time: 上午 11:32
 */

namespace QApi\Command;


use JetBrains\PhpStorm\Pure;
use QApi\Command;
use QApi\Config;
use QApi\Database\DBase;
use QApi\Logger;
use QApi\ORM\DB;
use QApi\ORM\Model;

/**
 * @package QApi\Command
 */
abstract class CommandHandler
{
    protected Command $command;
    protected array $argv = [];
    protected int $pid;

    /**
     * Handler constructor.
     * @param Command $command
     * @param array $argv
     */
    public function __construct(Command $command, array $argv = [])
    {
        $this->pid = posix_getpid();
        $this->command = $command;
    }

    public function hideLogger():void
    {
        Command::$showLogger = false;
    }

    public function showLogger():void
    {
        Command::$showLogger = true;
    }

    /**
     * @return bool
     */
    #[Pure] public function isWin(): bool
    {
        $index = stripos(PHP_OS, 'WIN');
        if ($index === false) {
            return false;
        }
        return $index === 0;
    }

    /**
     * Handler名称
     * @var string
     */
    public string $name = '';

    /**
     * @param $argv
     * @return mixed
     */
    abstract public function handler(array $argv): mixed;
    

    /**
     * select a database configuration
     */
    public function choseDatabase(): string
    {
        $configs = Config::database();
        $choseData = [];
        foreach ($configs as $configName => $config) {
            $choseData[$configName] = '[' . $configName . ']' . $config->name . '://' . $config->user . ':'
                . '***@' .
                $config->host
                . ':' .
                $config->port
                . '/' .
                $config->dbName;
        }
        $input = $this->command->cli->blue()->radio('Please select a database configuration item:',
            $choseData);
        return $input->prompt();
    }

    /**
     * input table name
     */
    public function getTable($databaseConfigName)
    {
        $config = Config::database($databaseConfigName);
        $input = $this->command->cli->blue()->input('Please enter the table name：');
        $table = $input->prompt();
        $model = new Model('', $databaseConfigName);
        $manager = $model->getSchemaManager();
        if (!$manager->tablesExist($config->tablePrefix . $table)) {
            $this->command->cli->red('[' . $config->tablePrefix . $table . '] not exist!');
            return $this->getTable($databaseConfigName);
        }
        $column = new ColumnCommand($this->command, [
            $databaseConfigName, $table,
        ]);
        $column->handler([
            $databaseConfigName, $table,
        ]);
        return $table;
    }

    /**
     * @param string $name
     * @param int $type
     * @param bool $uc_first
     * @return string
     */
    public function parseName(string $name, int $type = 0, bool $uc_first = true): string
    {
        if ($type) {
            $name = preg_replace_callback('/_([a-zA-Z])/', function ($match) {
                return strtoupper($match[1]);
            }, $name);
            return $uc_first ? ucfirst($name) : lcfirst($name);
        } else {
            return strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
        }
    }
}