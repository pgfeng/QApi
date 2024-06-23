<?php

namespace QApi\Console\db;

use QApi\Config;
use QApi\Console\Command;
use QApi\ORM\DB;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DatabaseBackupCommand extends Command
{
    public function __construct(string $name = null)
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setName('db:backup')
            ->setAliases(['db-backup', 'dbb'])
            ->setDescription('Backup the database.')
            // 数据库配置
            ->addOption('config', 'c', InputOption::VALUE_OPTIONAL, 'Database configuration name that needs to be backed up.', null)
            // 保存路径
            ->addOption('path', 'p', InputOption::VALUE_OPTIONAL, 'The path to save the backup file.', './.backup/')
            // 最多保存多少个备份
            ->addOption('max', '', InputOption::VALUE_OPTIONAL, 'The maximum number of backups to keep.', 5)
            ->setHelp('This command will backup the database.');
    }

    /**
     * @throws \ErrorException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = $input->getOption('config');
        $io = new SymfonyStyle($input, $output);
        if (!$config) {
            $configs = Config::database();
            $configs = array_keys($configs);
            $config = $io->choice('Please select a database configuration', $configs, $config);
        }
        $db = new DB('', $config);
        // 获取数据库表
        $tables = $db->getSchemaManager()->listTableNames();
        $path = $input->getOption('path');
        $path = rtrim($path, '/') . '/';
        $dirPath = $path . $config . DIRECTORY_SEPARATOR;
        $path = $path . $config . DIRECTORY_SEPARATOR . date('YmdHis') . '.sql';
        mkPathDir($path);
//        遍历目录
        $files = scandir($dirPath);
        $files = array_filter($files, function ($file) {
            return $file !== '.' && $file !== '..';
        });
        $max = $input->getOption('max');
//        按照时间排序 大的在前
        rsort($files);
        while (count($files) >= $max) {
            $io->writeln('The number of backups exceeds the maximum limit, deleting the oldest backup.');
            $deleteFile = array_pop($files);
            unlink($dirPath . $deleteFile);
        }
        $io->writeln('Backup path: ' . $path);
        if (file_exists($path)) {
            // 文件已存在 提示是否覆盖
            if (!$io->confirm('The backup file already exists, do you want to overwrite it?')) {
                $io->error('Backup canceled.');
                return Command::FAILURE;
            } else {
                unlink($path);
            }
        }
        $file = fopen($path, 'w');
        fwrite($file, "-- QApi Database backup " . PHP_EOL);
        //备份时间
        fwrite($file, "-- Date: " . date('Y-m-d H:i:s') . PHP_EOL . PHP_EOL);
        foreach ($tables as $table) {
            $io->writeln(PHP_EOL . 'Backup table: ' . $table);
            fwrite($file, "-- ----------------------------" . PHP_EOL);
            fwrite($file, "-- Table `$table` START" . PHP_EOL);
            fwrite($file, "-- ----------------------------" . PHP_EOL);
            $createSql = @$db->getConnection()->executeQuery("SHOW CREATE TABLE `$table`")->fetchAllAssociative();
            // 如果表存在 删除表
            fwrite($file, "DROP TABLE IF EXISTS `$table`;" . PHP_EOL);
            fwrite($file, $createSql[0]['Create Table'].';' . PHP_EOL);
            // 备份表数据
            $total = @$db->getConnection()->executeQuery("SELECT COUNT(*) FROM `$table`")->fetchOne();
            // 打印进度
            $progress = $io->createProgressBar($total);
            if ($total) {
                fwrite($file, PHP_EOL . "-- ----------------------------" . PHP_EOL);
                fwrite($file, "-- Table `$table` Data" . PHP_EOL);
                fwrite($file, "-- ----------------------------" . PHP_EOL);
                // 每次获取200条数据
                $limit = 200;
                $offset = 0;
                while ($offset < $total) {
                    $data = @$db->getConnection()->executeQuery("SELECT * FROM `$table` LIMIT $offset, $limit")->fetchAllAssociative();
                    // 获取总数量
                    foreach ($data as $row) {
                        $keys = array_keys($row);
                        $values = array_map(function ($value) use ($db) {
                            if ($value === null) {
                                return 'NULL';
                            } else {
                                return $db->getConnection()->quote($value);
                            }
                        }, array_values($row));
                        fwrite($file, "INSERT INTO `$table` (`" . implode('`,`', $keys) . "`) VALUES (" . implode(',', $values) . ");" . PHP_EOL);
                    }
                    $offset += $limit;
                    $progress->advance($limit);
                }
                $progress->finish();
                fwrite($file, "-- ----------------------------" . PHP_EOL);
            } else {
                // io 打印黄色警告 使用write
                $io->write(' <comment>Table data is empty.</comment>');
            }
            // 备份表完成
            fwrite($file, "-- Table `$table` END" . PHP_EOL);
            fwrite($file, "-- ----------------------------" . PHP_EOL . PHP_EOL);
        }
        fclose($file);
        $io->success('Backup completed.' . PHP_EOL . 'Backup path: ' . $path);
        return Command::SUCCESS;
    }
}