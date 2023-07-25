<?php

namespace QApi\Template\Error;

use JetBrains\PhpStorm\Pure;
use QApi\Template\Lib\Line;
use QApi\Template\Lib\Source;

class ParseError extends \Error
{
    /**
     * Constructor.
     *
     * By default, automatic guessing is enabled.
     *
     * @param string $message The error message
     * @param Source $source
     * @param Line $line
     */
    #[Pure(true)] public function __construct(string $message, Source $source, Line $line, \Exception $previous = null)
    {
        $this->line = $line->lineno;
        $this->file = $source->path;
        $fileContent = file_get_contents($this->file);
        $this->rawMessage = $message;
        parent::__construct($this->rawMessage, 0, $previous);
    }
}