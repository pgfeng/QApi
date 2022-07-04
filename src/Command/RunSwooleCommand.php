<?php


namespace QApi\Command;

use ErrorException;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use QApi\App;
use QApi\Cache\Cache;
use QApi\Cache\CacheInterface;
use QApi\Cache\FileSystemAdapter;
use QApi\Cache\SwooleTableAdapter;
use QApi\Command;
use QApi\Config;
use QApi\Config\Application;
use QApi\Config\Cache\FileSystem;
use QApi\Data;
use QApi\Enumeration\RunMode;
use QApi\Logger;
use QApi\Request;
use QApi\Response;
use QApi\Router;
use RuntimeException;
use Swoole\Http\Server;
use Swoole\Timer;

class RunSwooleCommand extends CommandHandler
{
    /**
     * @var string
     */
    public string $name = 'run:swoole';


    /**
     * @var Application[]
     */
    private array $apps;

    public function __construct(Command $command, $argv = [])
    {
        parent::__construct($command, $argv);
        $this->apps = Config::apps();
    }

    /**
     * 热重启服务
     * @param $request
     * @param $cache
     * @param $http
     * @param $appDomain
     */
    function reload($request, $cache, $http, $appDomain)
    {
        if ($appDomain['runMode'] == RunMode::DEVELOPMENT) {
            $cache->set('runNumber', $cache->get('runNumber') - 1);
            if ($cache->get('runNumber') <= 0) {
                $cache->set('runNumber', 0);
                Timer::after(1, function () use ($request, $cache, $http, $appDomain) {
                    $time = explode('.', microtime(true));
                    if (count($time) === 2 && $cache->get('reloadTime', 0) < time() - 5 && ($request->fd === (ceil
                            ($time[1])) % 100)) {
                        $cache->set('reloadTime', time());
                        $http->reload();
                    } else {
                        $this->reload($request, $cache, $http, $appDomain);
                    }
                });
            }
        }
    }

    /**
     * @param $http_host
     * @return Application|null
     */
    public function getApp($http_host): Application|null
    {
        $appConfig = Config::apps();
        $appConfig = array_reverse($appConfig);
        $appHosts = array_keys($appConfig);
        $appHostPattern = str_replace('*', '(.+)', $appHosts);
        foreach ($appHosts as $key => $host) {
            if (preg_match('/^' . $appHostPattern[$key] . '$/i', $http_host)) {
                return App::$app = &$appConfig[$host];
            }
        }
        return null;
    }

    public function handler(array $argv): mixed
    {
        $appDomain = $this->choseApp();
        $lockFile = PROJECT_PATH . 'SwooleServer-' . $appDomain['port'] . '.lock';
        if (file_exists($lockFile)) {
            $options = ['reload', 'halt'];
            $input = $this->command->cli->blue()->radio('Server is Running,Please select an action:', $options);
            $response = $input->prompt();
            $this->command->info('Start stopping the server...');
            \swoole_process::kill((int)file_get_contents($lockFile));
            \swoole_process::wait();
            usleep(20000);
            $this->command->info('Service stopped successfully！');
            unlink($lockFile);
            if ($response == 'halt') {
                exit;
            } else {
                $this->command->info('Start starting the server!');
            }
        }
        @cli_set_process_title('QApiServer-' . $appDomain['port']);

        $http = new Server("0.0.0.0", $appDomain['port']);

        $options = [
            'enable_static_handler' => true,
            'document_root' => Config::command('ServerRunDir'),
            'package_max_length' => (int)ini_get('post_max_size') * 1024 * 1024,
            'http_parse_cookie' => true,
            'http_autoindex' => false,
            'http_index_files' => ['index.html', 'index.htm'],
            'daemonize' => in_array('--daemonize', $argv, true),
            'log_date_format' => '%Y-%m-%d %H:%M:%S',
        ];
        $http->set($options);
        $http->on("start", function ($server) use ($appDomain, $lockFile, $argv) {
            $this->command->cli->blue(sprintf('QApi Server Startup On <http://%s:%s/> Server-PID：%s', $appDomain['host'],
                $appDomain['port'], $server->master_pid . '-' . $server->manager_pid));
            if (in_array('--daemonize', $argv, true)) {
                file_put_contents($lockFile, $server->master_pid);
            }
        });
        $cache = new SwooleTableAdapter(new Config\Cache\SwooleTable(2, 11));
        $http->on("request", function ($request, $response) use ($http, $appDomain, $cache) {
            $request->server['DOCUMENT_ROOT'] = Config::command('ServerRunDir');
            App::$app = Config::$app = $app = $this->getApp($request->header['host']);
            try {
                /**
                 * @var Application $app
                 */
                $argv = [];
                $request->server['HTTP_HOST'] = $request->header['host'];
                $request->server = array_change_key_case($request->server, CASE_UPPER);
                if (!$app) {
                    $configPath = PROJECT_PATH . App::$configDir . DIRECTORY_SEPARATOR . 'app.php';
                    $response->header('Server', 'QApiServer');
                    $response->status(404);
                    $response->header('Content-Type', 'application/json;charset=utf-8');
                    $response->end(json_encode([
                        'status' => false,
                        'code' => 404,
                        'msg' => 'host ' . $request->header['host'] . ' not bind app!'
                    ]));
                    throw new ErrorException('host ' . $request->header['host'] . ' not bind app!', 0, 1,
                        $configPath);
                } else {
                    if ($app->isDev()) {
                        $time = explode('.', microtime(true));
                        while (count($time) === 2 && ($request->fd !== (ceil($time[1])
                                ) %
                                100)) {
                            $time = explode('.', microtime(true));
                            usleep(1);
                        }
                        $cache->set('runNumber', $cache->get('runNumber', 0) + 1);
                    }
                    $defaultHandle = new StreamHandler(PROJECT_PATH . DIRECTORY_SEPARATOR . App::$runtimeDir . DIRECTORY_SEPARATOR . 'CliLog' .
                        DIRECTORY_SEPARATOR
                        . date('Y-m-d')
                        . DIRECTORY_SEPARATOR . (date('H') . '-' . ceil(((int)date('i')) / 10)) . '.log',
                        \Monolog\Logger::API,
                        true, null, true);
                    $formatter = new LineFormatter("%datetime% %channel%.%level_name% > %message%\n", '[Y-m-d H:i:s]');
                    $defaultHandle->setFormatter($formatter);
                    $app->logHandler = [
                        $defaultHandle
                    ];
                    Config::$command['logHandler'] = $defaultHandle;
                    Logger::init('QApiServer-' . $appDomain['port'] . '[' . $this->pid . ']', true);
                    $input = $request->rawContent();
                    $headers = [];
                    foreach ($request->header as $name => $header) {
                        $name = explode('-', $name);
                        foreach ($name as &$n) {
                            $n = ucfirst($n);
                        }
                        $name = implode('-', $name);
                        $headers[$name] = $header;
                    }
                    $req = new Request(
                        new Data($argv),
                        $request->get, $request->post,
                        array_merge($request->get ?? [], $request->post ?? []),
                        $input, $request->files ?? [], $request->cookie,
                        null,
                        $request->server, $headers);
                    /**
                     * @var Response
                     */
                    $res = \QApi\App::run(apiPassword: $appDomain['app']->docPassword, request: $req);

                }
            } catch (\Error $e) {
                App::clearDevBuildRouteLock();
                $res = (new Response())->setCode(500)->withAddedHeader('Access-Control-Allow-Headers', '_QApi')
                    ->withHeader('X-Powered-By', 'QApi')->withHeader('Content-Type', 'application/json;charset=utf-8')
                    ->setExtra([
                        'msg' => get_class($e) . '：' . $e->getMessage(),
                        'error_msg' => get_class($e) . '：' . $e->getMessage() . ' in ' . $e->getFile()
                            . ' on line ' .
                            $e->getLine(),
                    ]);
            }
            $response->header('Server', 'QApiServer');
            $response->status($res->getStatusCode(), $res->getReason());
            if ($res instanceof Response) {
                $headers = $res->getHeaders();
                foreach ($headers as $name => $header) {
                    if (strtoupper($name) === 'LOCATION') {
                        $response->redirect($header, 301);
                    }
                    if (is_array($header)) {
                        $response->header($name, implode(',', $header));
                    } else {
                        $response->header($name, $header);
                    }
                }
            }
            $response->end($res);
            $this->reload($request, $cache, $http, $appDomain);

        });
        $http->on('Close', function ($server, $fd) use ($cache) {
            $cache->set('runNumber', $cache->get('runNumber') - 1);
            $server->close($fd, true);
        });
        $http->start();
        $cache->set('reloadTime', time());
        return null;
    }

    /**
     * @return array
     */
    public function choseApp(): array
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
        $choseAppKey = $input->prompt();
        $data = parse_url($choseAppKey);
        $data['runMode'] = $this->apps[$choseAppKey]->getRunMode();
        $data['allowOrigin'] = $this->apps[$choseAppKey]->allowOrigin;
        $data['allowHeaders'] = $this->apps[$choseAppKey]->allowHeaders;
        $data['host'] = (string)($data['host'] ?? '0.0.0.0');
        $data['port'] = (int)($data['port'] ?? 80);
        $data['app'] = $this->apps[$choseAppKey];
        return $data;
    }

    public function help(): void
    {
    }
}