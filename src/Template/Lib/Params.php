<?php

namespace QApi\Template\Lib;

/**
 * Params
 */
class Params implements \Stringable
{
    const NAMED = 'NAMED';
    const UNNAMED = 'UNNAMED';

    public string $type;

    /**
     * @var Parameter[]
     */
    private array $params = [];
    public string $originalParams = '';

    public function __construct(string $params)
    {
        $this->originalParams = trim($params);
        $params = trim($params, ' ');
        $params = ' ' . $params . ' ';
        if (preg_match_all('/([a-zA-Z_$][a-zA-Z0-9_]*)=[\s]*([^\s]*)[\s]+/U', $params, $matches)) {
            $this->type = Params::NAMED;
            foreach ($matches[0] as $key => $originalString) {
                $this->params[$matches[1][$key]] = new Parameter($originalString, $matches[1][$key], $matches[2][$key], $this->type);
            }
        } else {
            $this->type = Params::UNNAMED;
            $params = explode(' ', trim(preg_replace('/\s+/', ' ', $params), ' '));
            foreach ($params as $key => $originalString) {
                $this->params[] = new Parameter($originalString, $key, $originalString, $this->type);
            }
        }
    }

    /**
     * @param $key
     * @return ?Parameter
     */
    public function getParam($key): ?Parameter
    {
        return $this->params[$key]??null;
    }

    public function __toString(): string
    {
        return implode('', $this->params);
    }
}