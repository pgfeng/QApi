<?php

namespace QApi\Template\Rules;

class Nodes
{

    private array $nodeLists = [];

    /**
     * Parse Node
     * @param array $nodes
     * @param string $beginNodeName
     * @param string|null $endNodeName
     */
    public function __construct(private array $nodes, private string $beginNodeName, private ?string $endNodeName = null)
    {
        foreach ($this->nodes as $k => $node) {
            $this->nodes[$k]['name'] = strtoupper($node['name']);
        }
        if (!$this->endNodeName) {
            $this->endNodeName = 'End' . $beginNodeName;
        }
    }


    /**
     * @param int $level
     * @return array
     */
    public function getNode(): array
    {
        foreach ($this->nodes as $node) {
            if (strtoupper($node['name']) === strtoupper($this->beginNodeName)) {
                $n = [
                    'startLine' => $node['line'],
                    'endLine' => -1,
                    'startName' => $node['name'],
                    'params' => $node['params'],
                    'endName' => null,
                ];
                $n = $this->findEnd($n);
                if ($n) {
                    $this->nodeLists[] = $n;
                }
            }
        }
        uasort($this->nodeLists,function ($a,$b){
            return $b['level']-$a['level'];
        });
        return $this->nodeLists;
    }

    /**
     * @param $n
     * @param $needLevel
     * @return mixed
     */
    public function findEnd($n): mixed
    {
        $level = 0;
        $relative = 0;
        foreach ($this->nodes as $node) {
            if (strtoupper($node['name']) === strtoupper($this->beginNodeName)) {
                $level += 1;
                if ($node['line'] === $n['startLine']) {
                    $relative = $level;
                }
            } else if (strtoupper($node['name']) === strtoupper($this->endNodeName)) {
                if ($node['line'] > $n['startLine'] && $relative === $level) {
                    $n['level'] = $level;
                    $n['endName'] = $node['name'];
                    $n['endLine'] = $node['line'];
                    return $n;
                } else {
                    $level -= 1;
                }
            }
        }
        return null;
    }

}