<?php

namespace QApi\Template\Lib;


class Tag
{
    /**
     * @var string[]
     */
    private array $start;
    /**
     * @var string[]
     */
    private array $end;

    /**
     * @param string|array $start
     * @param string|array $end
     */
    public function __construct(string|array $start = ['<![\-]{2,}[\s]*', '{[\s]*?'], string|array $end = ['[\s]*[\-]{2,}>', '[\s]*}'])
    {
        if (is_string($start)) {
            $start = [$start];
        }
        $this->start = $start;
        if (is_string($end)) {
            $end = [$end];
        }
        $this->end = $end;
    }

    public function getStatements(Line $line): array
    {
        $all = [];
        foreach ($this->start as $key => $value) {
            if (preg_match_all('/' . $this->start[$key] . '(' . preg_quote(Statement::CUSTOM_INSTRUCTION,'/') . '|' . preg_quote(Statement::RENDER_INSTRUCTION,'/'). '|' . preg_quote(Statement::ANNOTATION_INSTRUCTION,'/'). '|' . preg_quote(Statement::FUNCTION_INSTRUCTION,'/') . ')([a-zA-Z0-9_\x80-\xff]+?)[\s]*(.*)?' . $this->end[$key] . '/iU', $line->getLineData(), $matches)) {
                $statements = [];
                foreach ($matches[0] as $key => $value) {
                    $statements[] = new Statement($line->getLineno(), $value, $matches[1][$key], $matches[2][$key], $matches[3][$key]);
                }
                $all = array_merge($all, $statements);
            }
        }
        return $all;
    }

    /**
     * @return string[]
     */
    public function getStart(): array
    {
        return $this->start;
    }

    /**
     * @return string[]
     */
    public function getEnd(): array
    {
        return $this->end;
    }
}