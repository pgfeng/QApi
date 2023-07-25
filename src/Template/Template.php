<?php

namespace QApi\Template;

use ErrorException;
use Exception;
use QApi\Data;
use QApi\Exception\ParseException;
use QApi\Template\Error\Error;
use QApi\Template\Error\LoaderError;
use QApi\Template\Error\SyntaxError;
use QApi\Template\Interfaces\AfterEventRuleInterface;
use QApi\Template\Interfaces\BeforeEventRuleInterface;
use QApi\Template\Interfaces\RuleInterface;
use QApi\Template\Lib\Source;
use QApi\Template\Lib\Statement;
use QApi\Template\Lib\Stream;
use QApi\Template\Rules\AnnotationRule;
use QApi\Template\Rules\ComponentRule;
use QApi\Template\Rules\DoRules;
use QApi\Template\Rules\EachRule;
use QApi\Template\Rules\forRule;
use QApi\Template\Rules\ifRule;
use QApi\Template\Rules\MacroRule;
use QApi\Template\Rules\TemplateRule;
use QApi\Template\Rules\VariableRule;
use QApi\Template\Rules\VerbatimRule;

/**
 * Template
 */
class Template
{

    public array $data = [];

    /**
     * @var array[]
     */
    private array $macro = [];

    public string $path;

    /**
     * @var RuleInterface[]
     */
    private array $rules = [];

    public function __construct(public Config $config)
    {
        $this->addRule([
            new AnnotationRule($this),
            new ifRule($this),
            new forRule($this),
            new VariableRule($this),
            new EachRule($this),
            new TemplateRule($this),
            new VerbatimRule($this),
            new DoRules($this),
            new MacroRule($this),
            new ComponentRule($this),
        ]);
    }

    /**
     * @param string|array|Data $name
     * @param mixed|null $value
     * @return Template
     */
    public function assign(string|array|Data $name, mixed $value = null): self
    {
        if (is_string($name)) {
            $this->data[$name] = $value;
        } else {
            if ($name instanceof Data) {
                $name = $name->getArrayCopy();
            }
            $this->data = array_merge($this->data, $name);
        }
        return $this;
    }

    public function templateHasModify($templateName): bool
    {
        $template_c_path = $this->getTemplateCompilationCachePath($templateName);
        $paths = [
            $templateName . $this->config->templateSuffix,
        ];
        try {
            $template_c_content = file_get_contents($template_c_path);
            preg_match_all('/' . preg_quote('@Template(', '/') . '(.*)#(.*):(\d+)\)\s+' . preg_quote('*/', '/') . '/iU', $template_c_content, $matches);
            $paths = array_unique(array_merge($paths, $matches[2]));

            $ftime = filemtime($template_c_path);
            foreach ($paths as $path) {
                if ($this->config->loader->isFresh($path, $ftime)) {
                    return true;
                }
            }
        } catch (Exception $exception) {
        }
        return false;
    }

    /**
     * @param $templateName
     * @return Source
     */
    public function getSource($templateName): Source
    {
        return $this->config->loader->getSource($templateName . $this->config->templateSuffix);
    }


    /**
     * @param Source $source
     * @param string|null $template_c_path
     * @return Source
     */
    public function compile(Source $source, ?string $template_c_path = null): Source
    {
        if (true || $template_c_path === null || $this->templateHasModify(substr($source->name, 0, strlen($source->name) - strlen($this->config->templateSuffix)))) {
            foreach ($this->rules as $rule) {
                if ($rule instanceof BeforeEventRuleInterface) {
                    $source = $rule->before($source);
                }
            }
            foreach ($this->rules as $rule) {
                $source = $rule->parse($source);
            }
            foreach ($this->rules as $rule) {
                if ($rule instanceof AfterEventRuleInterface) {
                    $source = $rule->after($source);
                }
            }
            if ($template_c_path) {
                mkPathDir($template_c_path);
                file_put_contents($template_c_path, $source);
            }
        }
        return $source;
    }

    /**
     * @param string $path
     * @return string
     */
    public function getTemplatePath(string $path): string
    {
        return $this->config->rootPath . DIRECTORY_SEPARATOR . trim($this->config->templatePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . trim($path, DIRECTORY_SEPARATOR) . $this->config->templateSuffix;
    }

    /**
     * @param string $path
     * @return string
     */
    public function getTemplateCompilationCachePath(string $path): string
    {
        return $this->config->rootPath . DIRECTORY_SEPARATOR . trim($this->config->compilationCachePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . trim($path, DIRECTORY_SEPARATOR) . '.php';
    }


    /**
     * @param string $path
     * @param array $data
     * @return void
     */
    public function renderOutput(string $path, array $data = [])
    {
        if ($data) {
            $this->assign($data);
        }
        $template_c_path = $this->getTemplateCompilationCachePath($path);
        $source = $this->config->loader->getSource($path . $this->config->templateSuffix);
        $this->compile($source, $template_c_path);
        $this->run($template_c_path);
    }

    /**
     * @param $template_c_path
     * @return void
     * @throws Error
     */
    public function run($template_c_path)
    {
        try {
            include $template_c_path;
        } catch (Exception|ErrorException|ParseException|\Error $exception) {
            $this->throwError($exception);
        }
    }

    /**
     * @param Exception|ErrorException|ParseException|Error|\ParseError $exception
     * @return void
     * @throws Error
     */
    public function throwError(Exception|ErrorException|ParseException|Error|\ParseError $exception)
    {
        ob_clean();
        throw new Error($exception->getMessage(), $exception->getLine(), $this, $exception->getPrevious() ?? $exception);
    }

    /**
     * @param string $content
     * @param array $data
     * @return array|string|string[]|null
     * @throws Error
     */
    public function fetch(string $content, array $data = [])
    {
        Stream::register();
        $path = $this->getTemplateCompilationCachePath('.runtime' . DIRECTORY_SEPARATOR . md5($content));
        $this->compile($content, $path);
        ob_start();
        $this->run($path);
        $data = ob_get_clean();
        unlink($path);
        return preg_replace("/[\s\\t]{2,}/", "", $data);
    }

    /**
     * @param string $path
     * @param array $data
     * @return string
     */
    public function display(string $path, array $data = []): string
    {
        ob_start();
        $this->renderOutput($path, $data);
        $data = ob_get_clean();
        return preg_replace("/\\n+/", "\n", $data);
    }

    /**
     * @param RuleInterface|RuleInterface[] $rule
     * @return void
     */
    public function addRule(RuleInterface|array $rule): void
    {
        if (is_array($rule)) {
            $this->rules = array_merge($this->rules, $rule);
        } else {
            $this->rules[] = $rule;
        }
    }


    /**
     * @return RuleInterface[]
     */
    public function getRules(): array
    {
        return $this->rules;
    }
}