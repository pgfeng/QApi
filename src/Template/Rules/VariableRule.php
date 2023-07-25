<?php

namespace QApi\Template\Rules;

use QApi\Template\Interfaces\RuleInterface;
use QApi\Template\Lib\Condition;
use QApi\Template\Lib\Source;
use QApi\Template\Template;
use QApi\Template\Lib\Statement;

/**
 * <!-- $i -->
 * <!-- $i.'asdsad' -->
 * <!-- $i+$ii -->
 * { $i }
 * { $i . 'asdsasd'}
 * { $i+$ii }
 * { $i+556 }
 */
class VariableRule implements RuleInterface
{
    public function parse(Source $source, string $uniqueString = null): Source
    {
        return Condition::static($source,Statement::RENDER_INSTRUCTION)->rewrite(function (Statement $statement){
            return 'echo ' . Statement::RENDER_INSTRUCTION . $statement->name . $statement->params->originalParams . ';';
        });
    }

    public function __construct(protected Template $context)
    {
    }
}