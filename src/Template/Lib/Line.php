<?php

namespace QApi\Template\Lib;

use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;
use QApi\Template\Error\ParseError;
use Stringable;


/**
 * Line
 */
class Line implements Stringable
{
    /**
     * @var Statement[]
     */
    public array $statements = [];

    public function __construct(public int $lineno, private string $data)
    {
        $this->init();
    }


    public function replaceVars($string): string
    {
        return preg_replace_callback('/\$([a-zA-Z_][a-zA-Z0-9_.]*)/', function ($matches) {
            $vars = explode('.', $matches[1]);
            $name = '';
            foreach ($vars as $var) {
                $name .= '[\'' . $var . '\']';
            }
            return '$this->data' . $name;
        }, $string);
    }

    /**
     * @param Statement $originalStatement
     * @param string $newStatement
     * @return Line
     */
    public function rewrite(Statement $originalStatement, string $newStatement, Source $source, bool $force): Line
    {
        $this->data = str_replace($originalStatement->originalStatement, !$force ? '<?php /* @Template(' . $source->path . '#' . $source->name . ':' . $this->lineno . ')  */ ' . $this->replaceVars($newStatement) . ' ?>' : $newStatement, $this->data);
        return $this;
    }

    public function markLine(Source $source): static
    {
        $this->data = preg_replace('/^' . preg_quote('<?php /* @Template(', '/') . '(.*)' . preg_quote(')  */ ?>', '/') . '/iUs', '', $this->data);
        $this->setLineData(('<?php /* @Template(' . $source->path . '#' . $source->name . ':' . $this->lineno . ')  */ ?>') . $this->data);
        return $this;
    }

    public function throwUnclosedError(Source $source, Statement $statement): void
    {
        throw new ParseError('Unclosed ' . $statement->name . ' on line ' . $statement->lineno, $source, $source->getLine($statement->lineno));
    }

    /**
     * @param Source $source
     * @param Statement $statement
     * @param string $endPrefix
     * @return Line|null
     */
    public function findEnd(Source $source, Statement $statement, string $endPrefix = 'end'): ?Line
    {
        if ($this->getStatement(strtoupper($endPrefix . $statement->name))) {
            return $source->getLine($statement->lineno);
        }
        $relative = 0;
        $lines = $source->getLines($statement->lineno, null);
        foreach ($lines as $line) {
            foreach ($line->statements as $statementItem) {
                if (strtoupper($statement->name) === strtoupper($statementItem->name)) {
                    $relative += 1;
                }
                if (strtoupper($endPrefix . $statement->name) === strtoupper($statementItem->name)) {
                    if ($relative === 0) {
                        return $line;
                    } else {
                        $relative -= 1;
                    }
                }
            }
        }
        return null;
    }

    public function init()
    {

        $this->statements = (new Tag())->getStatements($this);
    }

    /**
     * @return int
     */
    public function getLineno(): int
    {
        return $this->lineno;
    }

    public function getLineData(): string
    {
        return $this->data;
    }


    /**
     * @param $statementName
     * @return Statement|null
     */
    public function getStatement($statementName): ?Statement
    {
        foreach ($this->statements as $statement) {
            if (preg_match('/' . preg_quote(trim($statementName)) . '/i', trim($statement->name))) {
                return $statement;
            }
        }
        return null;
    }

    /**
     * @param Source $source
     * @param Statement $startStatement
     * @param Statement $endStatement
     * @return array|null
     */
    #[ArrayShape(['original' => "mixed", 'match' => "mixed", "status" => "bool"])] public function getBlockData(Source $source, Statement $startStatement, Statement $endStatement): ?array
    {
        $lines = $source->getLines($startStatement->lineno - 1, $endStatement->lineno - $startStatement->lineno + 1);
        if (preg_match('/' . preg_quote($startStatement->originalStatement, '/') . '(.*)' . preg_quote($endStatement->originalStatement, '/') . '/is', implode("\n", $lines), $matches)) {
            return [
                'original' => $matches[0],
                'match' => $matches[1],
                'status' => true,
            ];
        } else {
            return [
                'original' => null,
                'match' => null,
                'status' => false,
            ];
        }
    }

    public function replaceBlockData(Source $source, Statement $statement, Statement $endStatement, $replaceData): Source
    {
        $matches = $this->getBlockData($source, $statement, $endStatement);
        if ($matches['status']) {
            for ($i = $statement->lineno; $i <= $endStatement->lineno; $i++) {
                if ($i === $statement->lineno) {
                    $line = $source->getLine($statement->lineno);
                    $source->setLineData($statement->lineno, str_replace($matches['match'], $replaceData, $line->getLineData()));
                } elseif ($i !== $endStatement->lineno) {
                    $source->setLineData($i, '');
                }
            }
            $source->rewriteLine($statement->lineno, $statement, '');
            $source->rewriteLine($endStatement->lineno, $endStatement, '');
            $source->getLine($endStatement->lineno)->init();
        }
        return $source;
    }

    public function setLineData(string $data): Line
    {
        $this->data = $data;
        $this->init();
        return $this;
    }

    /**
     * @return string
     */
    #[Pure] public function __toString(): string
    {
        return $this->getLineData();
    }
}