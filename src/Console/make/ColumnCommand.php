<?php

namespace QApi\Console\make;

use Doctrine\DBAL\Exception;
use ErrorException;
use QApi\Config;
use QApi\Console\Command;
use QApi\Logger;
use QApi\ORM\DB;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ColumnCommand extends Command
{
    private string $tabCharacter = '    ';
    public function __construct(string $name = null)
    {
        parent::__construct($name);
        Logger::$disabledType = ['SQL'];
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('make:column')
            ->setAliases(['make-column', 'mc'])
            ->setDescription('Generate database table field tool class.')
            ->setHelp('This command will generate the database table field utility class')
            ->addOption('config', 'c', InputOption::VALUE_OPTIONAL, 'The configuration file to generate the field utility class for.', null)
            ->addOption('table', 't', InputOption::VALUE_OPTIONAL, 'The table to generate the field utility class for.', null);
    }

    /**
     * @throws Exception
     * @throws ErrorException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $config = $input->getOption('config');
        $table = $input->getOption('table');
        $output->writeln('Generation Parameters:'."\n$this->tabCharacter".'Config:'.($config?:'-')."\t".'Table:'.($table?:'-'));
        $io = new SymfonyStyle($input, $output);
        if (!$config) {
            $systemDatabaseConfig = Config::database();
            foreach ($systemDatabaseConfig as $config => $value) {
                $this->buildDatabaseColumn($config, $input, $output);
            }
            return Command::SUCCESS;
        }
        if (!$table) {
            $this->buildDatabaseColumn($config, $input, $output);
            return Command::SUCCESS;
        }
        $this->buildTableColumn($table, $config, $this->getNameSpace($config), $this->getColumnPath($config, $table), $input, $output);
        return Command::SUCCESS;
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
     * @param $path
     */
    public function clearDir($path): void
    {
        if (is_dir($path)) {
            $p = scandir($path);
            foreach ($p as $val) {
                if ($val !== "." && $val !== "..") {
                    if (is_dir($path . $val)) {
                        $this->clearDir($path . $val . '/');
                        @rmdir($path . $val . '/');
                    } else {
                        unlink($path . $val);
                    }
                }
            }
        }
    }

    /**
     * @param $config
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws Exception
     * @throws \ErrorException
     */
    private function buildDatabaseColumn($config, InputInterface $input, OutputInterface $output): void
    {
        $output->writeln("[$config]:");
        $io = new SymfonyStyle($input, $output);
        $bar = $io->createProgressBar();
        $bar->setFormat("$this->tabCharacter<info>%current%/%max% [%bar%] %percent%% %notice%</info>");
        $bar->setMessage('Database Start generating!', 'notice');
        $tables = (new DB('', $config))->getSchemaManager()->listTables();
        $bar->setMaxSteps(count($tables));
        $this->clearDir(PROJECT_PATH . Config::command('ColumnDir') . '_' . $config . DIRECTORY_SEPARATOR);
        $bar->setMessage('Clean up complete!', 'notice');
        $bar->setMessage('--', 'table');
        foreach ($tables as $index => $table) {
            $table = $table->getName();
            $bar->setProgress($index);
            $bar->setMessage($table, 'notice');
            if (str_starts_with($table, Config::database($config)->tablePrefix)) {
                $table = substr($table, strlen(Config::database($config)->tablePrefix));
                $this->buildTableColumn($table, $config, $this->getNameSpace($config), $this->getColumnPath($config, $table), $input, $output, true);
            }
        }
        $bar->setMessage('Ok!', 'notice');
        $bar->finish();
        $output->writeln("");
    }

    /**
     * @param $table
     * @param $config
     * @param $nameSpace
     * @param $columnPath
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws ErrorException|Exception
     */
    private function buildTableColumn($table, $config, $nameSpace, $columnPath, InputInterface $input, OutputInterface $output, $hideOutput = false): void
    {
        $columns = (new DB($table, $config))->query('desc ' . Config::database($config)->tablePrefix . $table);
        $columns_comment = (new DB($table, $config))->query('select column_name as column_name,column_comment as column_comment from information_schema.columns where table_schema =\'' . Config::database($config)->dbName . '\'  and table_name = \'' . Config::database($config)->tablePrefix . $table . '\';');
        $columns_comment = $columns_comment->transPrimaryIndex('column_name');
        $const = '';
        foreach ($columns as $column) {
            $const .= '
            
    /**
     * @var string ' . addslashes($columns_comment[$column['Field']]['column_comment'] ?? '') . '
     * ';
            $const .= $column;
            $const .= '
     */
    #[Field(name: \'' . $column['Field'] . '\', comment: \'' . addslashes($columns_comment[$column['Field']]['column_comment'] ?? '') . '\', type: \'' . $column['Type'] . '\', allowNull: \'' . ($column['Null'] === 'NO' ? 'true' : 'false') . '\', default: \'' . $column['Default'] . '\', key: \'' . $column['Key'] . '\', extra: \'' . $column['Extra'] . '\')]
    public const ' . strtoupper($column['Field']) . ' = \'' . $column['Field'] . '\';' . "\r\n";
        }
        $date = date('Y-m-d H:i:s');
        $tableComment = (new DB($table, $config))->query("SELECT TABLE_COMMENT FROM INFORMATION_SCHEMA.TABLES  WHERE TABLE_NAME = '" . Config::database($config)->tablePrefix . $table . "' AND TABLE_SCHEMA = '" . Config::database($config)->dbName . "'");
        if ($tableComment) {
            $tableComment = addslashes($tableComment[0]['TABLE_COMMENT'] ?? '');
        } else {
            $tableComment = '';
        }
        $ColumnContent = <<<Column
<?php
/**
 * Created by QApi-builder.
 * Time: $date
 */
 
namespace $nameSpace;

use QApi\Attribute\Column\Table;
use QApi\Attribute\Column\Field;

#[
    Table(name: '{$table}', comment: '{$tableComment}'),
]
class $table
{

    /* table name */
    public const table_name = '$table';
$const
}
Column;
        mkPathDir($columnPath);
        file_put_contents($columnPath, $ColumnContent);
        if (!$hideOutput) {
            $output->writeln('<info>[' . $nameSpace . '\\' . $table . '] Generation complete!</info>');
        }
    }
}