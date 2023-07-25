<?php

namespace QApi\Template\Interfaces;

use QApi\Template\Lib\Source;

interface BeforeEventRuleInterface extends RuleInterface
{
    /**
     * @param Source $source
     * @return Source
     */
    public function before(Source $source): Source;

}