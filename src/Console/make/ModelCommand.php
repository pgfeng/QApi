<?php

namespace QApi\Console\make;

use Composer\Autoload\ClassLoader;
use Doctrine\DBAL\Exception;
use ErrorException;
use QApi\Cache\Cache;
use QApi\Cache\CacheInterface;
use QApi\Config;
use QApi\Console\Command;
use QApi\Model\Traits\Authorize;
use QApi\Model\Traits\Auxiliary;
use QApi\Model\Traits\Partition;
use QApi\Model\Traits\SoftDelete;
use QApi\Model\Traits\UUID;
use QApi\Model\Traits\Validate;
use QApi\ORM\DB;
use QApi\ORM\Model;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class ModelCommand extends Command
{
    public CacheInterface $cache;

    protected function configure(): void
    {
        $this->cache = Cache::initialization('__cli');
        $this->setName('make:model')
            ->setAliases(['mm', 'make-model'])
            ->setDescription('Create a new model class')
            ->addOption('config', 'c', InputOption::VALUE_OPTIONAL, 'Database configuration name[default]', null)
            ->addOption('table', 't', InputOption::VALUE_OPTIONAL, 'The table to generate the field utility class for.', null)
            ->addOption('traits', 'tr', InputOption::VALUE_OPTIONAL, 'The traits to generate the field utility class for.', null)
            ->addOption('namespace', 'ns', InputOption::VALUE_OPTIONAL, 'The namespace to generate the field utility class for.', null);
    }


    /**
     * @throws ErrorException
     * @throws Exception|ExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $options = $input->getOptions();
        $config = $options['config'];
        if (empty($config)) {
            $nameSpaceCacheKey = $this->getName() . '::config';
            $config = $this->cache->get($nameSpaceCacheKey);
            $configs = Config::database();
            $configs = array_keys($configs);
            $config = $io->choice('Please select a database configuration', $configs, $config);
            $this->cache->set($nameSpaceCacheKey, $config);
        }
        $table = $options['table'];
        if (empty($table)) {
            $db = new DB('', $config);
            $tables = $db->getSchemaManager()->listTableNames();
            $prefix = Config::database($config)->tablePrefix;
            $tables = array_map(function ($table) use ($prefix) {
                return preg_replace('/^'.$prefix.'/', '', $table);
            }, $tables);
            $table = $io->choice('Please select a table', $tables);
            if (empty($table)) {
                $io->error('Table cannot be empty');
                return Command::FAILURE;
            }
        }else{
            $db = new DB('', $config);
            $tables = $db->getSchemaManager()->listTableNames();
            $prefix = Config::database($config)->tablePrefix;
            $tables = array_map(function ($table) use ($prefix) {
                return preg_replace('/^'.$prefix.'/', '', $table);
            }, $tables);
            if (!in_array($table, $tables)) {
                $io->error('Table does not exist');
                return Command::FAILURE;
            }
        }
        $mc = $this->getApplication()->find('make:column');
        $input = new ArrayInput([
            'command' => 'make:column',
            '--config' => $config,
            '--table' => $table,
        ]);
        $mc->run($input, $output);
        $traits = $options['traits'];
        if (empty($traits)) {
            $traits = $this->getUseTrait($input, $output);
        }
        if (empty($namespace)) {
            $nameSpaceCacheKey = $this->getName() . '::namespace';
            $namespace = $this->cache->get($nameSpaceCacheKey);
            $namespace = $io->ask('Please enter the namespace',$namespace);
            if (empty($namespace)) {
                $io->error('Namespace cannot be empty');
                return Command::FAILURE;
            } else {
                $this->cache->set($nameSpaceCacheKey, $namespace);
            }
        }
        $rootNameSpace = $this->choseNameSpace($io);
        $result = $this->saveModelFile($config, $table, $traits, $namespace, $rootNameSpace, Model::class,$input, $output);
        if ($result){
            $io->success("Model file generation completed！\nFile path:" . $result);
        }else{
            $io->error('Model file generation failed！');
        }
        return Command::SUCCESS;
    }

    public function choseNameSpace(SymfonyStyle $io): string
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
        $cacheKey = $this->getName() . '::root_namespace';
        $rootNameSpace = $this->cache->get($cacheKey);
        $rootNameSpace = $io->choice('Please select a model root namespace', $psr4Dir,$rootNameSpace);
        $this->cache->set($cacheKey, $rootNameSpace);
        return $rootNameSpace;
    }

    /**
     * @param array $psr4Dir
     * @return array
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

        $data = listSortBy($tempDir, 'percent', 'desc');
        $psr4Dir = [];
        foreach ($data as $dir) {
            $psr4Dir[] = $dir['nameSpace'];
        }
        return $psr4Dir;
    }
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return array
     */
    public function getUseTrait(InputInterface $input, OutputInterface $output): array
    {
        $choices = str_replace('QApi\\Model\\Traits\\', '', [
            Auxiliary::class,
            SoftDelete::class,
            Authorize::class,
            UUID::class,
            Validate::class,
            Partition::class,
        ]);
        $choiceQuestion = new ChoiceQuestion('Please select your choices', $choices);
        $choiceQuestion->setMultiselect(true);
        $choiceQuestion->setValidator(function ($answer) {
            return $answer;
        });
        $traits = $this->getHelper('question')->ask($input, $output, $choiceQuestion);
        return explode(',',$traits)??[];
    }

    /**
     * @throws Exception
     * @throws ErrorException
     */
    public function saveModelFile($configName, $table, $traits, $dirPath, $rootNameSpace, $extendModelClass, InputInterface $input, OutputInterface $output): string
    {
        $namespaceDir = getComposerNameSpaceDir($rootNameSpace);
        $savePath = $namespaceDir . DIRECTORY_SEPARATOR . parseDir($dirPath) . convertToCamelCase($table)
            . 'Model.php';

        if (file_exists($savePath)) {
            $io = new SymfonyStyle($input, $output);
            $input = $io->confirm('Do you want to regenerate it?', false);
            if (!$input) {
                return '';
            }
        }
        $manager = (new Model($table, $configName))->getSchemaManager();
        $config = Config::database($configName);
        $columns = $manager->listTableIndexes($config->tablePrefix . $table);
        $primary_key = null;
        foreach ($columns as $column) {
            if ($column->isPrimary()) {
                $primary_key = strtoupper($column->getColumns()[0]);
            }
        }
        $modelName = convertToCamelCase($table).'Model';
        if ($traits) {
            $traits = array_filter($traits);
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
        return $savePath;
    }
}