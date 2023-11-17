<?php

namespace QApi\Console;

use QApi\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RunCommand extends Command
{
    protected function configure()
    {
        $this->setName('run')
            ->setDescription('Run project with PHP Web-Server.')
            ->setHelp('This command allows you to run project with PHP Web-Server.')
            ->addOption('host', 'H', InputOption::VALUE_OPTIONAL, 'The host address to serve the application on. Default:0.0.0.0','0.0.0.0')
            ->addOption('port', 'p', InputOption::VALUE_OPTIONAL, 'The port to serve the application on. Default:8889','8080');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $host = $input->getOption('host') ?: '0.0.0.0';
        $port = $input->getOption('port') ?: '8080';
        $command = sprintf(
            'php -S %s:%d -t %s %s -i',
            $host,
            $port,
            escapeshellarg(Config::command('ServerRunDir')),
            escapeshellarg(__DIR__ . DIRECTORY_SEPARATOR . '../Route/router.php'),
        );
        passthru($command);
        return Command::SUCCESS;
    }
}