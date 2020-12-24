<?php


namespace QApi\Command;


use QApi\Router;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RouteCommand extends Command
{
    protected static $defaultName = 'route';

    protected function configure()
    {
        $this->setDescription('路由查询')->addOption('list')->addOption('find', null,
            InputOption::VALUE_REQUIRED,)->setHelp('路由操作');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('list')){
            print_r(Router::$routeLists);
        }
        return 1;
    }
}