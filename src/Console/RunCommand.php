<?php

namespace QApi\Console;

use QApi\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RunCommand extends Command
{
    protected function configure()
    {
        $this->setName('run')
            ->setDescription('Run project with PHP Web-Server.')
            ->setHelp('This command allows you to run project with PHP Web-Server.')
            ->addOption('host', 'H', InputOption::VALUE_OPTIONAL, 'The host address to serve the application on. Default:0.0.0.0', '0.0.0.0')
            ->addOption('port', 'p', InputOption::VALUE_OPTIONAL, 'The port to serve the application on.', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $host = $input->getOption('host') ?: '0.0.0.0';
        $port = $input->getOption('port');
        $io = new SymfonyStyle($input, $output);
        if (!$port) {
            $configs = Config::apps();
            // 端口号为键 下面为应用的数组
            $applications = [];
            foreach ($configs as $domain => $app) {
                $port = (string)((parse_url($domain, PHP_URL_PORT)) ?? '80');
                if (isset($applications[$port])) {
                    $applications[$port] = [ $app->appDir . ':'.$app->getRunMode().'-' . $domain];
                } else {
                    $applications[$port][] = $app->appDir . ':'.$app->getRunMode().'-' . $domain;
                }
            }
            foreach ($applications as $port => $apps) {
                $applications[$port] = implode(",", $apps);
            }
            $port = $io->choice('Please select the port on which it is running', $applications, $port);
            $port = array_search($port, $applications);
        }
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