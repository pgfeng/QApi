<?php

namespace QApi\Template\Error;

use QApi\Template\Template;

class Error extends \Exception
{
    private $lineno;
    private $name;
    private $rawMessage;
    private $sourcePath;
    private $sourceCode;

    /**
     * Constructor.
     *
     * By default, automatic guessing is enabled.
     *
     * @param string $message The error message
     * @param int $lineno The template line where the error occurred
     * @param Template|null $context
     * @param \Exception|null $previous
     */
    public function __construct(string $message, int $lineno = -1, Template $context = null, \Exception|ParseError|\ParseError $previous = null)
    {
        if (preg_match('/on line (\d+)/',$message,$matches)){
            $this->line = $matches[1];
        }else{
            $this->line = $previous->getLine();
        }
        $this->file = $previous->getFile();
        $fileContent = file_get_contents($this->file);
        $lines = explode("\n",$fileContent);
        if (preg_match_all('/'.preg_quote('@Template(','/').'(.*)#(.*):(\d+)\)\s+'.preg_quote('*/','/').'/iU',$lines[$this->line-1],$matches)){
            $this->file = end($matches[1]);
            $this->rawMessage = '['.end($matches[2]).']:'.$message;
            $this->line = end($matches[3]);
        }else{
            $this->lineno = $lineno;
            $this->rawMessage = $message;
        }
        parent::__construct($this->rawMessage, 0, $previous);
        $this->updateRepr();
    }

    public function getRawMessage(): string
    {
        return $this->rawMessage;
    }

    public function getTemplateLine(): int
    {
        return $this->lineno;
    }

    public function setTemplateLine(int $lineno): void
    {
        $this->lineno = $lineno;

        $this->updateRepr();
    }

    public function getSourceContext(): ?Source
    {
        return $this->name ? new Source($this->sourceCode, $this->name, $this->sourcePath) : null;
    }

    public function setSourceContext(Source $source = null): void
    {
        if (null === $source) {
            $this->sourceCode = $this->name = $this->sourcePath = null;
        } else {
            $this->sourceCode = $source->getCode();
            $this->name = $source->getName();
            $this->sourcePath = $source->getPath();
        }

        $this->updateRepr();
    }

    public function guess(): void
    {
        $this->guessTemplateInfo();
        $this->updateRepr();
    }

    public function appendMessage($rawMessage): void
    {
        $this->rawMessage .= $rawMessage;
        $this->updateRepr();
    }

    private function updateRepr(): void
    {
        $this->message = $this->rawMessage;

        if ($this->sourcePath && $this->lineno > 0) {
            $this->file = $this->sourcePath;
            $this->line = $this->lineno;

            return;
        }

        $dot = false;
        if ('.' === substr($this->message, -1)) {
            $this->message = substr($this->message, 0, -1);
            $dot = true;
        }

        $questionMark = false;
        if ('?' === substr($this->message, -1)) {
            $this->message = substr($this->message, 0, -1);
            $questionMark = true;
        }

        if ($this->name) {
            if (\is_string($this->name) || (\is_object($this->name) && method_exists($this->name, '__toString'))) {
                $name = sprintf('"%s"', $this->name);
            } else {
                $name = json_encode($this->name);
            }
            $this->message .= sprintf(' in %s', $name);
        }

        if ($this->lineno && $this->lineno >= 0) {
            $this->message .= sprintf(' at line %d', $this->lineno);
        }

        if ($dot) {
            $this->message .= '.';
        }

        if ($questionMark) {
            $this->message .= '?';
        }
    }

    private function guessTemplateInfo(): void
    {
        $template = null;
        $templateClass = null;

        $backtrace = debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS | \DEBUG_BACKTRACE_PROVIDE_OBJECT);
        foreach ($backtrace as $trace) {
            if (isset($trace['object']) && $trace['object'] instanceof Template) {
                $currentClass = \get_class($trace['object']);
                $isEmbedContainer = null === $templateClass ? false : 0 === strpos($templateClass, $currentClass);
                if (null === $this->name || ($this->name == $trace['object']->getTemplateName() && !$isEmbedContainer)) {
                    $template = $trace['object'];
                    $templateClass = \get_class($trace['object']);
                }
            }
        }

        // update template name
        if (null !== $template && null === $this->name) {
            $this->name = $template->getTemplateName();
        }

        // update template path if any
        if (null !== $template && null === $this->sourcePath) {
            $src = $template->getSourceContext();
            $this->sourceCode = $src->getCode();
            $this->sourcePath = $src->getPath();
        }

        if (null === $template || $this->lineno > -1) {
            return;
        }

        $r = new \ReflectionObject($template);
        $file = $r->getFileName();

        $exceptions = [$e = $this];
        while ($e = $e->getPrevious()) {
            $exceptions[] = $e;
        }

        while ($e = array_pop($exceptions)) {
            $traces = $e->getTrace();
            array_unshift($traces, ['file' => $e->getFile(), 'line' => $e->getLine()]);

            while ($trace = array_shift($traces)) {
                if (!isset($trace['file']) || !isset($trace['line']) || $file != $trace['file']) {
                    continue;
                }

                foreach ($template->getDebugInfo() as $codeLine => $templateLine) {
                    if ($codeLine <= $trace['line']) {
                        // update template line
                        $this->lineno = $templateLine;

                        return;
                    }
                }
            }
        }
    }
}