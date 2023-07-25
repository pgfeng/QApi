<?php

namespace QApi\Template\Rules;

use QApi\Template\Interfaces\RuleInterface;
use QApi\Template\Lib\Condition;
use QApi\Template\Lib\Parameter;
use QApi\Template\Lib\Params;
use QApi\Template\Lib\Source;
use QApi\Template\Lib\Statement;
use QApi\Template\Template;

/**
 * <!-- #Each data=data index=index item=item -->
 * <!-- #Each $data as $key=>$value -->
 * <!-- #Each $data as $value -->
 * <!-- #EndEach -->
 * <!-- #Loop data=data index=index item=item -->
 * <!-- #Loop $data as $key=>$value -->
 * <!-- #Loop $data as $value -->
 * <!-- #EndLoop -->
 */
class EachRule implements RuleInterface
{
    public function __construct(protected Template $context)
    {
    }

    public function parse(Source $source): Source
    {
        return Condition::static($source, Statement::CUSTOM_INSTRUCTION, ['EACH', 'ENDEACH', 'LOOP', 'ENDLOOP'])->rewrite(function (Statement $statement) {
            if (in_array(strtoupper($statement->name), ['EACH', 'LOOP'])) {
                if ($statement->params->type === Params::NAMED) {
                    $data = preg_replace('/[\'"]/', '', $statement->params->getParam('data'));
                    $index = preg_replace('/[\'"]/', '', $statement->params->getParam('index'));
                    $item = preg_replace('/[\'"]/', '', $statement->params->getParam('item'));
                    if ($index) {
                        return 'if(isset( $' . $data . ') && $' . $data . ') foreach ( $' . $data . ' as $' . $index . ' => $' . $item . ' ) {';
                    } else {
                        return 'if(isset( $' . $data . ') && $' . $data . ') foreach ( $' . $data . ' as $' . preg_replace('/[\'"]/', '', $item) . ' ) {';
                    }
                } else {
                    $data = preg_replace('/[\'"]/', '', $statement->params->getParam(0) ?? '');
                    $index = preg_replace('/[\'"]/', '', $statement->params->getParam(1) ?? '');
                    $item = preg_replace('/[\'"]/', '', $statement->params->getParam(2) ?? '');
                    if ($item) {
                        return 'if(isset( $' . $data . ') && $' . $data . ') foreach ( $' . $data . ' as $' . $index . ' => $' . $item . ' ) {';
                    } else {
                        return 'if(isset( $' . $data . ') && $' . $data . ') foreach ( $' . $data . ' as $' . $index . ') {';
                    }
                }
            } else {
                return ' } ';
            }
        });
    }
}