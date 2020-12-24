<?php


namespace QApi\Attribute;


use QApi\App;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)] class Route
{


    /**
     * 生成路由规则
     * @param \ReflectionClass $class
     * @param string $controllerName
     * @param string $methodName
     * @return mixed
     */
    public function builder(\ReflectionClass $class, string $controllerName, string $methodName): mixed
    {
        $save_path = PROJECT_PATH . App::$routeDir . DIRECTORY_SEPARATOR . App::$app->getDir() . DIRECTORY_SEPARATOR
            . str_replace('.', '', App::getVersion()) . DIRECTORY_SEPARATOR . 'builder.php';
        if (!file_exists($save_path)) {
            mkPathDir($save_path);
            file_put_contents($save_path, file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . '../Route/buildTemplate.php'), LOCK_EX);
        }
        $data = file_get_contents($save_path);
        $classRoute = $class->getAttributes('QApi\Attribute\Route');
        if ($classRoute) {
            $classRoute = $classRoute[0]->newInstance();
        } else {
            $classRoute = null;
        }
        if (is_array($this->methods)) {
            foreach ($this->methods as $method) {
                if ($this->paramPattern === []) {
                    if ($classRoute) {
                        $write_data = 'Router::' . $method . '(path: \'' . $classRoute->path . $this->path . '\', callback: \'' .
                            $class->getName()
                            . '@'
                            . $methodName
                            . '\')';
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
                    $write_data = 'Router::' . $method . '(path: \'' . $classRoute->path . $this->path . '\', callback: \'' . $class->getName()
                        . '@'
                        . $methodName
                        . '\')';
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

                    foreach ($classRoute->paramPattern as $key => $pattern) {
                        $write_data .= '->paramPattern(paramName: \'' . $key . '\', pattern: \'' . $pattern . '\')';
                    }
                    foreach ($this->paramPattern as $key => $pattern) {
                        $write_data .= '->paramPattern(paramName: \'' . $key . '\', pattern: \'' . $pattern . '\')';
                    }

                } else {
                    $write_data = 'Router::' . $method . '(path: \'' . $this->path . '\', callback: \'' . $class->getName()
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
                    foreach ($this->paramPattern as $key => $pattern) {
                        $write_data .= '->paramPattern(paramName: \'' . $key . '\', pattern: \'' . $pattern . '\')';
                    }
                }
                $write_data .= ';';
                if (!str_contains($data, $write_data)) {
                    file_put_contents($save_path, "\n" . $write_data, FILE_APPEND);
                }
            }
        } else if ($this->paramPattern === []) {
            if ($classRoute) {
                $write_data = 'Router::' . $this->methods . '(path: \'' . $classRoute->path . $this->path . '\', callback: \'' .
                    $class->getName()
                    . '@'
                    . $methodName
                    . '\')';
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
                foreach ($classRoute->paramPattern as $key => $pattern) {
                    $write_data .= '->paramPattern(paramName: \'' . $key . '\', pattern: \'' . $pattern . '\')';
                }
            } else {
                $write_data = 'Router::' . $this->methods . '(path: \'' . $this->path . '\', callback: \'' .
                    $class->getName()
                    . '@'
                    . $methodName
                    . '\')';

                if (is_string($this->middleware) && $this->middleware) {
                    $write_data .= '->addMiddleware(middleware: \'' . $this->middleware . '\')';
                } else {
                    foreach ($this->middleware as $middleware) {
                        $write_data .= '->addMiddleware(middleware: \'' . $middleware . '\')';
                    }
                }
            }
            $write_data .= ';';
            if (!str_contains($data, $write_data)) {
                file_put_contents($save_path, "\n" . $write_data, FILE_APPEND);
            }
        } else {
            if ($classRoute) {
                $write_data = 'Router::' . $this->methods . '(path: \'' . $classRoute->path . $this->path . '\', callback: \'' .
                    $class->getName()
                    . '@'
                    . $methodName
                    . '\')';
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
                $write_data = 'Router::' . $this->methods . '(path: \'' . $this->path . '\', callback: \'' .
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
                foreach ($this->paramPattern as $key => $pattern) {
                    $write_data .= '->paramPattern(paramName: \'' . $key . '\', pattern: \'' . $pattern . '\')';
                }
            }
            $write_data .= ';';
            if (!str_contains($data, $write_data)) {
                file_put_contents($save_path, "\n" . $write_data, FILE_APPEND);
            }
        }
        return null;
    }

    /**
     * Route constructor.
     * @param string $path
     * @param string|array $methods
     * @param array $paramPattern
     * @param array|string|null $middleware
     */
    public function __construct(private string $path = '/', private string|array $methods = 'ALL', private array $paramPattern = [], private array|string|null $middleware = null){}
}