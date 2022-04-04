<?php


namespace QApi\Exception;


use QApi\App;

class ParseException extends \ErrorException
{
    public function __construct($message = "", $code = 0, $severity = 1, $filename = __FILE__, $line = __LINE__, $previous = null)
    {
        parent::__construct($message, $code, $severity, $filename, $line, $previous);
    }
}