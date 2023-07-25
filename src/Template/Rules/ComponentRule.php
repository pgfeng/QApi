<?php

namespace QApi\Template\Rules;

use QApi\Template\Interfaces\EventRuleInterface;
use QApi\Template\Interfaces\RuleInterface;
use QApi\Template\Lib\Condition;
use QApi\Template\Lib\Line;
use QApi\Template\Lib\Source;
use QApi\Template\Lib\Statement;
use QApi\Template\Template;

/**
 * <!-- #Component 'test'   -->
 *  <!-- #Slot --><!-- #EndSlot -->
 *  <!-- #Slot title -->
 *      <!-- Component 'slotTitle' -->
 *  Slot Content
 *  <!-- #EndSlot -->
 * <!-- #EndComponent -->
 */
class ComponentRule implements EventRuleInterface
{

    /**
     * @var Source[]
     */
    private array $layoutSources = [];
    /**
     * @var array
     */
    private array $slots = [];

    /**
     * @param $name
     * @return Source
     */
    public function getLayoutSource($name): Source
    {
        return $this->context->compile($this->context->config->loader->getSource($name . $this->context->config->templateSuffix));
    }

    public function after(Source $source): Source
    {
        return $source;
    }


    public function before(Source $source): Source
    {
        return $source;
    }

    public function __construct(private Template $context)
    {
    }

    public function parse(Source $source): Source
    {
//        foreach ($source as $line){
//            $line->markLine($source);
//        }
        Condition::static($source, Statement::CUSTOM_INSTRUCTION, ['Component'])->rewrite(function (Statement $statement, Line $line, Source $TemplateSource) {
            $endLine = $line->findEnd($TemplateSource, $statement);
            if (!$endLine) {
                return '';
            }
            $endStatement = $endLine->getStatement('EndComponent');
            if (!$endStatement) {
                return '';
            }
            $endLine->rewrite($endLine->getStatement('EndComponent'), '', $TemplateSource, true);
            $childSource = $TemplateSource->copySource($line->getLineno(), $endLine->getLineno() - $line->getLineno() - 1);
            $childCondition = Condition::static($childSource, Statement::CUSTOM_INSTRUCTION, ['Component']);
            if ($childCondition->hasStatement('Component')) {
                $statements = $childCondition->getStatements('Component');
                foreach ($statements as $childStatement) {
                    $startChildStatement = $childStatement;
                    $startChildLine = $childSource->getLine($startChildStatement->lineno);
                    $endChildStatementLine = $startChildLine->findEnd($childSource, $childStatement);
                    if (!$endChildStatementLine) {
                        $startChildLine->throwUnclosedError($TemplateSource, $childStatement);
                    }

                    $endChildStatement = $endChildStatementLine->getStatement('EndComponent');
                    if ($endChildStatement) {
                        $childComponentSource = $childSource->copySource($startChildLine->getLineno() - 1, $endChildStatement->lineno - $startChildLine->getLineno() + 1);
                        $childComponentSource = $this->parse($childComponentSource);
                        $line->replaceBlockData($TemplateSource, $startChildStatement, $endChildStatement, $childComponentSource);
                    } else {
                        $startChildLine->throwUnclosedError($TemplateSource, $childStatement);
                    }

                }
            }
            $ComponentSource = $this->getLayoutSource($statement->params->getParam(0)->getNoMarkValue());
            // Search Layout Slot
            Condition::static($ComponentSource, Statement::CUSTOM_INSTRUCTION, 'Slot')->rewrite(
                function (Statement $ComponentStatement, Line $ComponentLine, Source $ComponentSource) use ($TemplateSource, $childSource) {
                    $ComponentEndLine = $ComponentLine->findEnd($ComponentSource, $ComponentStatement);
                    if (!$ComponentEndLine) {
                        return $ComponentStatement->originalStatement;
                    }
                    $ComponentEndSlotStatement = $ComponentEndLine->getStatement('EndSlot');
                    $params = trim($ComponentStatement->params->originalParams);
                    if (!$params) {
                        $params = ['default', ''];
                    } else {
                        $params = [$params];
                    }
                    // Search Template Slot
                    Condition::static($TemplateSource, Statement::CUSTOM_INSTRUCTION, 'Slot', $params)->rewrite(function (Statement $templateSlotStatement, Line $templateSlotLine, Source $TemplateSource) use ($ComponentStatement, $ComponentEndSlotStatement, $ComponentLine, $ComponentSource) {
                        $TemplateSlotEndLine = $templateSlotLine->findEnd($TemplateSource, $templateSlotStatement);
                        if (!$TemplateSlotEndLine) {
                            $templateSlotLine->throwUnclosedError($TemplateSource, $templateSlotStatement);
                        }
                        $templateEndSlotStatement = $TemplateSlotEndLine->getStatement('EndSlot');
                        $block = $TemplateSlotEndLine->getBlockData($TemplateSource, $templateSlotStatement, $templateEndSlotStatement);
                        if ($block['status']) {
                            $ComponentLine->replaceBlockData($ComponentSource, $ComponentStatement, $ComponentEndSlotStatement, $block['match']);
                            $templateSlotLine->replaceBlockData($TemplateSource, $templateSlotStatement, $templateEndSlotStatement, '');
                        } else {
                            $ComponentLine->replaceBlockData($ComponentSource, $ComponentStatement, $ComponentEndSlotStatement,
                                $ComponentLine->getBlockData($ComponentSource, $ComponentStatement, $ComponentEndSlotStatement)['match'] ?? ''
                            );
                        }
                        $TemplateSource->rewriteLine($templateSlotLine->getLineno(), $templateSlotStatement, '');
                        $TemplateSource->rewriteLine($TemplateSlotEndLine->getLineno(), $templateEndSlotStatement, '');
                        return $templateSlotLine->getLineData();
                    }, true);
                    $ComponentLine->rewrite($ComponentStatement, '', $ComponentSource, true);
                    $ComponentEndLine->rewrite($ComponentEndSlotStatement, '', $ComponentSource, true);
                    return $ComponentLine->markLine($ComponentSource);
                }, true);
            $line->replaceBlockData($TemplateSource, $statement, $endStatement, $ComponentSource);
            return $ComponentSource;
        }, true);
        return $source;
    }
}