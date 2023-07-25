<?php

namespace QApi\Template\Rules;

use QApi\Template\Error\ParseError;
use QApi\Template\Interfaces\RuleInterface;
use QApi\Template\Lib\Condition;
use QApi\Template\Lib\Line;
use QApi\Template\Lib\Source;
use QApi\Template\Lib\Statement;
use QApi\Template\Template;

class DoRules implements RuleInterface
{

    public function __construct(Template $context)
    {
    }

    public function parse(Source $source): Source
    {
        return Condition::static($source, Statement::CUSTOM_INSTRUCTION, 'do')->rewrite(function (Statement $statement, Line $line, Source $source) {
            if (!trim($statement->params->originalParams)) {
                $endLine = $line->findEnd($source, $statement);
                if ($endLine && $endStatement = $endLine->getStatement('endDo')) {
                    $data = $line->getBlockData($source, $statement, $endStatement);
                    for ($i=$line->getLineno()+1;$i<=$endLine->getLineno();$i++){
                        $source->setLineData($i,'');
                    }
                    return $data['match'];
                }else{
                    throw new ParseError('Unclosed ' . $statement->name . ' on line ' . $statement->lineno, $source, $source->getLine($statement->lineno));
                }
            } else {
                return $statement->params->originalParams;
            }
        }, false);
    }
}