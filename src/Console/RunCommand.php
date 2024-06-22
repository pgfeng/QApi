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
            $applications = [];
            foreach ($configs as $domain => $app) {
                $port = (string)((parse_url($domain, PHP_URL_PORT)) ?? '80');
                if (!isset($applications[$port])) {
                    $applications[$port] = [$this->color($app->appDir . ' ' . $domain, $app->getRunMode())];
                } else {
                    $applications[$port][] = $this->color($app->appDir . ' ' . $domain, $app->getRunMode());
                }
            }
            foreach ($applications as $port => $apps) {
                $applications[$port] = implode("<fg=gray> | </>", $apps);
            }
            $port = $io->choice('Please select the port on which it is running', $applications, null);
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

    //按照运行模式给文字增加颜色
    public function color(string $string, string $devMode): string
    {
        // 添加Symfony Console颜色
        if ($devMode === 'development') {
            return "<fg=blue>$string</>";
        } elseif ($devMode === 'production') {
            return "$string";
        } else {
            return "<fg=yellow>$string</>";
        }
    }
}