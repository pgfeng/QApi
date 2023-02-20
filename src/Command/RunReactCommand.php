<?php


namespace QApi\Command;

use Psr\Http\Message\ServerRequestInterface;
use QApi\App;
use QApi\Command;
use QApi\Config;
use QApi\Config\Application;
use QApi\Data;
use QApi\Enumeration\RunMode;
use QApi\Request;
use React\Http\HttpServer;
use React\Http\Io\UploadedFile;
use React\Http\Message\Response;
use React\Http\Middleware\LimitConcurrentRequestsMiddleware;
use React\Http\Middleware\RequestBodyBufferMiddleware;
use React\Http\Middleware\RequestBodyParserMiddleware;
use React\Http\Middleware\StreamingRequestMiddleware;
use React\Socket\SocketServer;
use RingCentral\Psr7\Stream;
use RuntimeException;

//use Swoole\Http\Server;

class RunReactCommand extends CommandHandler
{
    /**
     * @var string
     */
    public string $name = 'run:react';

    /**
     * @var Application[]
     */
    private array $apps;

    public function __construct(Command $command, $argv = [])
    {
        parent::__construct($command, $argv);
        $this->apps = Config::apps();
        $this->showLogger();
    }

    public function handler(array $argv): mixed
    {
        $appDomain = $this->choseApp();
        @cli_set_process_title('QApiServer-' . $appDomain['port']);
        App::$app = $appDomain['app'];
        $http = new HttpServer(
            new StreamingRequestMiddleware(),
            new LimitConcurrentRequestsMiddleware(100), // 100 concurrent buffering handlers
            new RequestBodyBufferMiddleware(80 * 1024 * 1024), // 2 MiB per request
            new RequestBodyParserMiddleware(60 * 1024 * 1024, 10),
            function (ServerRequestInterface $request) use ($appDomain, $argv) {
                $headers = [];
                foreach ($request->getHeaders() as $k => $header) {
                    $headers[$k] = implode(',', $header);
                }
                $realHeaders = [];
                foreach ($headers as $name => $header) {
                    $name = explode('-', $name);
                    foreach ($name as &$n) {
                        $n = ucfirst($n);
                    }
                    $name = implode('-', $name);
                    $realHeaders[$name] = $header;
                }
                $server = $request->getServerParams();
                $server['REQUEST_URI'] = $request->getUri()->getPath() . '?' . $request->getUri()->getQuery();
                foreach ($headers as $k => $header) {
                    $server['HTTP_' . strtoupper($k)] = $header;
                }
                $server['DOCUMENT_ROOT'] = Config::command('ServerRunDir');
                $server['REQUEST_METHOD'] = $request->getMethod();
                $files = [];
                $uploadFields = $request->getUploadedFiles();
                /**
                 * @var UploadedFile $file
                 */
                $fds = [];
                foreach ($uploadFields as $name => $file) {
                    $fd = tmpfile();
                    fwrite($fd, $file->getStream()->getContents());
                    fseek($fd, 0);
                    $files[$name] = [
                        'name' => $file->getClientFilename(),
                        'type' => $file->getClientMediaType(),
                        'size' => $file->getSize(),
                        'tmp_name' => stream_get_meta_data($fd)['uri'],
                        'error' => $file->getError(),
                    ];
                    $fds[] = $fd;
                }
                $req = new Request(
                    arguments: new Data($argv),
                    get: $request->getQueryParams(),
                    post: $request->getParsedBody(),
                    request: array_merge($request->getQueryParams() ?? [], $request->getAttributes() ?? []),
                    input: $request->getBody()->getContents(),
                    files: $files,
                    cookie: $request->getCookieParams(),
                    session: [],
                    server: $server,
                    header: $realHeaders);
                $res = App::run(request: $req);
                foreach ($fds as $fd) {
                    fclose($fd);
                }
                return new Response(
                    $res->getStatusCode(),
                    ['Server' => "QApiServer/ReactPHPv1", ...$res->getHeaders()],
                    (string)$res,
                    $res->getVersion(),
                    $res->getReason(),
                );
            });
        $socket = new SocketServer("0.0.0.0:{$appDomain['port']}");
        $http->listen($socket);
        $this->command->cli->blue(sprintf('QApi Server Startup On <http://%s:%s/>', $appDomain['host'],
            $appDomain['port']));
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