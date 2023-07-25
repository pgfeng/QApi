<?php

namespace QApi\Template\Interfaces;

use QApi\Template\Lib\Source;

interface AfterEventRuleInterface extends RuleInterface
{
    /**
     * @param Source $source
     * @return string
     */
    public function after(Source $source): Source;
}