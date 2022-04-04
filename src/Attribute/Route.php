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

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)] class Route
{

    /**
     * Create path if needed.
     *
     * @param string $path
     * @return bool TRUE on success or if path already exists, FALSE if path cannot be created.
     */
    private function createPathIfNeeded(string $path): bool
    {
        return !(!is_dir($path) && @mkdir($path, 0777 & (~$this->umask), true) === false && !is_dir($path));
    }

    /**
     * @param string $filename
     * @param string $content
     * @return bool
     */
    protected function writeFile(string $filename, string $content): bool
    {
        $filepath = pathinfo($filename, PATHINFO_DIRNAME);

        if (!$this->createPathIfNeeded($filepath)) {
            return false;
        }

        if (!is_writable($filepath)) {
            return false;
        }

        $tmpFile = tempnam($filepath, 'swap');
        mkPathDir($tmpFile);
        if (file_put_contents($tmpFile, $content) !== false) {
            if (@rename($tmpFile, $filename)) {
                return true;
            }

            @unlink($tmpFile);

        }

        return false;
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
        if (count(glob(PROJECT_PATH . App::$routeDir . DIRECTORY_SEPARATOR . App::$app->getDir() .
                DIRECTORY_SEPARATOR
                . $versionDir . DIRECTORY_SEPARATOR . 'swap*')) > 0) {
            return null;
        }
        $save_path = PROJECT_PATH . App::$routeDir . DIRECTORY_SEPARATOR . App::$app->getDir() . DIRECTORY_SEPARATOR
            . $versionDir . DIRECTORY_SEPARATOR . 'builder.php';
        if (!file_exists($save_path)) {
            mkPathDir($save_path);
            $write_data = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . '../Route/buildTemplate.php');
            @file_put_contents($save_path, $write_data);
        } else {
            $write_data = '';
            try {
                $write_data = @file_get_contents($save_path);
            } catch (\Exception $e) {
            }
        }
        if ($write_data) {
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
                            $write_data .= $this->getRouterData($method, $classRoute->path .
                                ($attr ?
                                    $this->path : ''),
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
                            $write_data = 'Router::' . $method . '(path: \'' . $this->path . '\', callback: \'' .
                                $class->getName()
                                . '@'
                                . $methodName
                                . '\')';
                            if (is_string($this->middleware) && $this->middleware) {
                                $write_data .= '->addMiddleware(middleware: ' . $this->middleware . '::class)';
                            } else if (is_array($this->middleware)) {
                                foreach ($this->middleware as $middleware) {
                                    $write_data .= '->addMiddleware(middleware: ' . $middleware . '::class)';
                                }
                            }
                        }
                    } else if ($classRoute) {
                        $write_data .= $this->getRouterData($method, $classRoute->path .
                            ($attr ? $this->path : ''),
                            $class->getName() .
                            '@' . $methodName);
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
                    $this->writeFile($save_path, $write_data);
                }
            } else if ($this->paramPattern === []) {
                if ($classRoute) {
                    $write_data .= $this->getRouterData($this->methods, $classRoute->path .
                        ($attr ? $this->path : ''),
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
                $this->writeFile($save_path, $write_data);
            } else {
                if ($classRoute) {
                    $write_data .= $this->getRouterData($this->methods, $classRoute->path .
                        ($attr ? $this->path : ''),
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

                $this->writeFile($save_path, $write_data);
            }
        }
        return null;
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