<?php

namespace QApi\Console\make;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ModelCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('make:model')
            ->setAliases(['mm', 'make-model'])
            ->setDescription('Create a new model class')
            ->addOption('config', 'c', InputOption::VALUE_OPTIONAL, 'Database configuration name[default]', 'default')
            ->addOption('table', 't', InputOption::VALUE_OPTIONAL, 'The table to generate the field utility class for.', null)
            ->addOption('namespace', 'ns', InputOption::VALUE_OPTIONAL, 'The namespace to generate the field utility class for.', null)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->success('API document generation completed！');
        return Command::SUCCESS;
    }
}