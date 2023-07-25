<?php

namespace QApi\Template\Loader;

use QApi\Template\Error\LoaderError;
use QApi\Template\Lib\Source;

class FileSystemLoader implements LoaderInterface
{

    /**
     * @param array $path
     * @param string $rootPath
     */
    public function __construct(private array $path, private string $rootPath)
    {
        if (substr($this->rootPath,-1)!==DIRECTORY_SEPARATOR){
            $this->rootPath = $this->rootPath. DIRECTORY_SEPARATOR;
        }
    }

    public function getSource(string $name): Source
    {
        $hitFile = $this->exists($name);
        if ($hitFile === false) {
            throw new LoaderError(sprintf('The "%s" directory does not exist ("%s").', $name, json_encode($this->path)));
        }
        return new Source($hitFile, $name, file_get_contents($hitFile));
    }

    public function isFresh(string $name, int $time): bool
    {
        $hitFile = $this->exists($name);
        if ($hitFile === false) {
            throw new LoaderError(sprintf('The "%s" directory does not exist ("%s").', $name, json_encode($this->path)));
        }
        $fileModifyTime = filemtime($hitFile)??filectime($hitFile);
        if ($fileModifyTime) {
            return $fileModifyTime > $time;
        }
        return true;
    }

    public function exists(string $name): string|false
    {
        foreach ($this->path as $path) {
            $filePath = $this->rootPath . trim($path, '/') . DIRECTORY_SEPARATOR . trim($name, '/');
            if (file_exists($filePath)) {
                return $filePath;
            }
        }
        return false;
    }
}