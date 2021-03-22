<?php /** @noinspection ALL */


namespace QApi\Attribute;


use Attribute;
use QApi\App;
use QApi\Attribute\Parameter\GetParam;
use QApi\Attribute\Parameter\HeaderParam;
use QApi\Attribute\Parameter\PathParam;
use QApi\Attribute\Parameter\PostParam;
use QApi\Http\MiddlewareInterface;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)] class Route
{

    /**
     * @param $path
     * @return bool|string
     */
    public function getData($path): bool|string
    {

        return file_get_contents($path);
    }

    /**
     * 生成路由规则
     * @param \ReflectionClass $class
     * @param string $controllerName
     * @param string $methodName
     * @param bool $attr
     * @return mixed
     */
    public function builder(\ReflectionClass $class, string $controllerName, string $methodName, bool $attr = true):
    mixed
    {
        $tmpControllerName = trim(str_replace(App::$app->getNameSpace(), '', $controllerName), '\\');
        $versionDir = substr($tmpControllerName, 0, stripos($tmpControllerName, '\\'));
        $save_path = PROJECT_PATH . App::$routeDir . DIRECTORY_SEPARATOR . App::$app->getDir() . DIRECTORY_SEPARATOR
            . $versionDir . DIRECTORY_SEPARATOR . 'builder.php';
        if (!file_exists($save_path)) {
            mkPathDir($save_path);
            file_put_contents($save_path, file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . '../Route/buildTemplate.php'), LOCK_EX);
        }
        $classRoute = $class->getAttributes(__CLASS__);
        if ($classRoute) {
            $classRoute = $classRoute[0]->newInstance();
        } else {
            $classRoute = null;
        }
        if (is_array($this->methods)) {
            foreach ($this->methods as $method) {
                if ($this->paramPattern === []) {
                    if ($classRoute) {
                        $write_data = $this->getRouterData($method, $classRoute->path . ($attr ? $this->path : ''),
                            $class->getName() . '@' . $methodName);
                        if (is_string($classRoute->middleware) && $classRoute->middleware) {
                            $write_data .= '->addMiddleware(middleware: \'' . $classRoute->middleware . '\',isClass: true)';
                        } else if (is_array($classRoute->middleware)) {
                            foreach ($classRoute->middleware as $middleware) {
                                $write_data .= '->addMiddleware(middleware: \'' . $middleware . '\',isClass: true)';
                            }
                        }
                        if (is_string($this->middleware) && $this->middleware) {
                            $write_data .= '->addMiddleware(middleware: \'' . $this->middleware . '\')';
                        } else if (is_array($this->middleware)) {
                            foreach ($this->middleware as $middleware) {
                                $write_data .= '->addMiddleware(middleware: \'' . $middleware . '\')';
                            }
                        }
                        foreach ($classRoute->paramPattern as $key => $pattern) {
                            $write_data .= '->paramPattern(paramName: \'' . $key . '\', pattern: \'' . $pattern . '\')';
                        }
                    } else {
                        $write_data = 'Router::' . $method . '(path: \'' . $this->path . '\', callback: \'' .
                            $class->getName()
                            . '@'
                            . $methodName
                            . '\')';
                        if (is_string($this->middleware) && $this->middleware) {
                            $write_data .= '->addMiddleware(middleware: \'' . $this->middleware . '\')';
                        } else if (is_array($this->middleware)) {
                            foreach ($this->middleware as $middleware) {
                                $write_data .= '->addMiddleware(middleware: \'' . $middleware . '\')';
                            }
                        }
                    }
                } else if ($classRoute) {

                    $write_data = $this->getRouterData($method, $classRoute->path . ($attr ? $this->path : ''),
                        $class->getName() .
                        '@' . $methodName);
                    if (is_string($classRoute->middleware) && $classRoute->middleware) {
                        $write_data .= '->addMiddleware(middleware: \'' . $classRoute->middleware . '\',isClass: true)';
                    } else if (is_array($classRoute->middleware)) {
                        foreach ($classRoute->middleware as $middleware) {
                            $write_data .= '->addMiddleware(middleware: \'' . $middleware . '\',isClass: true)';
                        }
                    }
                    if (is_string($this->middleware) && $this->middleware) {
                        $write_data .= '->addMiddleware(middleware: \'' . $this->middleware . '\')';
                    } else if (is_array($this->middleware)) {
                        foreach ($this->middleware as $middleware) {
                            $write_data .= '->addMiddleware(middleware: \'' . $middleware . '\')';
                        }
                    }

                    foreach ($classRoute->paramPattern as $key => $pattern) {
                        $write_data .= '->paramPattern(paramName: \'' . $key . '\', pattern: \'' . $pattern . '\')';
                    }
                    foreach ($this->paramPattern as $key => $pattern) {
                        $write_data .= '->paramPattern(paramName: \'' . $key . '\', pattern: \'' . $pattern . '\')';
                    }

                } else {
                    $write_data = $this->getRouterData($method, $this->path, $class->getName() . '@' . $methodName);

                    if (is_string($this->middleware) && $this->middleware) {
                        $write_data .= '->addMiddleware(middleware: \'' . $this->middleware . '\')';
                    } else if (is_array($this->middleware)) {
                        foreach ($this->middleware as $middleware) {
                            $write_data .= '->addMiddleware(middleware: \'' . $middleware . '\')';
                        }
                    }
                    foreach ($this->paramPattern as $key => $pattern) {
                        $write_data .= '->paramPattern(paramName: \'' . $key . '\', pattern: \'' . $pattern . '\')';
                    }
                }
                $write_data .= ';';
                if (!str_contains($this->getData($save_path), $write_data)) {
                    file_put_contents($save_path, "\n" . $write_data, FILE_APPEND | LOCK_EX);
                }
            }
        } else if ($this->paramPattern === []) {
            if ($classRoute) {
                $write_data = $this->getRouterData($this->methods, $classRoute->path . ($attr ? $this->path : ''),
                    $class->getName() . '@' . $methodName);
                if (is_string($classRoute->middleware) && $classRoute->middleware) {
                    $write_data .= '->addMiddleware(middleware: \'' . $classRoute->middleware . '\',isClass: true)';
                } else if (is_array($classRoute->middleware)) {
                    foreach ($classRoute->middleware as $middleware) {
                        $write_data .= '->addMiddleware(middleware: \'' . $middleware . '\',isClass: true)';
                    }
                }
                if (is_string($this->middleware) && $this->middleware) {
                    $write_data .= '->addMiddleware(middleware: \'' . $this->middleware . '\')';
                } else if (is_array($this->middleware)) {
                    foreach ($this->middleware as $middleware) {
                        $write_data .= '->addMiddleware(middleware: \'' . $middleware . '\')';
                    }
                }
                foreach ($classRoute->paramPattern as $key => $pattern) {
                    $write_data .= '->paramPattern(paramName: \'' . $key . '\', pattern: \'' . $pattern . '\')';
                }
            } else {
                $write_data = $this->getRouterData($this->methods, $this->path, $class->getName()
                    . '@'
                    . $methodName);
                if (is_string($this->middleware) && $this->middleware) {
                    $write_data .= '->addMiddleware(middleware: \'' . $this->middleware . '\')';
                } elseif (is_array($this->middleware)) {
                    foreach ($this->middleware as $middleware) {
                        $write_data .= '->addMiddleware(middleware: \'' . $middleware . '\')';
                    }
                }
            }
            $write_data .= ';';
            if (!str_contains($this->getData($save_path), $write_data)) {
                file_put_contents($save_path, "\n" . $write_data, FILE_APPEND | LOCK_EX);
            }
        } else {
            if ($classRoute) {
                $write_data = $this->getRouterData($this->methods, $classRoute->path . ($attr ? $this->path : ''),
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
                    $write_data .= '->addMiddleware(middleware: \'' . $classRoute->middleware . '\')';
                } else if (is_array($classRoute->middleware)) {
                    foreach ($classRoute->middleware as $middleware) {
                        $write_data .= '->addMiddleware(middleware: \'' . $middleware . '\')';
                    }
                }
                if (is_string($this->middleware) && $this->middleware) {
                    $write_data .= '->addMiddleware(middleware: \'' . $this->middleware . '\')';
                } else if (is_array($this->middleware)) {
                    foreach ($this->middleware as $middleware) {
                        $write_data .= '->addMiddleware(middleware: \'' . $middleware . '\')';
                    }
                }
            } else {
                $write_data = $this->getRouterData($this->methods, $this->path, $class->getName() . '@' . $methodName);
                if (is_string($this->middleware) && $this->middleware) {
                    $write_data .= '->addMiddleware(middleware: \'' . $this->middleware . '\')';
                } else if (is_array($this->middleware)) {
                    foreach ($this->middleware as $middleware) {
                        $write_data .= '->addMiddleware(middleware: \'' . $middleware . '\')';
                    }
                }
                foreach ($this->paramPattern as $key => $pattern) {
                    $write_data .= '->paramPattern(paramName: \'' . $key . '\', pattern: \'' . $pattern . '\')';
                }
            }
            $write_data .= ';';
            if (!str_contains($this->getData($save_path), $write_data)) {
                file_put_contents($save_path, "\n" . $write_data, FILE_APPEND | LOCK_EX);
            }
        }
        return null;
    }

    public function getRouterData(?string $method, ?string $path, string $callback): string
    {
        if ($path) {
            return 'Router::' . $method . '(path: \'' . $path . '\', callback: \'' . $callback . '\')';
        }

        return 'Router::' . $method . '(callback: \'' . $callback . '\')';
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
        private string $path = '',
        private string|array $methods = 'ALL',
        private array $paramPattern = [],
        private array|string|null $middleware = null,
        private string|null $summary = null,
        private string|null $description = null,
        private bool $checkParams = false,
    )
    {
    }
}