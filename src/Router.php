<?php


namespace QApi;


use JetBrains\PhpStorm\NoReturn;
use QApi;

/**
 * Class Router
 * @package QApi
 * @method static Router get(string|null $path = null, Callable|string|null $callback = null)
 * @method static Router post(string|null $path = null, Callable|string|null $callback = null)
 * @method static Router put(string|null $path = null, Callable|string|null $callback = null)
 * @method static Router delete(string|null $path = null, Callable|string|null $callback = null)
 * @method static Router options(string|null $path = null, Callable|string|null $callback = null)
 * @method static Router head(string|null $path = null, Callable|string|null $callback = null)
 * @method static Router all(string|null $path = null, Callable|string|null $callback = null)
 */
class Router
{
    public static array $router = [];

    public static array $middlewareList = [];
    public static array $classMiddlewareList = [];

    /**
     * 存储路由
     * 如果当前请求类型不存在会自动向ALL中查找
     * @var array
     */
    public static array $routeLists = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'DELETE' => [],
        'OPTIONS' => [],
        'HEAD' => [],
        'ALL' => [],
    ];

    /**
     * 初始化
     */
    public static function init(): void
    {
        if (Config::app()->getRunMode() !== QApi\Enumeration\RunMode::PRODUCTION) {
            self::BuildRoute(Config::$app->getNameSpace());
        }
        $versions = Config::versions();
        foreach ($versions as $version) {
            $base_path = PROJECT_PATH . App::$routeDir . DIRECTORY_SEPARATOR . App::$app->getDir() . DIRECTORY_SEPARATOR
                . str_replace('.', '', $version->versionName) . DIRECTORY_SEPARATOR;
            mkPathDir($base_path . 'builder.php');
            $data = scandir($base_path);
            foreach ($data as $file) {
                if ($file !== '.' && $file !== '..') {
                    include $base_path . $file;
                }
            }
            unset($base_path);
            if ($version->versionName === App::getVersion()) {
                break;
            }
        }
    }

    /**
     * @param string $nameSpace
     */
    #[NoReturn] public static function BuildRoute(string $nameSpace): void
    {
        $builder_file_path = PROJECT_PATH . App::$routeDir . DIRECTORY_SEPARATOR . App::$app->getDir() .
            DIRECTORY_SEPARATOR
            . str_replace('.', '', App::getVersion()) . DIRECTORY_SEPARATOR . 'builder.php';
        if (file_exists($builder_file_path)) {
            unlink($builder_file_path);
        }
        $version_path = str_replace('.', '', App::getVersion());
        $base_path = PROJECT_PATH . App::$app->getDir() . DIRECTORY_SEPARATOR . $version_path .
            DIRECTORY_SEPARATOR;
        mkPathDir($base_path . 'builder.php');
        $nameSpace .= '\\' . $version_path;
        try {
            self::build(scandir($base_path), $base_path, $nameSpace, $base_path);
        } catch (\ReflectionException $e) {
            $message = $e->getMessage();
            $file = $e->getFile();
            $line = $e->getLine();
            $errorType = get_class($e);
            error_log("\x1b[31;1m {$errorType}：" . $message . "\e[0m\n\t\t" . " in " . $file . ' on line ' .
                $line, 0);
        }
    }

    /**
     * 解析注解并且声称
     * @param $san_files
     * @param string $parent_path
     * @param string $nameSpace
     * @param string $base_path
     * @throws \ReflectionException
     */
    public static function build($san_files, string $parent_path, string $nameSpace, string $base_path): void
    {
        foreach ($san_files as $path) {
            if ($path !== '.' && $path !== '..') {
                if (is_dir($parent_path . $path)) {
                    self::build(scandir($parent_path . $path . DIRECTORY_SEPARATOR), $parent_path . $path . DIRECTORY_SEPARATOR,
                        $nameSpace, $base_path);
                } else if (preg_match('#(.+)Controller.php#', $path, $match)) {
                    $path_class = $nameSpace . '\\' . str_replace('/', '\\', str_replace($base_path, '',
                            $parent_path)) . $match[1] . 'Controller';
                    $refClass = new \ReflectionClass(new $path_class);
                    $methods = $refClass->getMethods();
                    foreach ($methods as $method) {
                        if (substr($method->getName(), -6) === 'Action') {
                            $methodAttributes = $method->getAttributes(QApi\Attribute\Route::class);
                            if (!$methodAttributes) {
                                $classAttrbutes = $refClass->getAttributes(QApi\Attribute\Route::class);
                                if ($classAttrbutes) {
                                    foreach ($classAttrbutes as $item) {
                                        $item->newInstance()->builder($refClass, $path_class, $method->getName(),false);
                                    }
                                }
                            } else {
                                foreach ($methodAttributes as $key => $item) {
                                    $item->newInstance()->builder($refClass, $path_class, $method->getName());
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Router constructor.
     * @param string $method
     * @param string|null $path
     * @param  $runData
     */
    public
    function __construct(protected string $method, protected string|null $path, protected $runData)
    {
        self::$routeLists[$method][$path] = [
            'callback' => $runData,
            'pattern' => [],
            'middleware' => [],
        ];
    }

    /**
     * @param string $method
     * @param array $params
     * @return Router
     */
    public
    static function __callStatic(string $method, array $params = []): static
    {
        return new static($method, $params['path']??null, $params['callback']);
    }

    /**
     * @param string $paramName
     * @param string $pattern
     * @return Router
     */
    public
    function paramPattern(string $paramName, string $pattern): self
    {
        self::$routeLists[$this->method][$this->path]['pattern'][$paramName] = $pattern;
        return $this;
    }

    /**
     * @param string $middleware
     * @param bool $isClass
     * @return $this
     */
    public function addMiddleware(string $middleware, bool $isClass = false): self
    {
        if (!in_array($middleware, self::$routeLists[$this->method][$this->path]['middleware'], true)) {
            self::$routeLists[$this->method][$this->path]['middleware'][] = $middleware;
            if (is_string(self::$routeLists[$this->method][$this->path]['callback'])) {
                self::$middlewareList[self::$routeLists[$this->method][$this->path]['callback']][] = $middleware;
            }
            if ($isClass && is_string(self::$routeLists[$this->method][$this->path]['callback'])) {
                $className = substr(self::$routeLists[$this->method][$this->path]['callback'], 0, stripos
                (self::$routeLists[$this->method][$this->path]['callback'], '@'));
                self::$classMiddlewareList[$className][] = $middleware;
            }
        }
        return $this;
    }

    /**
     * 执行
     */
    public
    static function run()
    {
        $routeList = self::$routeLists;
        $compileList = [];

        foreach ($routeList as $key => $routeMethodData) {
            $routeMethodData = array_reverse($routeMethodData);
            $methodData = [];
            foreach ($routeMethodData as $path => $route) {
                $params = [];
                if (preg_match_all('/\{(\w+)\}/', $path, $match)) {
                    foreach ($match[1] as $k => $p) {
                        if (isset($route['pattern'][$p])) {
                            $path = str_replace($match[0][$k], '(' . $route['pattern'][$p] . ')', $path);
                        }
                        $params[$k + 1] = $p;
                    }
                    $path = preg_replace('/\{(\w+)\}/', '(\w+)', $path);
                }
                $methodData[$path] = [
                    'callback' => $route['callback'],
                    'middleware' => $route['middleware'],
                    'params' => $params,
                    'pattern' => $path,
                ];
            }
            $compileList[strtoupper($key)] = $methodData;
        }
        $uri = preg_replace('#(/+)#', '/', '/' . $_SERVER["REQUEST_URI"]);
        $uri = parse_url($uri, PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];
        $callback = false;
        $params = [];
        if (array_key_exists($uri, $compileList[$method])) {
            $callback = $compileList[$method][$uri];
        } elseif (array_key_exists($uri, $compileList['ALL'])) {
            $callback = $compileList['ALL'][$uri];
        } else {
            $routers = array_keys($compileList[$method]);
            foreach ($routers as $routerKey => $router) {
                if (preg_match('#^' . $router . '$#', $uri, $params)) {
                    $callback = $compileList[$method][$router];
                    array_shift($params);
                    break;
                }
            }
            if (!$callback) {
                $routers = array_keys($compileList['ALL']);
                foreach ($routers as $routerKey => $router) {
                    if (preg_match('#^' . $router . '$#', $uri, $params)) {
                        $callback = $compileList['ALL'][$router];
                        array_shift($params);
                        break;
                    }
                }
            }
        }
        if (!$callback) {
            $uri = trim($uri, '/');
            $data = explode('/', $uri);
            $runData = [];
            $runData['nameSpace'] = App::$app->getNameSpace();
            if (count($data) === 1) {
                if ($data[0] === '') {
                    $data[0] = 'Index';
                }
                $runData['controller'] = $data[0];
                $runData['method'] = 'indexAction';
            } else if (count($data) === 2) {
                $runData['controller'] = $data[0];
                $runData['method'] = $data[1] . 'Action';
            } else {
                $method = array_pop($data);
                $runData['controller'] = implode('\\', $data);
                $runData['method'] = strtolower($method) . 'Action';
            }
            $callback = [
                'params' => [],
                'callback' => $runData,
                'middleware' => [],
            ];
        }
        if ($callback['params']) {
            $params = array_combine($callback['params'], $params);
        }
        return self::runCallBack($callback['callback'], $params, $callback['middleware']);
    }

    /**
     * 执行
     * @param string|callable|array $callback
     * @param array $params
     * @param array $middleware
     * @return mixed
     * @throws \ErrorException
     */
    public
    static function runCallBack(string|callable|array $callback, array $params, array $middleware = []): mixed
    {
        self::$router = [
            'callback' => $callback,
            'params' => $params,
            'middleware' => $middleware,
        ];
        if (!is_array($callback)) {
            if (is_callable($callback)) {
                if ($params) {
                    $arguments = new Data($params);
                } else {
                    $params = array();
                    $arguments = new Data($params);
                }
                $request = new Request($arguments);
                $response = new Response();
                $result = $callback($request, new Response());
                Logger::info('Router -> ' . $callback);
                Logger::info('RouterOriginal -> ' . json_encode(self::$router));
                if ($middleware) {
                    /**
                     * @var QApi\Http\MiddlewareHandler $middlewareObject
                     */
                    $middlewareObject = null;
                    $middlewareNumber = array_key_last($middleware);
                    foreach ($middleware as $index => $item) {
                        $middlewareObject = new $item;
                        if ($middlewareNumber === $index) {
                            $result = $middlewareObject->handle($request, $response, $callback);
                        } else {
                            $result = $middlewareObject->handle($request, $response, function ($resquest, $response) {
                                return $response;
                            });
                        }
                    }
                } else {
                    $result = $callback($request, $response);
                }
                if ($result instanceof \QApi\Response) {
                    $result->send();
                } else {
                    echo $result;
                }
                return null;
            }
            $callback = str_replace('/', '\\', $callback);
            $segments = explode('@', $callback);
            if (count($segments) !== 2) {
                throw new \ErrorException('routing syntax error，' . $callback . 'unable to resolve!');
            }
            $controllerName = $segments[0];
            $controller = new $controllerName();
            $method = $segments[1];
            if (!is_array($params)) {
                $params = [];
            }
            $arguments = new Data($params);
            $request = new Request($arguments);
            $response = new Response();
            Logger::info('Router -> ' . $controllerName . '@' . $method);
            Logger::info('RouterOriginal -> ' . json_encode(self::$router));
            $result = '';
            if ($middleware) {
                /**
                 * @var QApi\Http\MiddlewareHandler $middlewareObject
                 */
                $middlewareObject = null;

                $middlewareNumber = array_key_last($middleware);
                foreach ($middleware as $index => $item) {
                    $middlewareObject = new $item;
                    if ($middlewareNumber === $index) {
                        $result = $middlewareObject->handle($request, $response, static function (Request $request, Response $response) use ($controller, $method) {
                            return $controller->$method($request, $response);
                        });
                    } else {
                        $result = $middlewareObject->handle($request, $response, function ($request, $response) {
                            return $response;
                        });
                    }
                }
            } else {
                $result = $controller->$method(new Request($arguments), $response);
            }
            if ($result instanceof \QApi\Response) {
                $result->send();
            } else {
                echo $result;
            }
            return null;
        }
        $versions = Config::versions();
        $versions = array_reverse($versions);
        $nowVersion = Config::version();
        foreach ($versions as $version) {
            if ($version->version > $nowVersion->version) {
                continue;
            }
            $controllerName = App::$app->getNameSpace() . '\\' . $version->versionDir . '\\' . ($callback['controller']) . 'Controller';
            if (class_exists($controllerName)) {
                $controller = new $controllerName;
                if (method_exists($controller, $callback['method'])) {
                    $params = [];
                    $classMiddleware = self::$classMiddlewareList[$controllerName] ?? [];
                    $methodMiddleware = self::$middlewareList[$controllerName . '@' . $callback['method']] ?? [];
                    $middlewareLists = array_unique(array_merge($classMiddleware, $methodMiddleware));
                    ksort($middlewareLists);
                    self::$router['middleware'] = &$middlewareLists;
                    Logger::info('Router -> ' . $controllerName . '@' . $callback['method']);
                    Logger::info('RouterOriginal -> ' . json_encode(self::$router));
                    $arguments = new Data($params);
                    $request = new Request($arguments);
                    $response = new Response();
                    if (self::$router['middleware']) {
                        /**
                         * @var QApi\Http\MiddlewareHandler $middlewareObject
                         */
                        $middlewareObject = null;
                        $middlewareNumber = array_key_last(self::$router['middleware']);
                        foreach (self::$router['middleware'] as $index => $item) {
                            $middlewareObject = new $item;
                            if ($middlewareNumber === $index) {
                                $result = $middlewareObject->handle($request, $response, static function (Request $request, Response $response) use (
                                    $controller,
                                    $callback
                                ) {
                                    return $controller->{$callback['method']}($request, $response);
                                });
                            } else {
                                $result = $middlewareObject->handle($request, $response, function ($resquest, $response) {
                                    return $response;
                                });
                            }
                        }

                    } else {
                        $result = $controller->{$callback['method']}($request, $response);
                    }
                    if ($result instanceof \QApi\Response) {
                        return $result->send();
                    }
                    echo $result;
                    return null;
                }

                throw new \RuntimeException($controllerName . '@' . $callback['method'] . ' Not Found!');
            }
            throw new \RuntimeException($controllerName . ' Not Found!');
        }
        throw new \RuntimeException($callback['callback'] . 'Route Not Found!');
    }


}