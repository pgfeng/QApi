<?php

namespace QApi\Command;

use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Options;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler\CallableRequestHandler;
use Amp\Http\Server\Response;
use Amp\Socket\Server;
use Amp\Http\Status;
use Amp\Loop;
use JetBrains\PhpStorm\ArrayShape;
use Psr\Log\NullLogger;
use QApi\App;
use QApi\Command;
use QApi\Config;
use QApi\Config\Application;
use QApi\Data;
use Amp\Http\Server\FormParser;
use function RingCentral\Psr7\parse_query;

class RunAmPHPCommand extends CommandHandler
{

    /**
     * @var string
     */
    public string $name = 'run:amphp';

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

    #[ArrayShape(['runMode' => "string", 'allowOrigin' => "array|string[]", 'allowHeaders' => "array|string[]", 'host' => "string", 'port' => "string", 'app' => "mixed|\QApi\Config\Application"])] public function choseApp(): array
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
        print_r($data);
        return [
            'runMode' => $this->apps[$choseAppKey]->getRunMode(),
            'allowOrigin' => $this->apps[$choseAppKey]->allowOrigin,
            'allowHeaders' => $this->apps[$choseAppKey]->allowHeaders,
            'host' => (string)($data['host'] ?? '0.0.0.0'),
            'port' => (string)$data['port'],
            'app' => $this->apps[$choseAppKey],
        ];
    }

    public function handler(array $argv): mixed
    {
        $appDomain = $this->choseApp();
        @cli_set_process_title('QApiServer-' . $appDomain['port']);
        App::$app = $appDomain['app'];
        Loop::run(function () use ($appDomain, $argv) {
            $option = new Options();
//            $option->withBodySizeLimit(1000*1024*1024);
//            $option->withHeaderSizeLimit(1000*1024*1024);
//            print_r($option);
            $server = new HttpServer([
                Server::listen('0.0.0.0:' . $appDomain['port']),
            ], new CallableRequestHandler(function (Request $request) use ($argv, $appDomain) {
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
                $parser = (yield FormParser\parseForm($request));
                $post = $parser->getValues();
                $files = $parser->getFiles();
                $servers = [];
                $servers['REQUEST_URI'] = $request->getUri()->getPath();
                $servers['DOCUMENT_ROOT'] = Config::command('ServerRunDir');
                $servers['REQUEST_METHOD'] = $request->getMethod();
                $servers['HTTP_HOST'] = $request->getUri()->getHost();
                $servers['SERVER_PORT'] = $request->getUri()->getPort();
                $get = parse_query($request->getUri()->getQuery());
                $cookies = [];
                foreach ($request->getCookies() as $cookie) {
                    $cookies[$cookie->getName()] = $cookie->getValue();
                }
                $req = new \QApi\Request(
                    arguments: new Data($argv),
                    get: $get,
                    post: $post,
                    request: array_merge($get, $post),
                    input: yield $request->getBody()->buffer(),
                    files: [],
                    cookie: $cookies,
                    session: [],
                    server: $servers,
                    header: $realHeaders,
                );
                $res = App::run(request: $req);
                $resHeader = [];
                foreach ($res->getHeaders() as $k => $header) {
                    $resHeader[$k] = implode(',', $header);
                }
                $response = new Response(Status::OK, $resHeader, (string)$res);
                $response->setHeader('Server', 'QApiServer-AmPHP');
                return $response;
            }), new NullLogger);

            $this->command->cli->blue(sprintf('QApi Server Startup On <http://%s:%s/>', $appDomain['host'],
                $appDomain['port']));
            yield $server->start();
            // Stop the server gracefully when SIGINT is received.
            // This is technically optional, but it is best to call Server::stop().
            Loop::onSignal(SIGINT, function (string $watcherId) use ($server) {
                Loop::cancel($watcherId);
                yield $server->stop();
            });
        });
        return '';
    }
}