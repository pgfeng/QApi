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
     * @var int
     */
    public int $umask = 0777;

    /**
     * @param $path
     * @return bool|string
     */
    public function getData($path): bool|string
    {
        try {
            return file_get_contents($path);
        } catch (WarningException $e) {
            usleep(500);
            return $this->getData($path);
        }
    }

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
//        @chmod($tmpFile, 0666 & (~$this->umask));
        mkPathDir($tmpFile);
        if (file_put_contents($tmpFile, $content) !== false) {
//            @chmod($tmpFile, 0666 & (~$this->umask));
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
        $save_path = PROJECT_PATH . App::$routeDir . DIRECTORY_SEPARATOR . App::$app->getDir() . DIRECTORY_SEPARATOR
            . $versionDir . DIRECTORY_SEPARATOR . 'builder.php';
        if (!file_exists($save_path)) {
            mkPathDir($save_path);
            file_put_contents($save_path, file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . '../Route/buildTemplate.php'), LOCK_EX);
        }
        $write_data = file_get_contents($save_path);
        //        $fp = fopen($save_path, 'a+');
        //        if (!flock($fp, LOCK_EX)) {
        //            usleep(500);
        //            return $this->builder($class, $controllerName, $methodName, $attr);
        //        } else {
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
                        $write_data = $this->getRouterData($method, $classRoute->path .
                                ($attr ?
                                $this->path : ''),
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

                    $write_data = $this->getRouterData($method, $classRoute->path .
                            ($attr ? $this->path : ''),
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
                    $write_data = $this->getRouterData($method, $this->path,
                            $class->getName() . '@' . $methodName);

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
                $this->writeFile($save_path, $write_data);
                //                    if (!str_contains($this->getData($save_path), $write_data)) {
                //                        fseek($fp, 0, SEEK_END);
                //                        fwrite($fp, "\n" . $write_data,);
                //                    }
            }
        } else if ($this->paramPattern === []) {
            if ($classRoute) {
                $write_data = $this->getRouterData($this->methods, $classRoute->path .
                        ($attr ? $this->path : ''),
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
                $write_data = $this->getRouterData($this->methods, $this->path,
                        $class->getName()
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

            $this->writeFile($save_path, $write_data);
            //                if (!str_contains($this->getData($save_path), $write_data)) {
            //                    fseek($fp, 0, SEEK_END);
            //                    fwrite($fp, "\n" . $write_data,);
            //                }
        } else {
            if ($classRoute) {
                $write_data = $this->getRouterData($this->methods, $classRoute->path .
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
                $write_data = $this->getRouterData($this->methods, $this->path,
                        $class->getName() . '@' . $methodName);
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

            $this->writeFile($save_path, $write_data);
            //                if (!str_contains($this->getData($save_path), $write_data)) {
            //                    fseek($fp, 0, SEEK_END);
            //                    fwrite($fp, "\n" . $write_data,);
            //                }
            //            }
            //            flock($fp, LOCK_UN);
            //            fclose($fp);
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
        public string $path = '',
        public string|array $methods = 'ALL',
        public array $paramPattern = [],
        public array|string|null $middleware = null,
        public string|null $summary = null,
        public string|null $description = null,
        public string|null $tag = '',
        public bool $checkParams = false,
    )
    {
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