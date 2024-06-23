<?php

namespace QApi\Console\db;

use QApi\Config;
use QApi\Console\Command;
use QApi\ORM\DB;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DatabaseRestorationCommand extends Command
{
    public function __construct(string $name = null)
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setName('db:restore')
            ->setAliases(['db-restore', 'dbr'])
            ->setDescription('Restore the database.')
            // 数据库配置
            ->addOption('config', 'c', InputOption::VALUE_OPTIONAL, 'Database configuration name that needs to be restored.', null)
            // 保存路径
            ->addOption('path', 'p', InputOption::VALUE_OPTIONAL, 'The path to save the backup file.', './.backup/')
            ->setHelp('This command will restore the database.');
    }

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
        $path = $input->getOption('path');
        $path = rtrim($path, '/') . '/';
        $dirPath = $path . $config . DIRECTORY_SEPARATOR;
        mkPathDir($path);
        $files = scandir($dirPath);
        $files = array_filter($files, function ($file) {
            return $file !== '.' && $file !== '..';
        });
        $files = array_values($files);
        if (empty($files)) {
            $io->error('No backup files found.');
            return Command::FAILURE;
        }
        usort($files, function ($a, $b) use ($dirPath) {
            $aPath = $dirPath . $a;
            $bPath = $dirPath . $b;
            return filemtime($aPath) < filemtime($bPath) ? 1 : -1;
        });
        $file = $io->choice('Please select a backup file', $files);
        if (!$io->confirm('Are you sure you want to restore the database?')) {
            return Command::FAILURE;
        }
        $file = $dirPath . $file;
        $progressBar = new ProgressBar($output, 0);
        $progressBar->setFormat('[%indicator%] %message%');
        $progressBar->setMessage('Loading', 'message');
        $indicators = ['-', '\\', '|', '/'];
        $progressBar->start();
        $file = fopen($file, 'r');
        $sql = '';
        $number = 0;
        $connection = $db->getConnection();
        while (!feof($file)) {
            $sql .= fgets($file);
            // 以分号结尾
            if (str_ends_with(trim($sql), ';')) {
                $number++;
                $connection->executeStatement($sql);
                $sql = '';
                $progressBar->setMessage($indicators[$number % 4], 'indicator');
                $progressBar->setMessage('Executed SQL Number:' . $number, 'message');
                $progressBar->advance();
            }
        }
        fclose($file);
        $progressBar->finish();
        $io->writeln('');
        $io->success('Database restoration completed.');
        return Command::SUCCESS;
    }
}