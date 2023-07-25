<?php

namespace QApi\Template\Rules;

use QApi\Template\Interfaces\EventRuleInterface;
use QApi\Template\Lib\Condition;
use QApi\Template\Lib\Line;
use QApi\Template\Lib\Source;
use QApi\Template\Lib\Statement;
use QApi\Template\Template;

/**
 * The verbatim tag marks sections as being raw text that should not be parsed.
 * <!-- #Verbatim -->
 * <!-- #If $a=1 -->
 *  $a is 1
 * <!-- #EndIf -->
 * <!-- #EndVerbatim -->
 * outputs：
 *      <!-- #If $a=1 -->
 *          $a is 1
 *      <!-- #EndIf -->
 */
class VerbatimRule implements EventRuleInterface
{
    /**
     * @var array
     */
    protected array $codeSections = [];

    public function after(Source $source): Source
    {
        if ($this->codeSections && isset($this->codeSections[$source->path])){
            $source->setLineData(array_key_first($this->codeSections[$source->path])-1,'');
            foreach ($this->codeSections[$source->path] as $line => $code) {
                $source->setLineData($line, $code);
            }
            $source->setLineData(array_key_last($this->codeSections[$source->path])+1,'');
        }
        return $source;
    }

    public function before(Source $source): Source
    {
        Condition::static($source, Statement::CUSTOM_INSTRUCTION, 'verbatim')->rewrite(function (Statement $statement, Line $line, Source $source) {
            $endLine = $line->findEnd($source, $statement);
            for ($i = $line->lineno + 1; $i < $endLine->lineno; $i++) {
                $this->codeSections[$source->path][$i] = $source->getLine($i)->getLineData();
                $source->setLineData($i, '');
            }
            return $line->getLineData();
        }, true);
        $source->fresh();
        return $source;
    }

    public function __construct(Template $context)
    {
    }

    public function parse(Source $source): Source
    {
        return $source;
    }
}