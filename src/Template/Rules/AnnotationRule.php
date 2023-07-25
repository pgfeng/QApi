<?php

namespace QApi\Template\Rules;

use QApi\Template\Interfaces\BeforeEventRuleInterface;
use QApi\Template\Lib\Condition;
use QApi\Template\Lib\Source;
use QApi\Template\Lib\Statement;
use QApi\Template\Template;

class AnnotationRule implements BeforeEventRuleInterface
{

    public function before(Source $source): Source
    {
        return Condition::static($source, Statement::ANNOTATION_INSTRUCTION,'')->rewrite(function () {
            return '';
        }, true);
    }

    public function __construct(Template $context)
    {
    }

    public function parse(Source $source): Source
    {
        return $source;
    }
}