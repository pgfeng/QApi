<?php


namespace QApi\Attribute;


use QApi\Attribute\Column\Field;
use QApi\Attribute\Parameter\GetParam;
use QApi\Attribute\Parameter\GetParamFromTableField;
use QApi\Attribute\Parameter\HeaderParam;
use QApi\Attribute\Parameter\PathParam;
use QApi\Attribute\Parameter\PathParamFromTableField;
use QApi\Attribute\Parameter\PostParam;
use QApi\Attribute\Parameter\PostParamFromTable;
use QApi\Attribute\Parameter\PostParamFromTableField;
use QApi\Cache\Cache;
use QApi\Command;
use QApi\Config;
use QApi\DI\Container;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Utils
{

    public static array $docApp = [];

    public static array $columns = [];


    /**
     * @param $apps
     * @param Command|null $commandHandler
     * @return array
     * @throws \ErrorException
     * @deprecated
     */
    public static function v0($apps, Command $commandHandler = null): array
    {
        foreach ($apps as $host => $app) {
            $commandHandler?->info('Loading document for app: ' . $host);
            if (!isset(self::$docApp[$app->appDir])) {
                self::$docApp[$app->appDir] = [];
            }
            $versions = Config::versions($app->runMode);
            $apis = [];
            foreach ($versions as $version) {
                $commandHandler?->info('Loading document for version: ' . $version->versionDir);
                $path = PROJECT_PATH . $app->appDir . DIRECTORY_SEPARATOR . $version->versionDir .
                    DIRECTORY_SEPARATOR;
                $data = [];
                self::buildVersionDoc(scandir($path), $path, $app->nameSpace . '\\' . $version->versionDir,
                    $version->versionDir, $data, $path);
                if (!isset($apis[$version->versionDir])) {
                    $apis[$version->versionDir] = [];
                }
                foreach ($data as $controller => $methods) {
                    $commandHandler?->info('Loading document for controller: ' . $controller);
                    foreach ($methods as $mname => $attr) {
                        $commandHandler?->info('Loading document for method: ' . $mname);
                        $path = '';
                        $type = '';
                        $tag = '';
                        $summary = '';
                        $description = '';
                        $resultDictionary = [];
                        foreach ($attr as $item) {
                            foreach ($item as $key => $v) {
                                if ($v instanceof Route || $v instanceof GetParam || $v instanceof PostParam || $v
                                    instanceof HeaderParam || $v instanceof PathParam || $v instanceof PostParamFromTableField || $v instanceof GetParamFromTableField || $v instanceof PathParamFromTableField) {
                                    if ($v instanceof Route) {
                                        if ($path) {
                                            if (!str_ends_with($path, '/')) {
                                                $path .= '/';
                                            }
                                        }
                                        if ($v->path) {
                                            $commandHandler?->info('Loading document for route: ' . $v->path);
                                        }
                                        if ($v->path) {
                                            $path .= $v->path;
                                        }
                                        if (!$tag && $v->tag) {
                                            $tag = implode('-', $v->tag);
                                        }
                                        if ($v->summary) {
                                            $summary = $v->summary;
                                        }
                                        if ($v->description) {
                                            $description = $v->description;
                                        }
                                        if ($v->methods) {
                                            $type = $v->methods;
                                        }
                                    } else {
                                        $commandHandler?->info('Loading document for parameter: ' . get_class($v) . '->' . $v->name);
                                    }
                                    $item[$key] = $v->toArray();
                                } else if ($v instanceof ResultDictionary || $v instanceof ResultDictionarys || $v
                                    instanceof ResultDictionaryFromTable) {
                                    $commandHandler?->info('Loading document for result dictionary: ' . get_class($v) . '->' . json_encode($data, JSON_UNESCAPED_UNICODE));
                                    $data = $v->toArray();
                                    foreach ($data as $resultField) {
                                        if (!empty($resultField['comment'])) {
                                            if (!isset($resultDictionary[$resultField['tag']])) {
                                                $resultDictionary[$resultField['tag']] = [];
                                            }
                                            $resultDictionary[$resultField['tag']][] = [
                                                'name' => $resultField['name'],
                                                'comment' => $resultField['comment'] ?? '',
                                                'type' => $resultField['type'],
                                            ];
                                        }
                                    }
                                }
                            }
                        }
                        if (!$tag) {
                            $tag = 'Default';
                        }
                        if (!isset($apis[$version->versionDir][$tag])) {
                            $apis[$version->versionDir][$tag] = [];
                        }
                        if ($summary) {
                            $apis[$version->versionDir][$tag][] = [
                                'summary' => $summary,
                                'type' => $type,
                                'description' => $description,
                                'path' => $path ?: ('/' . $controller . '/' . $mname),
                                'SystemPath' => '/' . $controller . '/' . $mname,
                                'params' => $attr,
                                'resultDictionary' => $resultDictionary,
                                'response' => null,
                            ];
                        }
                    }
                }
                foreach ($apis[$version->versionDir] as $tag => $docArray) {
                    if (!count($docArray)) {
                        unset($apis[$version->versionDir][$tag]);
                    }
                }
                $commandHandler?->success('Loading document completed for version: [' . $version->versionDir . ']');
            }
            self::$docApp[$app->appDir][] = [
                'host' => $host,
                'scheme' => $app->scheme,
                'mode' => $app->runMode,
                'nameSpace' => $app->nameSpace,
                'doc' => $apis,
            ];
            $commandHandler?->success('Loading document completed for app: [' . $host . ']');
        }
        return self::$docApp;
    }

    /**
     * V1 文档生成
     * @param Config\Application[] $apps
     * @param Command|null $commandHandler
     * @return array
     */
    public static function v1(array $apps, Command $commandHandler = null, InputInterface $input = null, OutputInterface $output = null): array
    {
        $appData = [];
        $namespaces = [];
        $doc = [];
        foreach ($apps as $host => $app) {
            if (!array_key_exists($app->nameSpace, $namespaces)) {
                $namespaces[$app->nameSpace] = $app->appDir;
            }
            $appData[$host] = [
                'scheme' => $app->scheme,
                'host' => $host,
                'appDir' => $app->appDir,
                'nameSpace' => $app->nameSpace,
                'runMode' => $app->runMode,
                'defaultVersionName' => $app->defaultVersionName,
                'allowOrigin' => $app->allowOrigin,
                'allowHeaders' => $app->allowHeaders,
                'allowMethods' => $app->allowMethods,
            ];
        }
        foreach ($namespaces as $namespace => $appDir) {
            $versions = Config::versions($app->runMode);
            foreach ($versions as $version) {
                $output?->writeln("<info>[$appDir:$version->versionDir]</info>");
                $path = PROJECT_PATH . $appDir . DIRECTORY_SEPARATOR . $version->versionDir .
                    DIRECTORY_SEPARATOR;
                $doc[$namespace][$version->versionName] = [];
                $data = [];
                if (!file_exists($path))
                    continue;
                self::buildVersionDoc(scandir($path), $path, $namespace . '\\' . $version->versionDir,
                    $version->versionDir, $data, $path);
                foreach ($data as $controller => $methods) {
                    $output?->writeln("    <info>{$controller}Controller:</info>");
                    $commandHandler?->info('Loading document for controller: ' . $controller);
                    foreach ($methods as $mname => $attr) {
                        $commandHandler?->info("Loading document for method: $mname");
                        $output?->writeln("        <info>{$mname}Action:</info>");
                        $path = '';
                        $type = '';
                        $tag = '';
                        $summary = '';
                        $description = '';
                        $resultDictionary = [];
                        foreach ($attr as $item) {
                            foreach ($item as $key => $v) {
                                if ($v instanceof Route || $v instanceof GetParam || $v instanceof PostParam || $v
                                    instanceof HeaderParam || $v instanceof PathParam || $v instanceof PostParamFromTableField || $v instanceof GetParamFromTableField || $v instanceof PathParamFromTableField) {
                                    if ($v instanceof Route) {
                                        $output?->writeln('<info>            ' . get_class($v) . '->' .json_encode($v->toArray(),JSON_UNESCAPED_UNICODE). '</info>');
                                        if ($path) {
                                            if (!str_ends_with($path, '/')) {
                                                $path .= '/';
                                            }
                                        }
                                        if ($v->path) {
                                            $path .= $v->path;
                                        }
                                        if ($v->path) {
                                            $commandHandler?->info('            Loading document for route: ' . $path);
                                        }
                                        if (!$tag && $v->tag) {
                                            $tag = $v->tag;
                                        }
                                        if ($v->summary) {
                                            $summary = $v->summary;
                                        }
                                        if ($v->description) {
                                            $description = $v->description;
                                        }
                                        if ($v->methods) {
                                            $type = $v->methods;
                                        }
                                    } else {
                                        $output?->writeln('<info>            ' . get_class($v) . '->' . $v->name .json_encode($v->toArray(),JSON_UNESCAPED_UNICODE). '</info>');
                                        $commandHandler?->info('Loading document for parameter: ' . get_class($v) . '->' . $v->name);
                                    }
                                    $item[$key] = $v->toArray();
                                } else if ($v instanceof ResultDictionary || $v instanceof ResultDictionarys || $v
                                    instanceof ResultDictionaryFromTable) {
                                    $commandHandler?->info('Loading document for result dictionary: ' . get_class($v) . '->' . json_encode($data, JSON_UNESCAPED_UNICODE));

                                    $output?->writeln('<info>            ' . get_class($v) . '->' . json_encode($data, JSON_UNESCAPED_UNICODE) . '</info>');
                                    $data = $v->toArray();
                                    foreach ($data as $resultField) {
                                        if (!empty($resultField['comment'])) {
                                            if (!isset($resultDictionary[$resultField['tag']])) {
                                                $resultDictionary[$resultField['tag']] = [];
                                            }
                                            $resultDictionary[$resultField['tag']][] = [
                                                'name' => $resultField['name'],
                                                'comment' => $resultField['comment'],
                                                'type' => $resultField['type'],
                                            ];
                                        }
                                    }
                                }
                            }
                        }
                        if (!$tag) {
                            $tag = ['Default'];
                        }
                        if ($summary) {
                            $doc[$namespace][$version->versionName][] = [
                                'summary' => $summary,
                                'moduleKey' => implode('-', $tag),
                                'modules' => $tag,
                                'type' => $type,
                                'description' => $description,
                                'path' => $path ?: ('/' . $controller . '/' . $mname),
                                'systemPath' => '/' . $controller . '/' . $mname,
                                'params' => $attr,
                                'resultDictionary' => $resultDictionary,
                                'response' => null,
                            ];
                        }
                    }
                }
                foreach ($doc[$namespace][$version->versionName] as $tag => $docArray) {
                    if (!count($docArray)) {
                        unset($doc[$namespace][$version->versionName][$tag]);
                    }
                }

                $output?->writeln('<info>Loading document completed for version: [' . $version->versionDir . ']</info>');
                $commandHandler?->success('Loading document completed for version: [' . $version->versionDir . ']');
            }
        }
        return [
            'Applications' => $appData,
            'Documents' => $doc,
        ];
    }

    /**
     * @return array
     */
    public static function loadDocument(Command $commandHandler = null, string $version = '', InputInterface $input = null, OutputInterface $output = null): array
    {
        $output?->writeln('<info>Loading document...</info>');
        $apps = Config::apps();
        if (!$version) {
            return self::v0($apps, $commandHandler);
        } else {
            $commandHandler?->info('Loading document...');
            return self::v1($apps, $commandHandler, $input, $output);
        }
    }

    /**
     * Rebuild
     * @return mixed
     * @throws \ErrorException
     * @throws \QApi\Exception\CacheErrorException
     */
    public static function rebuild(Command $commandHandler = null, string $version = '', InputInterface $input = null, OutputInterface $output = null): mixed
    {
        $data = self::loadDocument($commandHandler, $version, $input, $output);
        $cache = Cache::initialization('__document');
        $cache->set('__apiDocument' . $version, $data);
        return null;
    }

    /**
     * @param array $san_files
     * @param string $parent_path
     * @param string $nameSpace
     * @param string $versionDir
     * @param array $data
     * @param string $base_path
     * @return void
     */
    public static function buildVersionDoc(array $san_files, string $parent_path, string $nameSpace, string $versionDir, array &$data =
    [], string                                   $base_path = ''): array
    {
        foreach ($san_files as $path) {
            if ($path !== '.' && $path !== '..') {
                if (is_dir($parent_path . $path)) {
                    self::buildVersionDoc(scandir($parent_path . $path . DIRECTORY_SEPARATOR), $parent_path . $path .
                        DIRECTORY_SEPARATOR,
                        $nameSpace . '\\' . $path, $versionDir, $data, $base_path);
                } else if (preg_match('#(.+)Controller.php#', $path, $match)) {
                    $data[str_replace($base_path, '', $parent_path . $match[1])] = self::getDocAttribute($nameSpace . '\\' . $match[1] . 'Controller');
                }
            }
        }
        return $data;
    }

    /**
     * @param string $className
     * @return mixed
     */
    private static function getClassObject(string $className): mixed
    {
        if (!Container::G()->has($className)) {
            Container::G()->set($className);
        }
        return Container::G()->get($className);
    }

    /**
     * @throws ReflectionException
     */
    public static function getDocAttribute(string $className): array
    {
        $data = [];
        $refClass = new ReflectionClass(self::getClassObject($className));
        $actions = $refClass->getMethods();
        foreach ($actions as $method) {
            if (str_ends_with($method->getName(), 'Action')) {
                $data[substr($method->getName(), 0, -6)] = self::getAttribute($className,
                    $method->getName(), [
                        Route::class,
                        HeaderParam::class,
                        PathParam::class,
                        GetParam::class,
                        PostParam::class,
                        PostParamFromTable::class
                    ]);
            }
        }
        return $data;
    }

    /**
     * @param string $columnClassName
     * @param array $ignoreField
     * @return array
     * @throws ReflectionException
     */
    public static function tableColumn(string $columnClassName, array $ignoreField = []): array
    {
        if (isset(self::$columns[$columnClassName])) {
            $columns = self::$columns[$columnClassName];
        } else {
            $columns = [];
            $ref = new ReflectionClass(self::getClassObject($columnClassName));
            $constants = $ref->getReflectionConstants();
            foreach ($constants as $constant) {
                $attributes = $constant->getAttributes(Field::class);
                foreach ($attributes as $attribute) {
                    $argument = $attribute->getArguments();
                    $columns[$argument['name']] = [
                        'name' => $argument['name'],
                        'comment' => $argument['comment'],
                        'type' => $argument['type'],
                    ];
                }
            }
            self::$columns[$columnClassName] = $columns;
        }
        return array_filter($columns, static function ($k) use ($ignoreField) {
            return !in_array($k, $ignoreField, true);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * @param string $className
     * @param string|null $method
     * @param array|null $attributeFilter
     * @return array
     * @throws ReflectionException
     */
    public static function getAttribute(string $className, string $method = null, array $attributeFilter =
    null): array
    {
        $refClass = new ReflectionClass(self::getClassObject($className));
        $classAttributes = $refClass->getAttributes();
        $data = [];
        if ($classAttributes) {
            $classRoute = $refClass->getAttributes(Route::class);
            foreach ($classAttributes as $item) {
                $newInstance = $item->newInstance();
                if ($attributeFilter && in_array($item->getName(), $attributeFilter, true)) {
                    if ($newInstance instanceof PostParamFromTable) {
                        $data[PostParam::class] = array_merge($data[PostParam::class] ?? [], $newInstance->postParams);
                    } else {
                        $data[$item->getName()][] = $newInstance;
                    }

                    if ($item->getName() === Route::class && $newInstance->middleware) {
                        self::margeMiddleware($newInstance->middleware, $data, $attributeFilter);
                    }
                } else {
                    $data[$item->getName()][] = $newInstance;
                }
            }
        }
        if ($method) {
            $methods = $refClass->getMethod($method);
            $methodsAttributes = $methods->getAttributes();
            foreach ($methodsAttributes as $item) {
                $newInstance = $item->newInstance();
                if ($attributeFilter && in_array($item->getName(), $attributeFilter, true)) {
                    if ($newInstance instanceof PostParamFromTable) {
                        $data[PostParam::class] = array_merge($data[PostParam::class] ?? [], $newInstance->postParams);
                    } else {
                        $data[$item->getName()][] = $newInstance;
                    }
                    if ($item->getName() === Route::class && $newInstance->middleware) {
                        self::margeMiddleware($newInstance->middleware, $data, $attributeFilter);
                    }
                } else {
                    $data[$item->getName()][] = $newInstance;
                }
            }
        }
        return $data;
    }

    /**
     * @param array|string $middle
     * @param $data
     * @param $attributeFilter
     * @return void
     * @throws ReflectionException
     */
    public static function margeMiddleware(array|string $middle, &$data, $attributeFilter): void
    {
        if (is_string($middle)) {
            $middleware_data = self::getAttribute($middle, 'handle', $attributeFilter);
            foreach ($middleware_data as $key => $lists) {
                foreach ($lists as $v) {
                    $data[$key][] = $v;
                }
            }
        } else {
            foreach ($middle as $m) {
                $middleware_data = self::getAttribute($m, 'handle', $attributeFilter);
                foreach ($middleware_data as $key => $lists) {
                    foreach ($lists as $v) {
                        $data[$key][] = $v;
                    }
                }
            }
        }
    }
}