<?php


namespace QApi\Attribute;


use QApi\Attribute\Parameter\GetParam;
use QApi\Attribute\Parameter\HeaderParam;
use QApi\Attribute\Parameter\PathParam;
use QApi\Attribute\Parameter\PostParam;
use QApi\Cache\Cache;
use QApi\Config;
use ReflectionClass;
use ReflectionException;

class Utils
{

    public static array $docApp = [];

    /**
     * Rebuild
     * @return mixed
     * @throws \ErrorException
     * @throws \QApi\Exception\CacheErrorException
     */
    public static function rebuild(): mixed
    {
        /**
         * @var Config\Application[]
         */
        $apps = Config::apps();
        foreach ($apps as $host => $app) {
            if (!isset(self::$docApp[$app->appDir])) {
                self::$docApp[$app->appDir] = [];
            }
            $versions = Config::versions($app->runMode);
            $apis = [];
            foreach ($versions as $version) {
                $path = PROJECT_PATH . $app->appDir . DIRECTORY_SEPARATOR . $version->versionDir .
                    DIRECTORY_SEPARATOR;
                $data = [];
                self::buildVersionDoc(scandir($path), $path, $app->nameSpace . '\\' . $version->versionDir,
                    $version->versionDir, $data, $path);
                foreach ($data as $controller => $methods) {
                    foreach ($methods as $mname => $attr) {
                        $path = '';
                        $type = '';
                        $tag = '';
                        $summary = '';
                        $description = '';
                        foreach ($attr as $item) {
                            foreach ($item as $key => $v) {
                                if ($v instanceof Route) {
                                    if ($v->path) {
                                        $path = $v->path;
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
                                }
                                $item[$key] = $v->toArray();
                            }
                        }
                        if (!isset($apis[$version->versionDir][$tag])) {
                            $apis[$version->versionDir][$tag] = [];
                        }
                        $apis[$version->versionDir][$tag][] = [
                            'summary' => $summary,
                            'type' => $type,
                            'description' => $description,
                            'path' => $path ?: ('/' . $controller . '/' . $mname),
                            'SystemPath' => '/' . $controller . '/' . $mname,
                            'params' => $attr,
                            'response' => null,
                        ];
                    }
                }
            }
            self::$docApp[$app->appDir][] = [
                'host' => $host,
                'scheme' => $app->scheme,
                'mode' => $app->runMode,
                'nameSpace' => $app->nameSpace,
                'doc' => $apis,
            ];
        }
        $cache = Cache::initialization('__document');
        $cache->set('__apiDocument', self::$docApp);
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
    public static function buildVersionDoc(array $san_files, string $parent_path, string $nameSpace, string $versionDir, &$data =
    [], $base_path = ''): void
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
    }

    public static function getDocAttribute(string $className)
    {
        $data = [];
        $refClass = new ReflectionClass(new $className);
        $actions = $refClass->getMethods();
        foreach ($actions as $method) {
            if (substr($method->getName(), -6) === 'Action') {
                $data[substr($method->getName(), 0, -6)] = self::getAttribute($className,
                    $method->getName(), [
                        Route::class,
                        HeaderParam::class,
                        PathParam::class,
                        GetParam::class,
                        PostParam::class,
                    ]);
            }
        }
        return $data;
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
        $refClass = new ReflectionClass(new $className);
        $classAttributes = $refClass->getAttributes();
        $data = [];
        if ($classAttributes) {
            foreach ($classAttributes as $item) {
                $newInstance = $item->newInstance();
                if ($attributeFilter && in_array($item->getName(), $attributeFilter, true)) {
                    $data[$item->getName()][] = $newInstance;
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
                    $data[$item->getName()][] = $newInstance;
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