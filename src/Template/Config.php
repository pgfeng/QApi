<?php

namespace QApi\Template;


use QApi\Template\Interfaces\RuleInterface;
use QApi\Template\Loader\LoaderInterface;

class Config
{
    /**
     * @param LoaderInterface $loader
     * @param string $templatePath
     * @param string $compilationCachePath
     * @param string $rootPath
     * @param string $templateSuffix
     * @param RuleInterface[] $Rules
     */
    public function __construct(
        public LoaderInterface $loader,
        public string          $templatePath = 'template',
        public string          $compilationCachePath = 'template_c',
        public string          $rootPath = PROJECT_PATH . 'view',
        public string          $templateSuffix = '.html',
        public array           $Rules = [],
    )
    {
    }
}