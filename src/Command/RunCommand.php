<?php


namespace QApi\Command;


use QApi\App;
use QApi\Command;
use QApi\Config;
use QApi\Config\Application;

class RunCommand extends CommandHandler
{
    /**
     * @var string
     */
    public string $name = 'run';

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
        $command = sprintf(
            'php -S %s:%d -t %s %s -i',
            '0.0.0.0',
            $appDomain['port'],
            escapeshellarg(Config::command('ServerRunDir')),
            escapeshellarg(__DIR__ . DIRECTORY_SEPARATOR . '../Route/router.php'),
        );
        $this->command->cli->blue(sprintf('QApi Server Startup On <http://%s:%s/>', $appDomain['host'],
            $appDomain['port']));
        passthru($command);
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
        $data['host'] = (string)($data['host'] ?? '0.0.0.0');
        $data['port'] = (int)($data['port'] ?? 80);
        return $data;
    }

    public function help(): void
    {
//        return null;
    }
}