<?php


namespace QApi\Cache;


use DateInterval;
use DateTime;
use FilesystemIterator;
use Iterator;
use JetBrains\PhpStorm\Pure;
use QApi\Config\Cache\FileSystem;
use QApi\Exception\CacheErrorException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class FileSystemAdapter implements CacheInterface
{
    /**
     * @var string
     */
    public string $directory;

    /**
     * @var string
     */
    public string $extension;

    /**
     * @var int
     */
    public int $umask;


    /** @var int */
    private int $directoryStringLength;

    /** @var int */
    private int $extensionStringLength;

    /**
     * FileSystemCache constructor.
     * @param FileSystem $config
     * @throws CacheErrorException
     */
    public function __construct(protected FileSystem $config)
    {
        $directory = $config->directory;
        $this->directory = $directory;
        $this->extension = $config->extension;
        $this->umask = $config->umask;
        if (!$this->createPathIfNeeded($directory)) {
            throw new CacheErrorException(sprintf(
                'The directory "%s" does not exist and could not be created.',
                $directory
            ));
        }
        if (!is_writable($directory)) {
            throw new CacheErrorException(sprintf(
                'The directory "%s" is not writable.',
                $directory
            ));
        }
        $this->directoryStringLength = strlen($this->directory);
        $this->extensionStringLength = strlen($this->extension);
    }

    /**
     * @param string $id
     *
     * @return string
     */
    #[Pure] protected function getFilename(string $id): string
    {
        $hash = hash('sha256', $id);

        if (
            $id === ''
            || ((strlen($id) * 2 + $this->extensionStringLength) > 255)
            || (($this->directoryStringLength + 4 + strlen($id) * 2 + $this->extensionStringLength) > 258)
        ) {
            $filename = '_' . $hash;
        } else {
            $filename = bin2hex($id);
        }
        return $this->directory
            . DIRECTORY_SEPARATOR
            . substr($hash, 0, 2)
            . DIRECTORY_SEPARATOR
            . ($this->config->hashFileName ? $filename : $id)
            . $this->extension;
    }

    /**
     * Gets the cache directory.
     *
     * @return string
     */
    public function getDirectory(): string
    {
        return $this->directory;
    }

    /**
     * Gets the cache file extension.
     *
     * @return string
     */
    public function getExtension(): string
    {
        return $this->extension;
    }

    /**
     * Create path if needed.
     *
     * @param string $path
     * @return bool TRUE on success or if path already exists, FALSE if path cannot be created.
     */
    private function createPathIfNeeded(string $path): bool
    {
        return !(!is_dir($path) && @mkdir($path, 0777 & (~$this->umask), true) === false && !is_dir($path));
    }

    /**
     * @param string $filename
     * @param string $content
     * @return bool
     */
    protected function writeFile(string $filename, string $content): bool
    {
        $filepath = pathinfo($filename, PATHINFO_DIRNAME);

        if (!$this->createPathIfNeeded($filepath)) {
            return false;
        }

        if (!is_writable($filepath)) {
            return false;
        }

        $tmpFile = tempnam($filepath, 'swap');
        @chmod($tmpFile, 0666 & (~$this->umask));

        if (file_put_contents($tmpFile, $content) !== false) {
            @chmod($tmpFile, 0666 & (~$this->umask));
            if (@rename($tmpFile, $filename)) {
                return true;
            }

            @unlink($tmpFile);
        }

        return false;
    }

    /**
     * @param string $name
     * @return bool
     */
    #[Pure] private function isFilenameEndingWithExtension(string $name): bool
    {
        return $this->extension === ''
            || strrpos($name, $this->extension) === strlen($name) - $this->extensionStringLength;
    }


    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $data = '';
        $lifetime = -1;
        $filename = $this->getFilename($key);

        if (!is_file($filename)) {
            return $default;
        }

        $resource = fopen($filename, 'rb');
        $line = fgets($resource);

        if ($line !== false) {
            $lifetime = (int)$line;
        }

        if ($lifetime !== 0 && $lifetime < time()) {
            fclose($resource);
            $this->delete($key);
            return $default;
        }

        while (($line = fgets($resource)) !== false) {
            $data .= $line;
        }

        fclose($resource);

        return unserialize($data, [
            'allowed_classes' => true,
        ]);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param DateInterval|int|null $ttl
     * @return bool
     */
    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $lifeTime = 0;
        if (is_int($ttl)) {
            $lifeTime = time() + $ttl;
        } else if ($ttl instanceof \DateInterval) {
            $lifeTime = (new DateTime())->add($ttl)->getTimestamp();
        }
        $data = serialize($value);
        return $this->writeFile($this->getFilename($key), $lifeTime . PHP_EOL . $data);
    }

    /**
     *
     * @return Iterator
     */
    private function getIterator(): Iterator
    {
        return new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
    }

    /**
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool
    {
        $filename = $this->getFilename($key);
        return !file_exists($filename) || @unlink($filename);
    }

    /**
     * @return bool
     */
    public function clear(): bool
    {
        foreach ($this->getIterator() as $name => $file) {
            if ($file->isDir()) {
                @rmdir($name);
            } elseif ($this->isFilenameEndingWithExtension($name)) {
                @unlink($name);
            }
        }

        return true;
    }

    /**
     * @param iterable $keys
     * @param mixed|null $default
     * @return iterable
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $data = [];
        foreach ($keys as $key) {
            $data[$key] = $this->get($key, $default);
        }
        return $data;
    }

    /**
     * @param iterable $values
     * @param DateInterval|int|null $ttl
     * @return bool
     */
    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            if (!$this->set($key, $value, $ttl)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param iterable $keys
     * @return bool
     */
    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            if (!$this->delete($key)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param $key
     * @return bool
     */
    public function has($key): bool
    {
        return $this->get($key, null) !== null;
    }
}