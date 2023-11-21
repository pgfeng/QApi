<?php

namespace QApi\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class DocumentSystemUpdateCommand
 * @package QApi\Console
 */
class DocumentSystemUpdateCommand extends Command
{

    private string $zipSavePath = 'DocSystemTemp.zip';

    private string $downloadUrl = 'https://github.com/pgfeng/QApiDocument/archive/refs/heads/main.zip';

    protected function configure(): void
    {
        $this
            ->setName('install:documentSystem')
            ->setAliases(['install-documentSystem', 'update-documentSystem'])
            ->addOption('dir', 'd', InputOption::VALUE_OPTIONAL, 'The directory to install the document system. Default:./public/ApiDoc')
            ->setDescription('Update/Install the document system');
    }

    protected function execute($input, $output)
    {
        $ss = new SymfonyStyle($input, $output);
        $dir = $input->getOption('dir') ?: './public/ApiDoc';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        if (is_dir($dir)) {
            $this->deleteDir($dir);
        }
        if ($this->downloadFileWithProgress($input, $output)) {
            $zipArchive = new \ZipArchive();
            if ($zipArchive->open($this->zipSavePath) === true) {
                $totalEntries = $zipArchive->numFiles;
                $progressBar = new ProgressBar($output, $totalEntries);
                $progressBar->setFormat('Extracting... %current%/%max% [%bar%] %percent%%');
                $progressBar->start();
                for ($i = 0; $i < $totalEntries; $i++) {
                    $entryName = $zipArchive->getNameIndex($i);
                    $zipArchive->extractTo($dir, [$entryName]);
                    $progressBar->advance();
                }
                $progressBar->finish();
                $output->writeln("\n<info>Extraction complete.</info>");
                $zipArchive->close();
                unlink($this->zipSavePath);
                $extractDir = $dir . DIRECTORY_SEPARATOR . 'QApiDocument-main';
                $files = scandir($extractDir);
                $progressBar = new ProgressBar($output, $totalEntries);
                $progressBar->setFormat('Moving... %current%/%max% [%bar%] %percent%%');
                $progressBar->setMessage(0, 'current');
                $progressBar->setMessage(0, 'percent');
                $progressBar->setMessage(count($files), 'max');
                $progressBar->start();
                foreach ($files as $i => $file) {
                    // 设置进度条
                    $progressBar->setProgress($i);
                    $progressBar->setMessage($i, 'current');
                    usleep(100000);
                    if ($file === '.' || $file === '..') {
                        continue;
                    }
                    $filePath = $extractDir . DIRECTORY_SEPARATOR . $file;
                    if (is_dir($filePath)) {
                        rename($filePath, $dir . DIRECTORY_SEPARATOR . $file);
                    } else {
                        rename($filePath, $dir . DIRECTORY_SEPARATOR . $file);
                    }
                }
                $this->deleteDir($extractDir);
                $progressBar->finish();
                $ss->newLine();
                $ss->success("Document System Update complete!");
            } else {
                $ss->error("Failed to open the ZIP file.");
            }
        }

        return Command::SUCCESS;
    }

    private function downloadFileWithProgress(InputInterface $input, OutputInterface $output): bool
    {
        $fp = fopen($this->zipSavePath, 'w+');
        $ch = curl_init($this->downloadUrl);
        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_NOPROGRESS, false);
//        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $progressBar = new ProgressBar($output);
        $progressBar->setFormat('<info>Downloading... %percent%% [%bar%] %downloaded% of %total%<info>');
        $progressBar->setRedrawFrequency(1); // Redraw every 1%
        $progressBar->setMessage('---', 'downloaded');
        $progressBar->setMessage('---', 'total');
        $progressBar->start();
        curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function ($resource, $download_size, $downloaded_size, $upload_size, $uploaded_size) use ($progressBar) {
            if ($download_size > 0) {
                $progressBar->setMaxSteps($download_size);
                $progressBar->setProgress($downloaded_size);
                $progressBar->setMessage(number_format($downloaded_size / 1024 / 1024, 2) . ' MB', 'downloaded');
                $progressBar->setMessage(number_format($download_size / 1024 / 1024, 2) . ' MB', 'total');
            }
        });

        $progressBar->finish();
        curl_exec($ch);
        $error = curl_error($ch);
        if ($error) {
            $output->writeln("\n<error>$error\nYou can try downloading and extracting directly from GitHub to the corresponding directory.\ndownload url: <href=$this->downloadUrl>$this->downloadUrl</></error>");
            return false;
        }
        curl_close($ch);
        fclose($fp);
        curl_error($ch);
        $output->writeln("\n<info>Download complete.</info>");
        return true;
    }

    private function deleteDir(mixed $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $filePath = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($filePath)) {
                $this->deleteDir($filePath);
            } else {
                unlink($filePath);
            }
        }
        rmdir($dir);
    }
}