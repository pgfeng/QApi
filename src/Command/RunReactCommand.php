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
    }

    public function handler(array $argv): mixed
    {
        $appDomain = $this->choseApp();
        @cli_set_process_title('QApiServer-' . $appDomain['port']);
        App::$app = $appDomain['app'];
        $http = new HttpServer(function (ServerRequestInterface $request) use ($appDomain, $argv) {
            $headers = [];
            foreach ($request->getHeaders() as $k => $header) {
                $headers[$k] = implode(',', $header);
            }
            $server = $request->getServerParams();
            $server['REQUEST_URI'] = $request->getUri()->getPath();
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
            foreach ($uploadFields as $name => $file){
                $files[$name] = [
                    'name' => $file->getClientFilename(),
                    'type' => $file->getClientMediaType(),
                    'size' => $file->getSize(),
                    // TODO Stream save tmp
                    'tmp_name' => '',
                    'error' => $file->getError(),
                ];
            }
            $responseHeader = [];
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
                header: $headers);
            if (in_array('*', $appDomain['allowOrigin'], true)) {
                $responseHeader['Access-Control-Allow-Origin'] = $appDomain['allowOrigin'];
            } else if (in_array($headers['host'], $appDomain, true)) {
                $responseHeader['Access-Control-Allow-Origin'] = $headers['host'];
            }
            $responseHeader['Access-Control-Allow-Headers'] = implode(',', $appDomain['allowHeaders']);
            $responseHeader['x-powered-by'] = 'QApi';
            $res = App::run(request: $req);
            return new Response(
                $res->getStatusCode(),
                $responseHeader,
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