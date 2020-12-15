<?php

namespace QApi\Command;


class Arguments
{
    // store options
    private static $optsArr = [];
    // store args
    private static $argsArr = [];
    // 是否解析过
    private static $isParse = false;

    public function __construct() {
        if(!self::$isParse) {
            self::parseArgs();
        }
    }

    /**
     * 获取选项值
     * @param string|NULL $opt
     * @return array|string|NULL
     */
    public function getOptVal($opt = NULL) {
        if(is_null($opt)) {
            return self::$optsArr;
        } else if(isset(self::$optsArr[$opt])) {
            return self::$optsArr[$opt];
        }
        return null;
    }

    /**
     * 获取命令行参数值
     * @param integer|NULL $index
     * @return array|string|NULL
     */
    public function getArgVal($index = NULL) {
        if(is_null($index)) {
            return self::$argsArr;
        } else if(isset(self::$argsArr[$index])) {
            return self::$argsArr[$index];
        }
        return null;
    }

    /**
     * 注册选项对应的回调处理函数, $callback 应该有一个参数, 用于接收选项值
     * @param string $opt
     * @param callable $callback 回调函数
     * @throws InvalidArgumentException
     */
    public function option($opt, callable $callback) {
        // check
        if(!is_callable($callback)) {
            throw new InvalidArgumentException(sprintf('Not a valid callback <%s>.', $callback));
        }
        if(isset(self::$optsArr[$opt])) {
            call_user_func($callback, self::$optsArr[$opt]);
        } else {
            throw new InvalidArgumentException(sprintf('Unknown option <%s>.', $opt));
        }
    }

    /**
     * 是否是 -s 形式的短选项
     * @param string $opt
     * @return string|boolean 返回短选项名
     */
    public static function isShortOptions($opt) {
        if(preg_match('/^\-([a-zA-Z0-9])$/', $opt, $matchs)) {
            return $matchs[1];
        }
        return false;
    }

    /**
     * 是否是 -svalue 形式的短选项
     * @param string $opt
     * @return array|boolean 返回短选项名以及选项值
     */
    public static function isShortOptionsWithValue($opt) {
        if(preg_match('/^\-([a-zA-Z0-9])(\S+)$/', $opt, $matchs)) {
            return [$matchs[1], $matchs[2]];
        }
        return false;
    }

    /**
     * 是否是 --longopts 形式的长选项
     * @param string $opt
     * @return string|boolean 返回长选项名
     */
    public static function isLongOptions($opt) {
        if(preg_match('/^\-\-([a-zA-Z0-9\-_]{2,})$/', $opt, $matchs)) {
            return $matchs[1];
        }
        return false;
    }

    /**
     * 是否是 --longopts=value 形式的长选项
     * @param string $opt
     * @return array|boolean 返回长选项名及选项值
     */
    public static function isLongOptionsWithValue($opt) {
        if(preg_match('/^\-\-([a-zA-Z0-9\-_]{2,})(?:\=(.*?))$/', $opt, $matchs)) {
            return [$matchs[1], $matchs[2]];
        }
        return false;
    }

    /**
     * 是否是命令行参数
     * @param string $value
     * @return boolean
     */
    public static function isArg($value) {
        return ! preg_match('/^\-/', $value);
    }

    /**
     * 解析命令行参数
     * @return array ['opts'=>[], 'args'=>[]]
     */
    final public static function parseArgs() {
        global $argv;
        if(!self::$isParse) {
            // index start from one
            $index = 1;
            $length = count($argv);
            while($index < $length) {
                // current value
                $curVal = $argv[$index];
                // check, short or long options
                if( ($key = self::isShortOptions($curVal)) || ($key = self::isLongOptions($curVal)) ) {
                    // go ahead
                    $index++;
                    if( isset($argv[$index]) && self::isArg($argv[$index]) ) {
                        self::$optsArr[$key] = $argv[$index];
                    } else {
                        self::$optsArr[$key] = true;
                        // back away
                        $index--;
                    }
                } // check, short or long options with value
                else if( ($key = self::isShortOptionsWithValue($curVal))
                    || ($key = self::isLongOptionsWithValue($curVal)) ) {
                    self::$optsArr[$key[0]] = $key[1];
                } // args
                else if( self::isArg($curVal) ) {
                    self::$argsArr[] = $curVal;
                }
                // incr index
                $index++;
            }
            self::$isParse = true; // change status
        }
        return ['opts'=>self::$optsArr, 'args'=>self::$argsArr];
    }
}
