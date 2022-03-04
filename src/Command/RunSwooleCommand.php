<?php


namespace QApi\Command;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use QApi\App;
use QApi\Command;
use QApi\Config;
use QApi\Config\Application;
use QApi\Database\DB;
use QApi\Enumeration\RunMode;
use QApi\Logger;
use QApi\Response;
use RuntimeException;
use Swoole\Http\Server;
use Swoole\Table;

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

    public function handler(array $argv): mixed
    {
        $appDomain = $this->choseApp();
        @cli_set_process_title('QApiServer-' . $appDomain['port']);
        $http = new Server("0.0.0.0", $appDomain['port']);
        App::$app = $appDomain['app'];
        $options = [
            'enable_static_handler' => true,
            'document_root' => Config::command('ServerRunDir'),
            'package_max_length' => (int)ini_get('post_max_size') * 1024 * 1024,
            'http_parse_cookie' => true,
            'http_autoindex' => false,
            'pid_file' => 'SwooleServer.pid',
            'http_index_files' => ['index.html', 'index.htm'],
            'daemonize' => in_array('--daemonize', $argv, true),
            'log_date_format' => '%Y-%m-%d %H:%M:%S',
        ];
        $http->set($options);
        $table = new \Swoole\Table(1);
        $table->column('number', Table::TYPE_INT, 4);
        $table->create();
        $http->on("start", function ($server) use ($appDomain) {
            $this->command->cli->blue(sprintf('QApi Server Startup On <http://%s:%s/> Server-PID：%s', $appDomain['host'],
                $appDomain['port'], $server->master_pid . '-' . $server->manager_pid));
        });
        $http->on("request", function ($request, $response) use ($http, $appDomain, $table) {
            print_r($request);
            $table->set('requestNumber', [
                'number' => (int)$table->get('requestNumber', 'number') + 1,
            ]);
            if (in_array('*', $appDomain['allowOrigin'], true)) {
                $response->header('Access-Control-Allow-Origin', $appDomain['allowOrigin']);
            } else if (in_array($request->header['host'], $appDomain, true)) {
                $response->header('Access-Control-Allow-Origin', $request->header['host']);
            }
            $response->header('Access-Control-Allow-Headers', implode(',', $appDomain['allowHeaders']));
            $response->header('x-powered-by', 'QApi');
            $argv = [];
            $request->server['HTTP_HOST'] = $request->header['host'];
            $request->server = array_change_key_case($request->server, CASE_UPPER);
            try {
                /**
                 * @var Application $app
                 */
                $app = &$appDomain['app'];
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
                $req = new \QApi\Request(
                    new \QApi\Data($argv),
                    $request->get, $request->post,
                    array_merge($request->get ?? [], $request->post ?? []),
                    $input, $request->files ?? [], $request->cookie,
                    null,
                    $request->server, $headers);
                /**
                 * @var Response
                 */
                $res = \QApi\App::run(apiPassword: $appDomain['app']->docPassword, request: $req);
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
                    $response->end($res);
                } else {
                    $response->end($res);
                }
            } catch (RuntimeException $e) {
                error_log(get_class($e) . '：' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
                $response->end((new \QApi\Response())->setCode(500)->setMsg($e->getMessage())->setExtra([
                ])->fail());
            }
            $table->set('requestNumber', [
                'number' => (int)$table->get('requestNumber', 'number') - 1,
            ]);
            if ($appDomain['runMode'] === RunMode::DEVELOPMENT) {
                while (true){
                    $time = explode('.', microtime(true));
                    if ((count($time) === 2) && ($request->fd === (ceil($time[1] / 10)) % 100)) {
                        $http->reload();
                        break;
                    }
                    usleep(100);
                }
            }
        });
        $http->start();
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

    public function help(): mixed
    {
        return null;
    }
}