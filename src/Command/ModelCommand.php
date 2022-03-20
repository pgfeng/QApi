<?php

namespace QApi\Command;

use Composer\Autoload\ClassLoader;
use Composer\InstalledVersions;
use JetBrains\PhpStorm\Pure;
use QApi\Command;
use QApi\Config;
use QApi\Logger;
use QApi\Model\Traits\Auxiliary;
use QApi\Model\Traits\SoftDelete;
use QApi\Model\Traits\UUID;
use QApi\Model\Traits\Validate;
use QApi\ORM\Model;
use Test\Model\_Column_default\article;

class ModelCommand extends CommandHandler
{

    public string $name = 'build:model';

    #[Pure] public function __construct(Command $command, $argv = [])
    {
        parent::__construct($command, $argv);
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

    public function handler(array $argv): mixed
    {
        if (!isset($argv[0])) {
            return $this->handler([$this->choseDatabase()]);
        }
        if (!isset($argv[1])) {
            return $this->handler([$argv[0], $this->getTable($argv[0])]);
        }
        if (!isset($argv[2])) {
            return $this->handler([$argv[0], $argv[1], $this->getUseTrait()]);
        }
        if (!isset($argv[3])) {
            return $this->handler([$argv[0], $argv[1], $argv[2], $this->getModelDir()]);
        }
        if (!isset($argv[4])) {
            return $this->handler([$argv[0], $argv[1], $argv[2], $argv[3], $this->choseNameSpace()]);
        }
        if (!isset($argv[5])) {
            return $this->handler([$argv[0], $argv[1], $argv[2], $argv[3], $argv[4], $this->choseExtendModel()]);
        }
        $this->saveModelFile($argv[0], $argv[1], $argv[2], $argv[3], $argv[4], $argv[5]);
        return null;
    }

    /**
     * @return string
     */
    public function choseExtendModel(): string
    {
        $Models = [
            Model::class,
            \QApi\Model::class
        ];
        $input = $this->command->cli->blue()->radio('Please select the base model object to inherit:', $Models);
        return $input->prompt();
    }

    /**
     *
     */
    public function getModelDir(): string
    {
        $input = $this->command->cli->blue()->input('Please enter the directory of the model file:');
        return $input->prompt();
    }

    public function getComposerNameSpaceDir($nameSpace): string
    {
        $load = ClassLoader::getRegisteredLoaders();
        $vendorDir = PROJECT_PATH . 'vendor';
        foreach ($load as $item) {
            preg_match('/vendorDir".*"(.*)"/iUs', serialize($item), $matches);
            if (count($matches) == 2) {
                $vendorDir = $matches[1];
                break;
            }
        }
        $psr4 = include $vendorDir . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . 'autoload_psr4.php';
        $psr4Dir = [];
        foreach ($psr4 as $space => $dirArray) {
            $in = false;
            foreach ($dirArray as $dir) {
                if (str_starts_with($dir, $vendorDir)) {
                    $in = true;
                    break;
                }
            }
            if (!$in) {
                $psr4Dir[$space] = end($dirArray);
            }
        }
        return $psr4Dir[$nameSpace];
    }

    public function choseNameSpace(): string
    {
        $load = ClassLoader::getRegisteredLoaders();
        $vendorDir = PROJECT_PATH . 'vendor';
        foreach ($load as $item) {
            preg_match('/vendorDir".*"(.*)"/iUs', serialize($item), $matches);
            if (count($matches) == 2) {
                $vendorDir = $matches[1];
                break;
            }
        }
        $psr4 = include $vendorDir . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . 'autoload_psr4.php';
        $psr4Dir = [];
        foreach ($psr4 as $nameSpace => $dirArray) {
            $in = false;
            foreach ($dirArray as $dir) {
                if (str_starts_with($dir, $vendorDir)) {
                    $in = true;
                    break;
                }
            }
            if (!$in) {
                $psr4Dir[$nameSpace] = '[' . $nameSpace . ']' . end($dirArray);
            }
        }
        $psr4Dir = $this->sortModelNameSpaceDir($psr4Dir);
        $input = $this->command->cli->blue()->radio('Please select the root namespace model:', $psr4Dir);
        return $input->prompt();

    }

    function list_sort_by(array $list, string $field, string $sort = 'asc'): array
    {
        $refer = $resultSet = array();
        foreach ($list as $i => $data)
            $refer[$i] = $data[$field];
        switch ($sort) {
            case 'asc': // 正向排序
                asort($refer);
                break;
            case 'desc': // 逆向排序
                arsort($refer);
                break;
            case 'nat': // 自然排序
                natcasesort($refer);
                break;
        }
        foreach ($refer as $key => $val)
            $resultSet[] = &$list[$key];
        return $resultSet;
    }

    /**
     * @param array $psr4Dir
     */
    public function sortModelNameSpaceDir(array $psr4Dir): array
    {
        $tempDir = [];
        foreach ($psr4Dir as $key => $dir) {
            $tempDir[] = [
                'nameSpace' => $key,
                'dir' => $dir,
                'percent' => similar_text($key, 'M m o d e l O r'),
            ];
        }

        $data = $this->list_sort_by($tempDir, 'percent', 'desc');
        $psr4Dir = [];
        foreach ($data as $dir) {
            $psr4Dir[$dir['nameSpace']] = $dir['dir'];
        }
        return $psr4Dir;
    }

    public function saveModelFile($configName, $table, $traits, $dirPath, $rootNameSpace, $extendModelClass): void
    {
        $namespaceDir = $this->getComposerNameSpaceDir($rootNameSpace);
        $savePath = $namespaceDir . DIRECTORY_SEPARATOR . parseDir($dirPath) . $this->parseName($table,
                2)
            . 'Model.php';

        if (file_exists($savePath)) {
            $this->command->cli->yellow($savePath . ' exists!');
            $input = $this->command->cli
                ->red()
                ->radio('Model file already exists, do you want to regenerate it:',
                    [
                        'YES', 'NO'
                    ]);
            if ($input->prompt() === 'NO') {
                return;
            }
        }
        $manager = (new Model($table, $configName))->getSchemaManager();
        $config = Config::database($configName);
        if (!$manager->tablesExist($config->tablePrefix . $table)) {
            $this->command->cli->red('[' . $config->tablePrefix . $table . '] not exist!');
            return;
        }
        $columns = $manager->listTableIndexes($config->tablePrefix . $table);
        $primary_key = null;
        foreach ($columns as $column) {
            if ($column->isPrimary()) {
                $primary_key = strtoupper($column->getColumns()[0]);
            }
        }
        if ($traits) {

            $traits = explode(',', $traits);
            $traits = array_filter($traits);
            $modelName = $this->parseName($table,
                    2)
                . 'Model';
            $traitsOutClass = [];
            foreach ($traits as $trait) {
                $traitsOutClass[] = ' QApi\\Model\\Traits\\' . $trait;
            }
            $traitsOutClass = "\nuse" . implode(',', $traitsOutClass) . ';';
            $traitsInClass = "\n    use " . implode(', ', $traits) . ';';

        } else {
            $traitsOutClass = '';
            $traitsInClass = '';
        }
        $date = date('Y-m-d H:i:s', time());
        $columnClass = Config::command('BaseColumnNameSpace') . '_' . $configName . '\\' . $table;
        if ($dirPath) {
            $modelNameSpace = $rootNameSpace . str_replace('/', '\\', $dirPath);
        } else {
            $modelNameSpace = trim($rootNameSpace, '\\');
        }
        $extendClass = $extendModelClass;
        $extendModelClass = explode('\\', $extendModelClass);
        $extendModelName = end($extendModelClass);
        $extendModelNameOutClass = $extendClass;
        $modelContent = <<<ModelFileContent
<?php
/**
 * Created by QApi-CLI.
 * Time: $date
 */

namespace {$modelNameSpace};

use {$extendModelNameOutClass};{$traitsOutClass}
use {$columnClass};

class {$modelName} extends {$extendModelName}
{
    public string \$primary_key = {$table}::{$primary_key};{$traitsInClass}

    public function __construct(bool|string|null \$table = null, string \$configName = '{$configName}')
    {
        parent::__construct(\$table, \$configName);
    }
}
ModelFileContent;

        mkPathDir($savePath);
        file_put_contents($savePath, $modelContent);
        $this->command->info('Generated successfully ' . $savePath);
    }

    /**
     * @return string
     */
    public function getUseTrait(): string
    {

        $choseData = str_replace('QApi\\Model\\Traits\\', '', [
            Auxiliary::class,
            SoftDelete::class,
            UUID::class,
            Validate::class,
        ]);
        $input = $this->command->cli->checkboxes('Please select the trait you want to use:', $choseData);
        $traits = $input->prompt();
        return implode(',', $traits);
    }

    public function help(): mixed
    {
        return null;
    }
}