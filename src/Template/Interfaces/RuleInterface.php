<?php

namespace QApi\Template\Interfaces;


use QApi\Template\Lib\Source;
use QApi\Template\Template;

/**
 * RuleInterface
 */
interface RuleInterface
{
    /**
     * @param Template $context
     */
    public function __construct(Template $context);

    /**
     * @param Source $source
     * @return Source
     */
    public function parse(Source $source): Source;

}