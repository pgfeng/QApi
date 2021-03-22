<?php


namespace QApi\Command;


use ErrorException;
use QApi\Config;
use QApi\Database\DB;

class ColumnCommand extends CommandHandler
{
    /**
     * handler名称
     * @var string
     */
    public string $name = 'column';

    /**
     * 判断表是否存在
     * @param string $table
     * @return bool
     */
    private function tableExists(string $table): bool
    {
        if (!DB::table('')->query('show tables like \'' . $table . '\'')) {
            $this->command->writeln("数据表{$table}不存在!");
            return false;
        }

        return true;
    }

    /**
     * @return mixed
     */
    function help(): mixed
    {
        return null;
    }

    /**
     * @param $argv
     * @return mixed
     * @throws ErrorException
     */
    public function handler($argv): mixed
    {
        $this->argv = $argv;
        if (isset($argv[0]) && $argv[0] === '--all') {
            unset($this->argv[0], $argv[0]);
        } else {
            if (!isset($argv[0]) || (string)$argv[0] === '') {
                $res = $this->command->getStdin("未输入更新的数据库配置是否要更新全部数据库字段[yes or no]:[默认yes]")[0];
                if (false !== stripos($res, "no")) {
                    $this->argv[0] = $config = $this->choseConfig();
                }
            } else if (!Config::database($argv[0])) {
                $this->argv[0] = $config = $this->choseConfig();
            } else if ((!isset($argv[1]) || (int)$argv[1] === 1) && (isset($this->argv[0]) && (string)$this->argv[0] !== '')) {
                $res = $this->command->getStdin("未输入表名,是否{$this->argv[0]}全部数据表[yes or no]:[默认yes]")[0];
                if (false !== stripos($res, "no")) {
                    $this->argv[1] = $this->choseTable($this->argv[0]);
                }
            }
            if (isset($argv[1]) && !$this->tableExists(Config::database($this->argv[0])->tablePrefix . $this->argv[1])) {
                $this->argv[1] = $this->choseTable($this->argv[0]);
            }
        }
        $this->buildColumn();
        return null;
    }

    /**
     * 选择表
     * @param $config
     * @return string
     * @throws ErrorException
     */
    public function choseTable($config): string
    {
        $table = $this->command->getStdin("请输入表名:")[0];
        if (!$table || !$this->tableExists(Config::database($config)->tablePrefix . $table)) {
            return $this->choseTable($config);
        }

        return $table;
    }

    /**
     * @param $config
     * @return string
     */
    public function getNameSpace($config): string
    {
        return Config::command('BaseColumnNameSpace') . '_' . $config;
    }

    /**
     * @param $config
     * @param $table
     * @return string
     */
    public function getColumnPath($config, $table): string
    {
        return PROJECT_PATH . Config::command('ColumnDir') . '_' . $config . DIRECTORY_SEPARATOR . $table . '.php';
    }

    /**
     * 生成字段
     * @throws ErrorException
     */
    private function buildColumn(): void
    {
        if (!$this->argv) {
            $database_configs = array_keys(Config::database());
            foreach ($database_configs as $config) {
                $this->buildDatabaseColumn($config, $this->getNameSpace($config));
            }
        } elseif (isset($this->argv[0]) && !isset($this->argv[1])) {
            $this->buildDatabaseColumn($this->argv[0], $this->getNameSpace($this->argv[0]));
        } elseif (isset($this->argv[1], $this->argv[0])) {
            $this->buildTableColumn($this->argv[1], $this->argv[0], $this->getNameSpace($this->argv[0]),
                $this->getColumnPath($this->argv[0], $this->argv[1]));
        }
    }

    /**
     * @param $config
     * @param $nameSpace
     * @throws ErrorException
     */
    private function buildDatabaseColumn($config, $nameSpace): void
    {
        $tables = DB::table('', $config)->query("show tables");
        foreach ($tables as $table) {
            $table = $table[array_keys($table->toArray())[0]];
            if (str_starts_with($table, Config::database($config)->tablePrefix)) {
                $table = substr($table, strlen(Config::database($config)->tablePrefix));
                $this->buildTableColumn($table, $config, $this->getNameSpace($config), $this->getColumnPath($config, $table));
            }
        }
        $this->command->success("数据库[" . $config . "]表字段更新完成！");
    }

    /**
     * @param $table
     * @param $config
     * @param $nameSpace
     * @param $columnPath
     * @return void
     * @throws ErrorException
     */
    private function buildTableColumn($table, $config, $nameSpace, $columnPath): void
    {
        $columns = DB::table('', $config)->query('desc ' . Config::database($config)->tablePrefix . $table);
        $columns_comment = DB::table('', $config)->query('select column_name,column_comment from information_schema.columns where table_schema =\'' . Config::database($config)->dbName . '\'  and table_name = \'' . Config::database($config)->tablePrefix . $table . '\';');
        $columns_comment = $columns_comment->transPrimaryIndex('column_name');
        $const = '';
        foreach ($columns as $column) {
            $const .= '
    /**
    * @var string ' . $columns_comment[$column['Field']]['column_comment'] . '
    * ';
            $const .= $column;
            $const .= '
    */
    public const ' . strtoupper($column['Field']) . ' = \'' . $column['Field'] . '\';' . "\r\n";
        }
        $date = date('Y-m-d H:i:s');
        $ColumnContent = <<<Column
<?php
/**
 * Created by QApi-builder.
 * Time: $date
 */
 
namespace $nameSpace;

/**
 * Class {$table}
 * @package Model
 */
class $table{

    /* table name */
    public const table_name='$table';
$const
}
Column;
        mkPathDir($columnPath);
        file_put_contents($columnPath, $ColumnContent);
        $this->command->info($nameSpace . '\\' . $table . 'Column生成成功!');

    }

    /**
     * @param string $msg
     * @return string
     * @throws ErrorException
     */
    protected function choseConfig($msg = '请输入配置名称[默认default]:'): string
    {
        $config = $this->command->getStdin($msg)[0];
        if (!$config) {
            $config = 'default';
        }
        if (!Config::database($config)) {
            return $this->choseConfig('请输入正确的配置名称:');
        }

        return $config;
    }
}