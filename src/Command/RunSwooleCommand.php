<?php


namespace QApi\Command;

use QApi\Command;
use QApi\Config;
use QApi\Config\Application;
use QApi\Enumeration\RunMode;
use RuntimeException;
use Swoole\Http\Server;

class RunSwooleCommand extends CommandHandler
{
    /**
     * @var string
     */
    public string $name = 'run:swoole';

    /**
     * @var Application[]
     */
    private array $apps = [];

    public function __construct(Command $command, $argv = [])
    {
        parent::__construct($command, $argv);
        $this->apps = Config::apps();
    }

    public function handler(array $argv): mixed
    {
        $appDomain = $this->choseApp();
        $http = new Server("0.0.0.0", $appDomain['port']);

        $http->on("start", function ($server) use ($appDomain) {
            $this->command->cli->blue(sprintf('QApi Server Startup On <http://%s:%s/>', $appDomain['host'],
                $appDomain['port']));
        });
        $http->on("request", function ($request, $response) use ($http, $appDomain) {
            if (in_array('*', $appDomain['allowOrigin'], true)) {
                $response->header('Access-Control-Allow-Origin', $appDomain['allowOrigin']);
            } else if (in_array($request->header['host'], $appDomain, true)) {
                $response->header('Access-Control-Allow-Origin', $request->header['host']);
            }
            $response->header('Access-Control-Allow-Headers', implode(',', $appDomain['allowHeaders']));
            $argv = [];
            $request->server['HTTP_HOST'] = $request->header['host'];
            $request->server = array_change_key_case($request->server, CASE_UPPER);
            try {
                $req = new \QApi\Request(new \QApi\Data($argv), $request->get, $request->post,
                    array_merge($request->get ?? [], $request->post ?? []), $request->rawContent(), $request->cookie, null,
                    $request->server, $request->header);
                $response->end(\QApi\App::run(request: $req));
            } catch (RuntimeException $e) {
                error_log(get_class($e) . '：' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
                $response->end((new \QApi\Response())->setCode(500)->setMsg($e->getMessage())->setExtra([
                ])->fail());
            }
            if ($appDomain['runMode'] === RunMode::DEVELOPMENT) {
                $http->reload();
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
        return $data;
    }

    public function help(): mixed
    {
        return null;
    }
}