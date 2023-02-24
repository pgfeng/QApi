<?php


namespace QApi;


use Closure;
use ErrorException;
use Exception;
use JetBrains\PhpStorm\NoReturn;
use JsonException;
use QApi;
use QApi\Attribute\Utils;
use QApi\Cache\Cache;
use QApi\Cache\CacheContainer;
use QApi\Cache\CacheInterface;
use QApi\Cache\FileSystemAdapter;
use QApi\Config\Cache\FileSystem;
use QApi\Exception\CompileErrorException;
use ReflectionClass;
use ReflectionException;

/**
 * Class Router
 * @package QApi
 * @method static Router get(string|null $path = null, Callable|string|null $callback = null, array $pattern = [], QApi\Http\MiddlewareInterface[]|string $middleware = [])
 * @method static Router post(string|null $path = null, Callable|string|null $callback = null, array $pattern = [], QApi\Http\MiddlewareInterface[] $middleware = [])
 * @method static Router put(string|null $path = null, Callable|string|null $callback = null, array $pattern = [], QApi\Http\MiddlewareInterface[] $middleware = [])
 * @method static Router delete(string|null $path = null, Callable|string|null $callback = null, array $pattern = [], QApi\Http\MiddlewareInterface[] $middleware = [])
 * @method static Router options(string|null $path = null, Callable|string|null $callback = null, array $pattern = [], QApi\Http\MiddlewareInterface[] $middleware = [])
 * @method static Router head(string|null $path = null, Callable|string|null $callback = null, array $pattern = [], QApi\Http\MiddlewareInterface[] $middleware = [])
 * @method static Router all(string|null $path = null, Callable|string|null $callback = null, array $pattern = [], QApi\Http\MiddlewareInterface[] $middleware = [])
 */
class Router
{
    public static array $router = [];

    public static array $middlewareList = [];

    public static array $classMiddlewareList = [];

    public static ?QApi\Cache\CacheInterface $cache = null;

    public static array $config = [];

    public static string $URI = '';

    public static string $METHOD = '';

    private static bool|array|null $hitCache = null;

    public static ?Request $request = null;


    /**
     * @var CacheInterface|null
     */
    public static ?CacheInterface $routerBuilderCache = null;

    /**
     * 存储路由
     * 如果当前请求类型不存在会自动向ALL中查找
     * @var array
     */
    public static array $routeLists = [
    ];

    /**
     * 初始化
     * @param Request|null $request
     * @throws ErrorException
     */
    public static function init(Request $request = null): void
    {
        if (!isset(self::$routeLists[Config::$app->getDir()])) {
            self::$routeLists[Config::$app->getDir()] = [
                'GET' => [],
                'POST' => [],
                'PUT' => [],
                'DELETE' => [],
                'OPTIONS' => [],
                'HEAD' => [],
                'ALL' => [],];
        }
        if (!$request) {
            $arguments = [];
            self::$request = $request = new Request(new Data($arguments));
        } else {
            self::$request = $request;
        }

        Logger::router($request->method . ' -> ' . $request->domain() . $request->requestUri);
//            Logger::info("↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓  Request Data ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ");
        Logger::request('Headers -> ' . $request->header);
        if ($request->input) {
            Logger::request('InputData -> ' . $request->input);
        }
//            Logger::info(' RequestMethod' . ' -> ' . $this->method);
//            Logger::info(' HeaderData -> ' . $this->header);
//            if ($this->method === MethodsEnum::METHOD_POST) {
//                Logger::info(' PostData -> ' . $this->post);
//                if ($this->file->count()) {
//                    Logger::info(' FileData -> ' . $this->file);
//                }
//                if ($this->input) {
//                    Logger::info(' InputData -> ' . $this->input);
//                }
//            }
//            Logger::info("↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑  Request Data  ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ");
        self::$config = Config::route();
        if (self::$config['cache']) {
            self::$cache = new (self::$config['cacheDriver']->driver)(self::$config['cacheDriver']);
        }
        self::get(path: '/__status.json', callback: function (Request $request, Response $response) {
            if (!App::$apiPassword) {
                return $response->setMsg('Status success！')->ok();
            }
            $password = md5(trim($request->get->get('password')));
            if ($password === md5(md5(App::$apiPassword))) {
                return $response->setMsg('Status success！');
            }
            return $response->setMsg('Abnormal state!')->fail();
        });
        self::get(path: '/__apis.json', callback: function (Request $request, Response $response) {
            if (App::$apiPassword) {
                $password = md5(trim($request->get->get('token','')));
                if ($password !== md5(md5(App::$apiPassword))) {
                    return $response->setMsg('The current request requires login！')->setCode(403)->fail();
                }
            }
            $cache = Cache::initialization('__document');
            $v = '';
            if ($request->get->get('v')){
                $v = '@'.$request->get->get('v');
            }
            return $response->setData($cache->get('__apiDocument'.$v))->setMsg('Successfully obtained interface document!');
        });
        self::get(path: '/__apiResponse.json', callback: function (Request $request, Response $response) {
            if (App::$apiPassword) {
                $password = md5(trim($request->get->get('token','')));
                if ($password !== md5(md5(App::$apiPassword))) {
                    return $response->setMsg('The current request requires login！')->setCode(403)->fail();
                }
            }
            $cache = Cache::initialization('__document');
            return $response->setData($cache->get($request->get->get('type') . '/' . $request->get->get('path')))
                ->setMsg('Get interface return example succeeded!');
        });
        self::post(path: '/__apiResponse.json', callback: function (Request $request, Response $response) {
            if (App::$apiPassword) {
                $password = md5(trim($request->get->get('token','')));
                if ($password !== md5(md5(App::$apiPassword))) {
                    return $response->setMsg('The current request requires login！')->setCode(403)->fail();
                }
            }
            if (Config::app()->getRunMode() !== QApi\Enumeration\RunMode::DEVELOPMENT) {
                return $response->setMsg('Please save the instance in the development environment!')->fail();
            }
            $cache = Cache::initialization('__document');
            return $response->setData($cache->set($request->get->get('type') . '/' . $request->get->get('path'),
                $request->post->get('response')
            ))
                ->setMsg('Saving interface document example succeeded!');
        });
        self::get(path: '/__apiResponses.json', callback: function (Request $request, Response $response) {
            if (App::$apiPassword) {
                $password = md5(trim($request->get->get('token','')));
                if ($password !== md5(md5(App::$apiPassword))) {
                    return $response->setMsg('The current request requires login！')->setCode(403)->fail();
                }
            }
            $cache = Cache::initialization('__document');
            $default = $cache->get($request->get->get('type') . '/' . $request->get->get('path'));
            $data = [];
            if ($default) {
                $data['SUCCESS'] = $default;
            }
            unset($default);
            $tags = $cache->get($request->get->get('type') . '/' . $request->get->get('path') . '#___TAGS');
            if (!$tags) {
                $tags = [];
            }
            foreach ($tags as $tag) {
                $data[$tag] = $cache->get($request->get->get('type') . '/' . $request->get->get('path') . '#---' . $tag);
            }
            return $response->setData($data)
                ->setMsg('Get interface return example succeeded!');
        });
        self::post(path: '/__apiResponses.json', callback: function (Request $request, Response $response) {
            if (App::$apiPassword) {
                $password = md5(trim($request->get->get('token','')));
                if ($password !== md5(md5(App::$apiPassword))) {
                    return $response->setMsg('The current request requires login！')->setCode(403)->fail();
                }
            }
            if (Config::app()->getRunMode() !== QApi\Enumeration\RunMode::DEVELOPMENT) {
                return $response->setMsg('Please save the instance in the development environment!')->fail();
            }
            $tagName = trim($request->post->get('tagName'));
            if (!$tagName) {
                return $response->fail('The [tagName] field must be passed in!');
            }
            $cache = Cache::initialization('__document');
            $tags = $cache->get($request->get->get('type') . '/' . $request->get->get('path') . '#___TAGS');
            if (!$tags) {
                $tags = [];
            }
            if (!in_array($tagName, $tags)) {
                $tags[] = $tagName;
            }
            $cache->set($request->get->get('type') . '/' . $request->get->get('path') . '#___TAGS', $tags);
            $cache->set($request->get->get('type') . '/' . $request->get->get('path'), $request->post->get('response'));
            return $response->setData(
                $cache->set($request->get->get('type') . '/' . $request->get->get('path') . '#---' . $tagName, $request->post->get('response'))
            )
                ->setMsg('Saving interface document example succeeded!');
        });
        self::delete(path: '/__apiResponses.json', callback: function (Request $request, Response $response) {
            if (App::$apiPassword) {
                $password = md5(trim($request->get->get('token','')));
                if ($password !== md5(md5(App::$apiPassword))) {
                    return $response->setMsg('The current request requires login！')->setCode(403)->fail();
                }
            }
            if (Config::app()->getRunMode() !== QApi\Enumeration\RunMode::DEVELOPMENT) {
                return $response->setMsg('Please delete the instance in the development environment!')->fail();
            }
            $tagName = trim($request->get->get('tagName'));
            if (!$tagName) {
                return $response->fail('The [tagName] field must be passed in!');
            }
            $cache = Cache::initialization('__document');
            $tags = $cache->get($request->get->get('type') . '/' . $request->get->get('path') . '#___TAGS');
            if (!$tags) {
                $tags = [];
            }
            $index = array_search($tagName, $tags);
            if ($index !== false) {
                array_splice($tags, $index, 1);
            }
            $cache->set($request->get->get('type') . '/' . $request->get->get('path') . '#___TAGS', $tags);
            try {
                $cache->delete($request->get->get('type') . '/' . $request->get->get('path') . '#---' . $tagName);
            } catch (Exception) {
            }
            return $response->setData(true)
                ->setMsg('Deleting interface document example succeeded!');
        });
        self::post(path: '/__apis.json', callback: function (Request $request, Response $response) {
            if (App::$apiPassword) {
                $password = md5(trim($request->get->get('token','')));
                if ($password !== md5(md5(App::$apiPassword))) {
                    return $response->setMsg('The current request requires login！')->setCode(403)->fail();
                }
            }
            if (Config::app()->getRunMode() !== QApi\Enumeration\RunMode::DEVELOPMENT) {
                return $response->setMsg('Please refresh the API document in the development environment!')->fail();
            }
            $v = '';
            if ($request->get->get('v')){
                $v = '@'.$request->get->get('v');
            }
            Utils::rebuild(version: $v);
            $cache = Cache::initialization('__document');
            return $response->setData($cache->get('__apiDocument'.$v))->setMsg('Successfully obtained interface document!');
        });
        if (Config::app()->getRunMode() === QApi\Enumeration\RunMode::DEVELOPMENT) {
            self::BuildRoute(Config::$app->getNameSpace());
        }
        $uri = preg_replace('#(/+)#', '/', '/' . $request->server['REQUEST_URI']);
        $uri = parse_url($uri, PHP_URL_PATH);
        if (!$uri) {
            $uri = '/';
        }
        self::$URI = $uri;
        self::$METHOD = $_SERVER['REQUEST_METHOD'];
        if (self::$cache !== null) {
            self::$hitCache = self::$cache->get(':' . Config::app()->getNameSpace() . ':' . Config::app()
                    ->getDefaultVersion() . ':' .
                self::$METHOD . ':' . self::$URI);
        }
        if (!self::$hitCache && (!isset(self::$router[Config::$app->getDir()])) || empty(self::$router[Config::$app->getDir()])) {
            $versions = Config::versions();
            foreach ($versions as $version) {
                $base_path = PROJECT_PATH . App::$routeDir . DIRECTORY_SEPARATOR . Config::$app->getDir() . DIRECTORY_SEPARATOR
                    . str_replace('.', '', $version->versionName) . DIRECTORY_SEPARATOR;
                mkPathDir($base_path . 'builder.php');
                $data = glob($base_path . '*.php');
                foreach ($data as $file) {
                    include $file;
                }
                unset($base_path);
                if ($version->versionName === App::getVersion()) {
                    break;
                }
            }
        }
    }

    /**
     * @param string $nameSpace
     */
    #[NoReturn] public static function BuildRoute(string $nameSpace): void
    {
        if (!self::$routerBuilderCache) {
            self::$routerBuilderCache = new CacheContainer(new FileSystemAdapter(new FileSystem(PROJECT_PATH . \QApi\App::$runtimeDir . DIRECTORY_SEPARATOR . '.routerBuilder')), 'RouterBuilder',disableLog: true);
        }
        if (self::$routerBuilderCache->get('@buildTime', 0) >= time() - 3) {
            return;
        }
        self::$routerBuilderCache->set('@buildTime', time());
        $lockFile = PROJECT_PATH . App::$routeDir . DIRECTORY_SEPARATOR . Config::$app->getDir() .
            DIRECTORY_SEPARATOR
            . str_replace('.', '', App::getVersion()) . DIRECTORY_SEPARATOR . 'runBuildRoute.lock';
        if (file_exists($lockFile)) {
            return;
        }
        mkPathDir($lockFile);
        touch($lockFile);
        $version_path = str_replace('.', '', App::getVersion());
        $base_path = PROJECT_PATH . Config::$app->getDir() . DIRECTORY_SEPARATOR . $version_path .
            DIRECTORY_SEPARATOR;
        mkPathDir($base_path . 'builder.php');
        $nameSpace .= '\\' . $version_path;
        try {
            $data = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'Route/buildTemplate.php');
            $new_data = '';
            self::build(scandir($base_path), $base_path, $nameSpace, $base_path, $new_data);
            $save_path = PROJECT_PATH . App::$routeDir . DIRECTORY_SEPARATOR . Config::$app->getDir() . DIRECTORY_SEPARATOR
                . Config::version()->versionDir . DIRECTORY_SEPARATOR . 'builder.php';
            @file_put_contents($save_path, $data.$new_data);
        } catch (ReflectionException $e) {
            $message = $e->getMessage();
            $file = $e->getFile();
            $line = $e->getLine();
            $errorType = get_class($e);
            error_log("\x1b[31;1m {$errorType}：" . $message . "\e[0m\n\t\t" . " in " . $file . ' on line ' .
                $line, 0);
        }
        try {
            unlink($lockFile);
        } catch (Exception $e) {
        }
    }

    /**
     * 解析注解并且声称
     * @param $san_files
     * @param string $parent_path
     * @param string $nameSpace
     * @param string $base_path
     * @throws ReflectionException
     */
    public static function build($san_files, string $parent_path, string $nameSpace, string $base_path, &$data): string
    {
        foreach ($san_files as $path) {
            if ($path !== '.' && $path !== '..') {
                if (is_dir($parent_path . $path)) {
                    self::build(scandir($parent_path . $path . DIRECTORY_SEPARATOR), $parent_path . $path . DIRECTORY_SEPARATOR,
                        $nameSpace, $base_path, $data);
                } else if (preg_match('#(.+)Controller.php#', $path, $match)) {
                    $path_class = $nameSpace . '\\' . str_replace('/', '\\', str_replace($base_path, '',
                            $parent_path)) . $match[1] . 'Controller';
                    $refClass = new ReflectionClass(new $path_class);
                    $methods = $refClass->getMethods();
                    foreach ($methods as $method) {
                        if (str_ends_with($method->getName(), 'Action')) {
                            $methodAttributes = $method->getAttributes(QApi\Attribute\Route::class);
                            if (!$methodAttributes) {
                                $classAttributes = $refClass->getAttributes(QApi\Attribute\Route::class);
                                if ($classAttributes) {
                                    foreach ($classAttributes as $item) {
                                        $data .= $item->newInstance()->builder($refClass, $path_class, $method->getName(), false, $parent_path . $path);
                                    }
                                }
                            } else {
                                foreach ($methodAttributes as $key => $item) {
                                    $data .= $item->newInstance()->builder($refClass, $path_class, $method->getName(), true, $parent_path . $path);
                                }
                            }
                        }
                    }
                }
            }
        }
        return $data;
    }

    /**
     * Router constructor.
     * @param string $method
     * @param string|null $path
     * @param  $runData
     * @param array $pattern
     * @param QApi\Http\MiddlewareInterface[]|string $middleware
     */
    public
    function __construct(protected string $app, protected string $method, protected string|null $path, protected $runData, array $pattern = [],
                         array|string     $middleware = [])
    {
        if (is_string($middleware)) {
            $middleware = [$middleware];
        }
        self::$routeLists[Config::$app->getDir()][$method][$path] = [
            'callback' => $runData,
            'pattern' => $pattern,
            'middleware' => $middleware,
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

        if (!isset($params['callback'])) {
            $params = [
                'path' => $params[0],
                'callback' => $params[1],
                'pattern' => $params[2] ?? [],
                'middleware' => $params[3] ?? [],
            ];
        }
        if ((!isset($params['path']) || !$params['path']) && isset($params['callback']) && !is_callable($params['callback'])) {
            $params['path'] = str_replace(array(Config::$app->getNameSpace() . '\\' . Config::version()->versionDir, '\\'), array('', '/'), $params['callback']);
            $params['path'] = substr(str_replace('Controller@', '/', $params['path']), 0, -6);
        }
        return new static(Config::$app->getDir(), strtoupper($method), $params['path'] ?? null, $params['callback'], $params['pattern'] ?? [],
            $params['middleware'] ?? []);
    }

    /**
     * @param string $paramName
     * @param string $pattern
     * @return Router
     */
    public
    function paramPattern(string $paramName, string $pattern): self
    {
        self::$routeLists[Config::$app->getDir()][$this->method][$this->path]['pattern'][$paramName] = $pattern;
        return $this;
    }

    /**
     * @param string $middleware
     * @param bool $isClass
     * @return $this
     */
    public function addMiddleware(string $middleware, bool $isClass = false): self
    {
        if (!in_array($middleware, self::$routeLists[Config::$app->getDir()][$this->method][$this->path]['middleware'], true)) {
            self::$routeLists[Config::$app->getDir()][$this->method][$this->path]['middleware'][] = $middleware;
            if (is_string(self::$routeLists[Config::$app->getDir()][$this->method][$this->path]['callback'])) {
                self::$middlewareList[Config::$app->getDir()][self::$routeLists[Config::$app->getDir()][$this->method][$this->path]['callback']][] = $middleware;
            }
            if ($isClass && is_string(self::$routeLists[Config::$app->getDir()][$this->method][$this->path]['callback'])) {
                $className = substr(self::$routeLists[Config::$app->getDir()][$this->method][$this->path]['callback'], 0, strpos
                (self::$routeLists[Config::$app->getDir()][$this->method][$this->path]['callback'], '@'));
                self::$classMiddlewareList[Config::$app->getDir()][$className][] = $middleware;
            }
        }
        return $this;
    }


    /**
     * @return mixed
     * @throws ErrorException
     * @throws JsonException
     */
    public
    static function run()
    {
        $routeList = self::$routeLists;
        $compileList = [];
        $uri = self::$URI;
        $method = self::$METHOD;
        if (!self::$hitCache) {
            foreach ($routeList[Config::$app->getDir()] as $key => $routeMethodData) {
                $routeMethodData = array_reverse($routeMethodData);
                $methodData = [];
                foreach ($routeMethodData as $path => $route) {
                    $params = [];
                    if (preg_match_all('/{([a-zA-Z_][a-zA-Z0-9_]+)}/', $path, $match)) {
                        foreach ($match[1] as $k => $p) {
                            if (isset($route['pattern'][$p])) {
                                $path = str_replace($match[0][$k], '(' . $route['pattern'][$p] . ')', $path);
                            }
                            $params[$k + 1] = $p;
                        }
                        $path = preg_replace('/{([a-zA-Z_][a-zA-Z0-9_]+)}/', '(\w+)', $path);
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
            $callback = false;
            $params = [];
            if (array_key_exists($uri, $compileList[$method])) {
                $callback = $compileList[$method][$uri];
            } elseif (array_key_exists($uri, $compileList['ALL'])) {
                $callback = $compileList['ALL'][$uri];
            } else {
                $routers = array_keys($compileList[$method]);
                foreach ($routers as $routerKey => $router) {
                    if ($uri === $router || preg_match('#^' . $router . '$#', $uri, $params)) {
                        $callback = $compileList[$method][$router];
                        array_shift($params);
                        break;
                    }
                }
                if (!$callback) {
                    $routers = array_keys($compileList['ALL']);
                    foreach ($routers as $routerKey => $router) {
                        if ($uri === $router || preg_match('#^' . $router . '$#', $uri, $params)) {
                            $callback = $compileList['ALL'][$router];
                            array_shift($params);
                            break;
                        }
                    }
                }
            }
            if (!$callback) {
                if (Config::route('auto', true)) {
                    $uri = trim($uri, '/');
                    $data = explode('/', $uri);
                    $runData = [];
                    $runData['nameSpace'] = App::$app->getNameSpace();
                    if (count($data) === 1) {
                        if ($data[0] === '') {
                            $data[0] = Config::route('defaultController');
                        }
                        $runData['controller'] = $data[0];
                        $runData['method'] = Config::route('defaultAction') . 'Action';
                    } else if (count($data) === 2) {
                        $runData['controller'] = $data[0];
                        $runData['method'] = $data[1] . 'Action';
                    } else {
                        $method = array_pop($data);
                        $runData['controller'] = implode('\\', $data);
                        $runData['method'] = $method . 'Action';
                    }
                    $callback = [
                        'params' => [],
                        'callback' => $runData,
                        'middleware' => [],
                    ];
                } else {
                    return (new Response())->setCode(404)->setMsg('404 Not Found');
                }
            }

            if ($callback['params']) {
                $params = array_combine($callback['params'], $params);
            }
            $data = [
                'callback' => $callback,
                'params' => $params,
            ];
            if (!self::$cache?->has(':' . Config::app()->getNameSpace() . ':' . Config::app()->getDefaultVersion() . '&__middleware__&')) {
                self::$cache?->set(':' . Config::app()->getNameSpace() . ':' . Config::app()->getDefaultVersion() . '&__middleware__&', [
                    'classMiddlewareList' => self::$classMiddlewareList[Config::$app->getDir()] ?? [],
                    'middlewareList' => self::$middlewareList[Config::$app->getDir()] ?? [],
                ]);
            }
            if (!is_callable($data['callback']['callback'])) {
                self::$cache?->set(':' . Config::app()->getNameSpace() . ':' . Config::app()->getDefaultVersion() . ':' . self::$METHOD . ':' . self::$URI,
                    $data,
                    self::$config['cacheTTL']);
            } else if (self::$config['cacheClosure']) {
                $data['callback']['callback'] = new QApi\serializeClosure\Closure($data['callback']['callback']);
                self::$cache?->set(':' . Config::app()->getNameSpace() . ':' . Config::app()->getDefaultVersion() . ':' . self::$METHOD . ':' . self::$URI, $data, self::$config['cacheTTL']);
            }
        } else {
            $middleware = self::$cache->get(':' . Config::app()->getNameSpace() . ':' . Config::app()
                    ->getDefaultVersion() . '&__middleware__&');
            self::$classMiddlewareList[Config::$app->getDir()] = $middleware['classMiddlewareList'];
            self::$middlewareList[Config::$app->getDir()] = $middleware['middlewareList'];
            $callback = self::$hitCache['callback'];
            $params = self::$hitCache['params'];
        }
        return self::runCallBack($callback['callback'], $params, $callback['middleware']);
    }

    /**
     * 执行
     * @param string|callable|array $callback
     * @param array $params
     * @param array $middleware
     * @return mixed
     * @throws ErrorException|JsonException
     */
    public static function runCallBack(string|callable|array|QApi\serializeClosure\Closure $callback, array $params, array $middleware = []): mixed
    {
        if ($callback instanceof QApi\serializeClosure\Closure) {
            $callback = $callback->getClosure();
        }
        self::$router[Config::$app->getDir()] = [
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
                self::$request->arguments = $arguments;
                $response = new Response();
                Logger::router('Running ' . '-> ' . str_replace('{"callback":{}', '{"callback":Closure', json_encode(self::$router[Config::$app->getDir()], JSON_THROW_ON_ERROR)));
                $result = null;
                if ($middleware) {
                    /**
                     * @var QApi\Http\MiddlewareInterface $middlewareObject
                     */
                    $middlewareObject = null;
                    foreach ($middleware as $item) {
                        $middlewareObject = new $item;
                        $result = $middlewareObject->handle(self::$request, $response, static function (Request  $request,
                                                                                                        Response $response)
                        use ($callback) {
                            return $callback($request, $response);
                        });
                        if ($result instanceof Response) {
                            break;
                        }
                    }
                } else {
                    $result = $callback(self::$request, $response);
                }
                if ($result instanceof Response) {
                    return $result;
                }
                if ($result instanceof Closure) {
                    return $result(self::$request, $response);
                }

                return $result;
            }
            $callback = str_replace('/', '\\', $callback);
            $segments = explode('@', $callback);
            if (count($segments) !== 2) {
                throw new CompileErrorException('routing syntax error，' . $callback . 'unable to resolve!');
            }
            $controllerName = $segments[0];
            $controller = new $controllerName();
            $method = $segments[1];
            if (!is_array($params)) {
                $params = [];
            }
            $arguments = new Data($params);
            self::$request->arguments = $arguments;
            $response = new Response();
            Logger::router('Running -> ' . json_encode(self::$router[Config::$app->getDir()], JSON_THROW_ON_ERROR));
            $result = '';
            if ($middleware) {
                /**
                 * @var QApi\Http\MiddlewareInterface $middlewareObject
                 */
                $middlewareObject = null;
                foreach ($middleware as $item) {
                    $middlewareObject = new $item;
                    $result = $middlewareObject->handle(self::$request, $response, static function (Request $request, Response $response) use ($controller, $method) {
                        return $controller->$method($request, $response);
                    });
                    if ($result instanceof Response) {
                        break;
                    }
                }
            } else {
                self::$request->arguments = $arguments;
                $result = $controller->$method(self::$request, $response);
            }
            if ($result instanceof Response) {
                return $result;
            }
            if ($result instanceof Closure) {
                return $result(self::$request, $response);
            }

            return $result;
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
                    $classMiddleware = self::$classMiddlewareList[Config::$app->getDir()][$controllerName] ?? [];
                    $methodMiddleware = self::$middlewareList[Config::$app->getDir()][$controllerName . '@' . $callback['method']] ?? [];
                    $middlewareLists = array_unique(array_merge($classMiddleware, $methodMiddleware));
                    ksort($middlewareLists);
                    self::$router[Config::$app->getDir()]['middleware'] = &$middlewareLists;
                    Logger::router('Running -> ' . json_encode(self::$router[Config::$app->getDir()], JSON_THROW_ON_ERROR));
                    self::$request->arguments = new Data($params);
                    $response = new Response();
                    if (self::$router[Config::$app->getDir()]['middleware']) {
                        /**
                         * @var QApi\Http\MiddlewareInterface $middlewareObject
                         */
                        $middlewareObject = null;
                        foreach (self::$router[Config::$app->getDir()]['middleware'] as $item) {
                            $middlewareObject = new $item;
                            $result = $middlewareObject->handle(self::$request, $response, static function (Request  $request,
                                                                                                            Response $response) use ($controller, $callback) {
                                return $controller->{$callback['method']}($request, $response);
                            });
                            if ($result instanceof Response) {
                                break;
                            }
                        }

                    } else {
                        $result = $controller->{$callback['method']}(self::$request, $response);
                    }
                    if (isset($result)) {
                        if ($result instanceof Response) {
                            return $result;
                        }
                        if ($result instanceof Closure) {
                            return $result(self::$request, $response);
                        }
                        return $result;
                    }
                }
            }
        }
        return (new Response())->setCode(404)->setMsg($callback['nameSpace'] . '\\' . $nowVersion->versionDir . '\\' . $callback['controller']
            . 'Controller@'
            . $callback['method']
            . ' Not Found.');
    }
}