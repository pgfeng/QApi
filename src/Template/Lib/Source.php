<?php

namespace QApi\Template\Lib;

use Stringable;

class Source implements \Iterator, Stringable
{
    private int $position = 0;
    /**
     * @var Line[]
     */
    private array $lines = [];

    public function __construct(public string $path, public ?string $name = null, string $content = '')
    {
        if ($content === null) {
            $content = file_get_contents($this->path);
        }
        $line_temps = explode("\n", $content);
        $this->lines = [];
        foreach ($line_temps as $line => $data) {
            $this->lines[] = new Line($line + 1, $data);
        }
    }

    public function rewriteLine($lineno, Statement $statement, $data)
    {
        $this->lines[$lineno - 1]->rewrite($statement, $data, $this, true);
    }

    /**
     * @return Line[]
     */
    public function toArray(): array
    {
        return $this->lines;
    }

    public function setLine(Line $line)
    {
        $this->lines[$line->getLineno() - 1] = $line;
    }

    public function setLineData($lineno, $data): void
    {
        $this->lines[$lineno - 1]->setLineData($data);
    }

    public function &getLine($offset): Line
    {
        return $this->lines[$offset - 1];
    }

    /**
     * @param $offset
     * @param $length
     * @return Line[]
     */
    public function getLines($offset, $length = null): array
    {
        if (!$length) {
            $length = end($this->lines)->lineno - $offset;
        }
        $lines = [];
        for ($i = $offset;$i<($offset+$length);$i++){
            $lines[] = $this->lines[$i];
        }
        return $lines;
    }

    /**
     * @param $offset
     * @param $length
     * @return Source
     */
    public function copySource($offset, $length = null): Source
    {
        $source = clone $this;
        $source->lines = $this->getLines($offset, $length);
        $source->syncLineNo();
        return $source;
    }

    public function syncLineNo()
    {
        $lines = [];
        foreach ($this->lines as $line) {
            $line = clone $line;
            $line->init();
            $lines[$line->getLineno() - 1] = $line;
        }
        $this->lines = $lines;
    }

    public function fresh(): void
    {
        $line_temps = explode("\n", implode("\n", $this->lines));
        $this->lines = [];
        foreach ($line_temps as $line => $data) {
            $this->lines[] = new Line($line + 1, $data);
        }
    }

    public function __toString(): string
    {
        return implode("\n", $this->lines);
    }

    public function current(): mixed
    {
        return $this->lines[$this->position];
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function key(): mixed
    {
        return $this->position;
    }

    public function valid(): bool
    {
        return isset($this->lines[$this->position]);
    }

    public function rewind(): void
    {
        $this->position = 0;
    }
}