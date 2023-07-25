<?php

namespace QApi\Template\Lib;

use JetBrains\PhpStorm\Pure;

class Condition
{
    /**
     * @var Line[]
     */
    private array $lines = [];

    /**
     * @param Source $source
     * @param string $instruction
     * @param string|array|null $name
     * @param string|array $params
     * @param int $offsetLine
     * @param int|null $length
     */
    public function __construct(private Source $source, private string $instruction, private null|string|array $name = null, string|array $params = [], int $offsetLine = 0, ?int $length = null)
    {
        if (is_string($params)) {
            $params = [$params];
        }
        $this->lines = [];
        $tempSource = $this->source->toArray();
        foreach ($tempSource as $line) {
            foreach ($line->statements as $statement) {
                if ($name) {
                    if (is_string($name)) {
                        if ($statement->instruction === $this->instruction && strtoupper($statement->name) === strtoupper($name)) {
                            if ($params) {
                                if (in_array($statement->params->originalParams, $params)) {
                                    $this->lines[] = $line;
                                }
                            } else {
                                $this->lines[] = $line;
                            }
                        }
                    } else {
                        foreach ($name as $n) {
                            if ($statement->instruction === $this->instruction && strtoupper($statement->name) === strtoupper($n)) {
                                if ($params) {
                                    if (in_array($statement->params->originalParams, $params)) {
                                        print_r($statement->params->originalParams);
                                        $this->lines[] = $line;
                                    }
                                } else {
                                    $this->lines[] = $line;
                                }
                            }
                        }
                    }
                } else {
                    $this->lines[] = $line;
                }
            }
        }
    }

    /**
     * @param Source $source
     * @param string $instruction
     * @param string|array|null $name
     * @param array $params
     * @param int $offsetLine
     * @param $length
     * @return static
     */
    public static function static(Source $source, string $instruction, null|string|array $name = null, array $params = [], int $offsetLine = 0, $length = null): self
    {
        return new Condition($source, $instruction, $name, $params, $offsetLine, $length);
    }

    /**
     * @param $statementName
     * @return Statement[]
     */
    public function getStatements($statementName): array
    {
        $statements = [];
        foreach ($this->lines as $line) {
            if ($statement = $line->getStatement($statementName)) {
                $statements[] = $statement;
            }
        }
        return $statements;
    }

    /**
     * @param $statementName
     * @return bool
     */
    public function hasStatement($statementName): bool
    {
        foreach ($this->lines as $line) {
            if ($line->getStatement($statementName)) {
                return true;
            }
        }
        return false;
    }

    public function rewrite($callback, bool $force = false): Source
    {
        foreach ($this->lines as $line) {
            foreach ($line->statements as $statement) {
                if (!$this->name) {
                    if ($statement->instruction === $this->instruction) {
                        $line->rewrite($statement, $callback($statement, $line, $this->source), $this->source, $force);
                    }
                } else {
                    if (is_string($this->name)) {
                        if ($statement->instruction === $this->instruction && strtoupper($statement->name) === strtoupper($this->name)) {
                            $line->rewrite($statement, $callback($statement, $line, $this->source), $this->source, $force);
                        }
                    } else {
                        foreach ($this->name as $n) {
                            if ($statement->instruction === $this->instruction && strtoupper($statement->name) === strtoupper($n)) {
                                $line->rewrite($statement, $callback($statement, $line, $this->source), $this->source, $force);
                            }
                        }
                    }
                }
            }
        }
        return $this->source;
    }
}