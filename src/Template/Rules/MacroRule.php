<?php

namespace QApi\Template\Rules;

use QApi\Template\Error\ParseError;
use QApi\Template\Interfaces\RuleInterface;
use QApi\Template\Lib\Condition;
use QApi\Template\Lib\Line;
use QApi\Template\Lib\Source;
use QApi\Template\Lib\Statement;
use QApi\Template\Template;

class MacroRule implements RuleInterface
{

    public function __construct(Template $context)
    {
    }

    public function parse(Source $source): Source
    {
        $source = Condition::static($source, Statement::CUSTOM_INSTRUCTION, 'macro')->rewrite(function (Statement $statement, Line $line, Source $source) {
            $endLine = $line->findEnd($source, $statement);
            if (!$endLine) {
                $this->throwUnclosedError($source, $statement);
            } else {
                $endStatement = $endLine->getStatement('endmacro');
                $data = $line->getBlockData($source, $statement, $endStatement);
                for ($i = $line->getLineno() + 1; $i <= $endStatement->lineno; $i++) {
                    $source->setLineData($i, '');
                }
                $data = preg_replace_callback('/\{[\s*]?(.*)[\s*]?\}/U', function ($matches) {
                    return '$' . trim($matches[1]);
                }, $data['match']);
                if (preg_match('/(.*)[\s*]?\((.*)\)/i', $statement->params->originalParams, $matches)) {
                    $data = '<?php /* @Template(' . $source->path . '#' . $source->name . ':' . $line->lineno . ')  */ $this->macro[\'' . trim($matches[1]) . '\']=function(' . trim($matches[2]) . '){ echo "' . str_replace('"', '\"', trim($data)) . '"; } ?>';
                    return $data;
                } else {
                    return '';
                }
            }
        }, true);
        $source = Condition::static($source, Statement::FUNCTION_INSTRUCTION)->rewrite(function (Statement $statement, Line $line, Source $source) {
            return '<?php /* @Template(' . $source->path . '#' . $source->name . ':' . $line->lineno . ')  */ $this->macro[\''.$statement->name.'\']'.$line->replaceVars($statement->params->originalParams).'; ?>';
        },true);
        return $source;
    }


    public function throwUnclosedError(Source $source, Statement $statement): void
    {
        throw new ParseError('Unclosed ' . $statement->name . ' on line ' . $statement->lineno, $source, $source->getLine($statement->lineno));
    }
}