<?php

namespace QApi\Command;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use JetBrains\PhpStorm\ArrayShape;
use QApi\App;
use QApi\Command;
use QApi\Config;
use QApi\Config\Application;
use QApi\Http\Server;

class RunPhpCommand extends CommandHandler
{

    /**
     * @var string
     */
    public string $name = 'run:php';

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
        $server = new Server($appDomain['port'], Config::command('ServerRunDir'), $appDomain['allowOrigin']);
        $this->command->cli->blue(sprintf('QApi Server Startup On <http://%s:%s/>', $appDomain['host'],
            $appDomain['port']));
        $server->handle(function (\QApi\Request $request) {
            $request->server['DOCUMENT_ROOT'] = Config::command('ServerRunDir');
            return App::run(request: $request, logTime: true)->withHeader('Server', 'QApiServer-PhpSocket');
        });
        return '';
    }
}