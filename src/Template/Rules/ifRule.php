<?php

namespace QApi\Template\Rules;

use QApi\Template\Interfaces\RuleInterface;
use QApi\Template\Lib\Condition;
use QApi\Template\Lib\Source;
use QApi\Template\Lib\Statement;
use QApi\Template\Template;

/**
 * <!-- #IF $i=0 -->
 * <!-- #ELSE -->
 * <!-- #ELSEIF $i>10 -->
 * <!-- #ENDIF -->
 */
class ifRule implements RuleInterface
{

    public function __construct(Template $context)
    {
    }

    public function parse(Source $source, string $uniqueString = null): Source
    {
        return Condition::static($source, Statement::CUSTOM_INSTRUCTION, ['if', 'endif', 'else', 'elseif'])->rewrite(function (Statement $statement) {
            return match (strtoupper($statement->name)) {
                'IF' => 'if (' . $statement->params->originalParams . ' ) {',
                'ENDIF' => '}',
                'ELSEIF' => '} elseif (' . $statement->params->originalParams . ' ) {',
                'ELSE' => '} ELSE {',
            };
        });
    }
}