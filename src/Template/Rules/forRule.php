<?php

namespace QApi\Template\Rules;

use QApi\Template\Interfaces\RuleInterface;
use QApi\Template\Lib\Condition;
use QApi\Template\Lib\Source;
use QApi\Template\Lib\Statement;
use QApi\Template\Template;

/**
 * <!-- #FOR $i=0;$i<10;$i++; -->
 * <!-- #ENDFOR -->
 */
class forRule implements RuleInterface
{

    public function __construct(Template $context)
    {
    }

    public function parse(Source $source, string $uniqueString = null): Source
    {
        return Condition::static($source, Statement::CUSTOM_INSTRUCTION, ['FOR', 'ENDFOR'])->rewrite(function (Statement $statement) {
            return match (strtoupper($statement->name)) {
                'FOR' => 'for(' . $statement->params->originalParams . ') {',
                'ENDFOR' => ' } ',
            };
        });
    }
}