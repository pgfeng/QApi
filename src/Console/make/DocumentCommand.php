<?php

namespace QApi\Console\make;

use QApi\Attribute\Utils;
use QApi\Exception\CacheErrorException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DocumentCommand extends Command
{
    protected function configure()
    {
        $this->setName('make:document')
            ->setAliases(['md', 'make-document'])
            ->setDescription('Generate API documentation based on annotations');;
    }

    /**
     * @throws CacheErrorException
     * @throws \ErrorException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        Utils::rebuild(null, '1.0.0', $input, $output);
        $io->success('API document generation completed！');
        return Command::SUCCESS;
    }
}