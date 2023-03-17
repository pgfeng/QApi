<?php /** @noinspection ALL */


namespace QApi\Attribute;


use Attribute;
use QApi\App;
use QApi\Attribute\Parameter\GetParam;
use QApi\Attribute\Parameter\HeaderParam;
use QApi\Attribute\Parameter\PathParam;
use QApi\Attribute\Parameter\PostParam;
use QApi\Exception\WarningException;
use QApi\Http\MiddlewareInterface;
use QApi\Router;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE | Attribute::TARGET_FUNCTION)] class Route
{


    /**
     * 生成路由规则
     * @param \ReflectionClass $class
     * @param string $controllerName
     * @param string $methodName
     * @param bool $attr
     * @return mixed
     */
    public function builder(\ReflectionClass $class, string $controllerName, string $methodName, bool $attr, string $classPath):
    mixed
    {
        $cacheKey = $classPath . '@' . $methodName . '#' . $this->path;
        $classCache = Router::$routerBuilderCache->get($cacheKey);
        if ($classCache) {
            if ($classCache['mtime'] >= filemtime($classPath)) {
                return $classCache['route'];
            }
        }
        $classRoute = $class->getAttributes(__CLASS__);
        if ($classRoute) {
            $classRoute = $classRoute[0]->newInstance();
        } else {
            $classRoute = null;
        }
        $write_data = '';
        if (is_array($this->methods)) {
            foreach ($this->methods as $method) {
                if ($this->paramPattern === []) {
                    if ($classRoute) {
                        $write_data .= $this->getRouterData($this->methods, $classRoute->path ? (stripos($this->path, '/') === 0 ? ($attr ? $this->path : '') : (rtrim($classRoute->path, '/') . '/' .
                            ($attr ? $this->path : ''))) : ($attr ? $this->path : ''),
                            $class->getName() . '@' . $methodName);
                        if (is_string($classRoute->middleware) && $classRoute->middleware) {
                            $write_data .= '->addMiddleware(middleware: ' . $classRoute->middleware . '::class, isClass: true)';
                        } else if (is_array($classRoute->middleware)) {
                            foreach ($classRoute->middleware as $middleware) {
                                $write_data .= '->addMiddleware(middleware: ' . $middleware . '::class, isClass: true)';
                            }
                        }
                        if (is_string($this->middleware) && $this->middleware) {
                            $write_data .= '->addMiddleware(middleware: ' . $this->middleware . '::class)';
                        } else if (is_array($this->middleware)) {
                            foreach ($this->middleware as $middleware) {
                                $write_data .= '->addMiddleware(middleware: ' . $middleware . '::class)';
                            }
                        }
                        foreach ($classRoute->paramPattern as $key => $pattern) {
                            $write_data .= '->paramPattern(paramName: \'' . $key . '\', pattern: \'' . $pattern . '\')';
                        }
                    } else {
                        $write_data .= $this->getRouterData($method, $classRoute->path .
                            ($attr ? $this->path : ''),
                            $class->getName() .
                            '@' . $methodName);
                        if (is_string($this->middleware) && $this->middleware) {
                            $write_data .= '->addMiddleware(middleware: ' . $this->middleware . '::class)';
                        } else if (is_array($this->middleware)) {
                            foreach ($this->middleware as $middleware) {
                                $write_data .= '->addMiddleware(middleware: ' . $middleware . '::class)';
                            }
                        }
                    }
                } else if ($classRoute) {
                    $write_data .= $this->getRouterData($this->methods, $classRoute->path ? (stripos($this->path, '/') === 0 ? ($attr ? $this->path : '') : (rtrim($classRoute->path, '/') . '/' .
                        ($attr ? $this->path : ''))) : ($attr ? $this->path : ''),
                        $class->getName() . '@' . $methodName);
                    if (is_string($classRoute->middleware) && $classRoute->middleware) {
                        $write_data .= '->addMiddleware(middleware: ' . $classRoute->middleware . '::class, isClass: true)';
                    } else if (is_array($classRoute->middleware)) {
                        foreach ($classRoute->middleware as $middleware) {
                            $write_data .= '->addMiddleware(middleware: ' . $middleware . '::class, isClass: true)';
                        }
                    }
                    if (is_string($this->middleware) && $this->middleware) {
                        $write_data .= '->addMiddleware(middleware: ' . $this->middleware . '::class)';
                    } else if (is_array($this->middleware)) {
                        foreach ($this->middleware as $middleware) {
                            $write_data .= '->addMiddleware(middleware: ' . $middleware . '::class)';
                        }
                    }
                    foreach ($classRoute->paramPattern as $key => $pattern) {
                        $write_data .= '->paramPattern(paramName: \'' . $key . '\', pattern: \'' . $pattern . '\')';
                    }
                    foreach ($this->paramPattern as $key => $pattern) {
                        $write_data .= '->paramPattern(paramName: \'' . $key . '\', pattern: \'' . $pattern . '\')';
                    }

                } else {
                    $write_data .= $this->getRouterData($method, $this->path,
                        $class->getName() . '@' . $methodName);
                    if (is_string($this->middleware) && $this->middleware) {
                        $write_data .= '->addMiddleware(middleware: ' . $this->middleware . '::class)';
                    } else if (is_array($this->middleware)) {
                        foreach ($this->middleware as $middleware) {
                            $write_data .= '->addMiddleware(middleware: ' . $middleware . '::class)';
                        }
                    }
                    foreach ($this->paramPattern as $key => $pattern) {
                        $write_data .= '->paramPattern(paramName: \'' . $key . '\', pattern: \'' . $pattern . '\')';
                    }
                }
                $write_data .= ';';
            }
        } else if ($this->paramPattern === []) {
            if ($classRoute) {
                $write_data .= $this->getRouterData($this->methods, $classRoute->path ? (stripos($this->path, '/') === 0 ? ($attr ? $this->path : '') : (rtrim($classRoute->path, '/') . '/' .
                    ($attr ? $this->path : ''))) : ($attr ? $this->path : ''),
                    $class->getName() . '@' . $methodName);
                if (is_string($classRoute->middleware) && $classRoute->middleware) {
                    $write_data .= '->addMiddleware(middleware: ' . $classRoute->middleware . '::class, isClass: true)';
                } else if (is_array($classRoute->middleware)) {
                    foreach ($classRoute->middleware as $middleware) {
                        $write_data .= '->addMiddleware(middleware: ' . $middleware . '::class, isClass: true)';
                    }
                }
                if (is_string($this->middleware) && $this->middleware) {
                    $write_data .= '->addMiddleware(middleware: ' . $this->middleware . '::class)';
                } else if (is_array($this->middleware)) {
                    foreach ($this->middleware as $middleware) {
                        $write_data .= '->addMiddleware(middleware: ' . $middleware . '::class)';
                    }
                }
                foreach ($classRoute->paramPattern as $key => $pattern) {
                    $write_data .= '->paramPattern(paramName: \'' . $key . '\', pattern: \'' . $pattern . '\')';
                }
            } else {
                $write_data .= $this->getRouterData($this->methods, $this->path,
                    $class->getName()
                    . '@'
                    . $methodName);
                if (is_string($this->middleware) && $this->middleware) {
                    $write_data .= '->addMiddleware(middleware: ' . $this->middleware . '::class)';
                } elseif (is_array($this->middleware)) {
                    foreach ($this->middleware as $middleware) {
                        $write_data .= '->addMiddleware(middleware: ' . $middleware . '::class)';
                    }
                }
            }
            $write_data .= ';';
        } else {
            if ($classRoute) {
                $write_data .= $this->getRouterData($this->methods, $classRoute->path ? (stripos($this->path, '/') === 0 ? ($attr ? $this->path : '') : (rtrim($classRoute->path, '/') . '/' .
                    ($attr ? $this->path : ''))) : ($attr ? $this->path : ''),
                    $class->getName()
                    . '@'
                    . $methodName);
                foreach ($classRoute->paramPattern as $key => $pattern) {
                    $write_data .= '->paramPattern(paramName: \'' . $key . '\', pattern: \'' . $pattern . '\')';
                }
                foreach ($this->paramPattern as $key => $pattern) {
                    $write_data .= '->paramPattern(paramName: \'' . $key . '\', pattern: \'' . $pattern . '\')';
                }
                if (is_string($classRoute->middleware) && $classRoute->middleware) {
                    $write_data .= '->addMiddleware(middleware: ' . $classRoute->middleware . '::class)';
                } else if (is_array($classRoute->middleware)) {
                    foreach ($classRoute->middleware as $middleware) {
                        $write_data .= '->addMiddleware(middleware: ' . $middleware . '::class)';
                    }
                }
                if (is_string($this->middleware) && $this->middleware) {
                    $write_data .= '->addMiddleware(middleware: ' . $this->middleware . '::class)';
                } else if (is_array($this->middleware)) {
                    foreach ($this->middleware as $middleware) {
                        $write_data .= '->addMiddleware(middleware: ' . $middleware . '::class)';
                    }
                }
            } else {
                $write_data .= $this->getRouterData($this->methods, $this->path,
                    $class->getName() . '@' . $methodName);
                if (is_string($this->middleware) && $this->middleware) {
                    $write_data .= '->addMiddleware(middleware: ' . $this->middleware . '::class)';
                } else if (is_array($this->middleware)) {
                    foreach ($this->middleware as $middleware) {
                        $write_data .= '->addMiddleware(middleware: ' . $middleware . '::class)';
                    }
                }
                foreach ($this->paramPattern as $key => $pattern) {
                    $write_data .= '->paramPattern(paramName: \'' . $key . '\', pattern: \'' . $pattern . '\')';
                }
            }
            $write_data .= ';';

        }
        Router::$routerBuilderCache->set($cacheKey, [
            'path' => $classPath,
            'method' => $methodName,
            'mtime' => time(),
            'route' => $write_data,
        ]);
        return $write_data;
    }

    public function getRouterData(?string $method, ?string $path, string $callback): string
    {
        if ($path) {
            return "\nRouter::" . $method . '(path: \'' . $path . '\', callback: \'' . $callback . '\')';
        }

        return "\nRouter::" . $method . '(callback: \'' . $callback . '\')';
    }

    /**
     * Route constructor.
     * @param string $path
     * @param string|array $methods
     * @param array $paramPattern
     * @param array|string|null $middleware
     * @param string|null $summary
     * @param string|null $description
     * @param array $params
     */
    public function __construct(
        public string            $path = '',
        public string|array      $methods = 'ALL',
        public array             $paramPattern = [],
        public array|string|null $middleware = null,
        public string|null       $summary = null,
        public string|null       $description = null,
        public string|null|array $tag = '',
        public bool              $checkParams = false,
    )
    {
        if ($this->tag && is_string($this->tag)) {
            $this->tag = explode('-', $this->tag);
        }
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'path' => $this->path,
            'methods' => $this->methods,
            'paramPattern' => $this->paramPattern,
            'middleware' => $this->middleware,
            'summary' => $this->summary,
            'description' => $this->description,
            'tag' => $this->tag,
            'checkParams' => $this->checkParams
        ];
    }
}