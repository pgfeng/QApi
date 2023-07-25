<?php

namespace QApi\Template\Rules;

use QApi\Template\Error\ParseError;
use QApi\Template\Interfaces\AfterEventRuleInterface;
use QApi\Template\Interfaces\RuleInterface;
use QApi\Template\Lib\Condition;
use QApi\Template\Lib\Line;
use QApi\Template\Lib\Parameter;
use QApi\Template\Lib\Source;
use QApi\Template\Lib\Statement;
use QApi\Template\Template;

/**
 * Include Template file
 * <!-- #Template "child" -->
 * <!-- #Template 'child' -->
 * <!-- #Template 'child'.$name -->
 */
class TemplateRule implements RuleInterface
{
    public function __construct(private Template $context)
    {
    }

    public function parse(Source $source, string $uniqueString = null): Source
    {
        return Condition::static($source, Statement::CUSTOM_INSTRUCTION, ['Template','import'])->rewrite(function (Statement $statement, Line $line, Source $source) {
            $parameter = $statement->params->getParam(0);
            if ($parameter && $template = $statement->params->getParam(0)->getNoMarkValue()) {
                return 'self::renderOutput(\'' . $template . '\');';
            } else {
                throw new ParseError('Too few arguments, must be passed in ' . $statement->name . ' on line ' . $statement->lineno, $source, $source->getLine($statement->lineno));
            }
        });
    }
}