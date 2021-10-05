<?php


namespace QApi\Command;


use QApi\App;
use QApi\Cache\Cache;
use QApi\Cache\CacheInterface;
use QApi\Command;
use QApi\Config;
use QApi\Config\Application;
use QApi\Config\Version;
use QApi\Router;

class RouteCacheClearCommand extends CommandHandler
{


    public string $name = 'route:clearCache';
    private string $baseDir;
    /**
     * @var Application[]
     */
    private array $apps;
    private Application $app;
    /**
     * @var Version[]
     */
    private array $versions;
    private CacheInterface $cache;
    private Version $version;

    public function __construct(Command $command, $argv = [])
    {
        parent::__construct($command, $argv);
        $this->baseDir = PROJECT_PATH . App::$routeDir;
        $this->apps = Config::apps();
    }

    public function handler(array $argv): mixed
    {
        $argv1 = $argv;
        $this->choseApp();
        Config::$app = $this->app;
        $this->choseVersion();
        Config::$version = $this->version;
        //        $routeLists = $this->loadRouter();
        $route = Config::route();
        if (!$route['cache']){
            return $this->command->error('Route Cache is not opened!');
        }
        $languages = [
            'Initialize cache......',
            'Ready to clean......',
            'Clean cache......',
            'Clean up completed!',
        ];
        $this->command->info('Start cleaning route cache!');
        usleep(80000);
        $progress = $this->command->cli->blue()->progress()->total(count($languages));
        usleep(80000);
        $progress->current(1, $languages[0]);
        usleep(80000);
        $cache = new ($route['cacheDriver']->driver)($route['cacheDriver']);
        usleep(80000);
        $progress->current(2, $languages[1]);
        usleep(80000);
        $progress->current(3, $languages[2]);
        usleep(80000);
        $cache->clear();
        usleep(80000);
        $progress->current(4, $languages[3]);
//        $this->command->success('Route cache cleanup completed！');
        return null;
    }

    /**
     * @param array $routeLists
     * @param $searchRoute
     */
    private function showTable(array $routeLists, $searchRoute = ''): void
    {
        $table = [];
        foreach ($routeLists as $method => $routes) {
            $routes = array_reverse($routes);
            foreach ($routes as $rule => $route) {
                if (preg_match_all('/\{(\w+)\}/', $rule, $match)) {
                    foreach ($match[1] as $k => $p) {
                        if (isset($route['pattern'][$p])) {
                            $path = str_replace($match[0][$k], '(' . $route['pattern'][$p] . ')', $rule);
                        } else {

                            $path = preg_replace('/\{(\w+)\}/', '(\w+)', $rule);
                        }
                        //                        $params[$k + 1] = $p;
                    }
                    $route['preg_pattern'] = $path;
                } else {
                    $route['preg_pattern'] = $rule;
                }
                $middleware = implode(',', $route['middleware']);
                if (!$searchRoute) {
                    $table[] = [
                        //                        'App' => $this->app->getNameSpace(),
                        'Method' => $method,
                        'Rule' => $rule,
                        //                        'Middleware' => $middleware ? '<' . $middleware . '>' : 'Null',
                        'Route' => is_callable($route['callback']) ? '<Closure>' : $route['callback'],
                        'CodePath' => is_callable($route['callback']) ? '<Closure>' : $this->getCodePathLine($route['callback']),
                    ];
                } else if (preg_match('/' . str_replace('/', '\/', $route['preg_pattern']) . '/', $searchRoute)) {
                    $table[] = [
                        //                        'App' => $this->app->getNameSpace(),
                        'Method' => $method,
                        'Rule' => $rule,
                        //                        'Middleware' => $middleware ? '<' . $middleware . '>' : 'Null',
                        'Route' => is_callable($route['callback']) ? '<Closure>' : $route['callback'],
                        'CodePath' => is_callable($route['callback']) ? '<Closure>' : $this->getCodePathLine($route['callback']),
                    ];
                }
            }
        }
        if (empty($table)) {
            $this->command->cli->error('No matching routing rules were found!');
        } else {
            $this->command->cli->info()->table($table);
        }
    }

    /**
     * @param string $action
     */
    private function getCodePathLine(string $action): string
    {
        $versionAppNameSpace = $this->app->getNameSpace() . '\\' . $this->version->versionDir;
        preg_match('/' . str_replace('\\', '\\\\', $versionAppNameSpace) . '\\\(.+)Controller@(.+)Action/',
            $action, $match);
        $controller = new ReflectionClass($versionAppNameSpace . '\\' . $match[1] . 'Controller');
        $d = $controller->getMethod($match[2] . 'Action');
        return PROJECT_PATH . str_replace('\\', DIRECTORY_SEPARATOR, $match[1]) . 'Controller.php on line ' . $d->getStartLine();
    }

    public function loadRouter(): array
    {
        Router::$routeLists = [
            'GET' => [],
            'POST' => [],
            'PUT' => [],
            'DELETE' => [],
            'OPTIONS' => [],
            'HEAD' => [],
            'ALL' => [],
        ];
        foreach ($this->getIterator() as $item) {
            if ($item->isFile() & $item->getExtension() === 'php') {
                include $item->getPath() . DIRECTORY_SEPARATOR . $item->getFilename();
            }
        }
        $routeLists = Router::$routeLists;
        Router::$routeLists = [
            'GET' => [],
            'POST' => [],
            'PUT' => [],
            'DELETE' => [],
            'OPTIONS' => [],
            'HEAD' => [],
            'ALL' => [],
        ];
        return $routeLists;
    }


    /**
     * @return Iterator
     */
    private function getIterator(): Iterator
    {
        return new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->baseDir . DIRECTORY_SEPARATOR . $this->app->getDir() . DIRECTORY_SEPARATOR
                . str_replace('.', '', $this->version->versionName) . DIRECTORY_SEPARATOR, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
    }

    private function choseVersion(): void
    {
        /**
         * @var Version[]
         */
        $versions = [];
        $this->versions = Config::versions($this->app->getRunMode());
        foreach ($this->versions as $index => $version) {
            $versions['I' . $index] = $version->versionName;
        }
        $input = $this->command->cli->cyan()->radio('Please select an version:', $versions);
        $this->version = $this->versions[substr($input->prompt(), 1)];
    }

    private function choseApp(): void
    {
        $apps = [];
        /**
         * @val
         */
        foreach ($this->apps as $host => $app) {
            $apps[$host] = PROJECT_PATH . $app->getDir() . "\t\t" . $app->getNameSpace() . "\t\t" . $host . '[' .
                $app->getRunMode() . ']';
        }
        $input = $this->command->cli->cyan()->radio('Please select an app:', $apps);
        $this->app = $this->apps[$input->prompt()];
    }

    public function help(): mixed
    {
        return null;
        // TODO: Implement help() method.
    }
}