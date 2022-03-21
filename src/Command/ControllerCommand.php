<?php

namespace QApi\Command;

use Composer\Autoload\ClassLoader;
use QApi\Config;

/**
 * 创建控制器
 */
class ControllerCommand extends CommandHandler
{
    public string $name = 'build:controller';

    public function handler(array $argv): mixed
    {
        if (!isset($argv[0])) {
            return $this->handler([$this->choseApp()]);
        }
        if (!isset($argv[1])) {
            return $this->handler([$argv[0], $this->getVersion()]);
        }
        if (!isset($argv[2])) {
            return $this->handler([$argv[0], $argv[1], $this->getControllerDir()]);
        }
        if (!isset($argv[3])) {
            return $this->handler([$argv[0], $argv[1], $argv[2], $this->getControllerName()]);
        }
        if (!isset($argv[3])) {
            return $this->handler([$argv[0], $argv[1], $argv[2], $this->getControllerName()]);
        }
        if (!isset($argv[4])) {
            return $this->handler([$argv[0], $argv[1], $argv[2], $argv[3], $this->customAction()]);
        }
        $this->saveController(Config::apps()[$argv[0]], $argv[1], $argv[2], $argv[3], $argv[4]);
        return null;
    }

    public function customAction(): string
    {
        $choseData = [
            'lists', 'info', 'save', 'delete', 'add', 'edit'
        ];
        $input = $this->command->cli->blue()->checkboxes('Please select the trait you want to use:', $choseData);
        $actions = $input->prompt();
        return implode(',', $actions);
    }

    public function saveController(Config\Application $app, string $version, string $dir, string $controllerName,
                                   string             $actions)
    {
        $actions = explode(',', $actions);
        foreach ($actions as $key => $action) {
            $actions[$key] = trim($action);
        }
        $controller = $this->parseName
            ($controllerName, 1) . 'Controller';
        $savePath = $this->getComposerNameSpaceDir($app->nameSpace) . $version . DIRECTORY_SEPARATOR . ($dir ? parseDir
            ($dir) : '') .
            $controller . '.php';
        if (file_exists($savePath)) {
            $this->command->cli->yellow($savePath . ' exists!');
            $input = $this->command->cli
                ->red()
                ->radio('Controller file already exists, do you want to regenerate it:',
                    [
                        'YES', 'NO'
                    ]);
            if ($input->prompt() === 'NO') {
                return;
            }
        }
        if ($dir) {
            $dir = '\\' . str_replace('/', '\\', trim($dir, "/\\ \t\n\r\0\x0B"));
        } else {
            $dir = '';
        }
        $controllerNameSpace = trim($app->nameSpace, '\\') . '\\' . $version . $dir;
        $actionsContent = '';
        foreach ($actions as $action) {
            $actionsContent .= "\n\n    public function {$action}Action(Request \$request, Response \$response): Response
    {
        return \$response;
    }";
        }
        $controllerContent = <<<CONTROLLERCONTENT
<?php

namespace {$controllerNameSpace};


use QApi\Request;
use QApi\Response;

class {$controller}
{{$actionsContent}
}
CONTROLLERCONTENT;
        mkPathDir($savePath);
        file_put_contents($savePath, $controllerContent);
        $this->command->info('Generated successfully ' . $savePath);
    }

    public function getVersion(): string
    {
        $input = $this->command->cli->blue()->input('Please enter version[V100]:');
        return $input->prompt() ?: 'V100';
    }

    public function getControllerName(): string
    {
        $input = $this->command->cli->blue()->input('Please enter controller name:');
        $controllerName = trim($input->prompt());
        if (!$controllerName) {
            return $this->getControllerName();
        }
        return $controllerName;
    }

    /**
     * @return string
     */
    public function choseApp(): string
    {

        $apps = Config::apps();
        $appLists = [];
        foreach ($apps as $host => $app) {
            if (!in_array($app->getNameSpace(), $appLists)) {
                $appLists[$host] = $app->getNameSpace();
            }
        }
        $input = $this->command->cli->blue()->radio('Please select an application:', $appLists);
        return $input->prompt();
    }

    public function getControllerDir(): string
    {
        $input = $this->command->cli->blue()->input('Please enter the directory of the controller file:');
        return $input->prompt();
    }

    public function getComposerNameSpaceDir($nameSpace): string
    {
        $load = ClassLoader::getRegisteredLoaders();
        $vendorDir = PROJECT_PATH . 'vendor';
        foreach ($load as $item) {
            preg_match('/vendorDir".*"(.*)"/iUs', serialize($item), $matches);
            if (count($matches) == 2) {
                $vendorDir = $matches[1];
                break;
            }
        }
        $psr4 = include $vendorDir . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . 'autoload_psr4.php';
        $psr4Dir = [];
        foreach ($psr4 as $space => $dirArray) {
            $in = false;
            foreach ($dirArray as $dir) {
                if (str_starts_with($dir, $vendorDir)) {
                    $in = true;
                    break;
                }
            }
            if (!$in) {
                $psr4Dir[$space] = end($dirArray);
            }
        }
        $nameSpace = explode('\\', $nameSpace);
        $nameSpace = array_filter($nameSpace);
        $count = count($nameSpace);
        $hitCount = $count;
        $tempSpace = '';
        for ($i = $count; $i >= 0; --$i) {
            $tempSpace = array_slice($nameSpace, 0, $i + 1);

            $tempSpace = implode('\\', $tempSpace) . '\\';
            if (in_array($tempSpace, array_keys($psr4Dir))) {
                $hitCount = $i;
                break;
            }
        }
        $dir = $psr4Dir[$tempSpace];
        if ($hitCount < $count) {
            $dir = $psr4Dir[$tempSpace] . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, array_slice($nameSpace, $hitCount - 1)) .
                DIRECTORY_SEPARATOR;
        }
        return $dir;
    }

    public function help(): mixed
    {
        // TODO: Implement help() method.
    }
}