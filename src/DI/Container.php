<?php

namespace QApi\DI;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;

/**
 * Class Container
 * @package QApi\DI
 */
class Container implements ContainerInterface
{
    /**
     * @var Container[]
     */
    private static array $containers = [];

    private array $dependencies;

    /**
     * Container constructor.
     * new Container(['Test' => Test::class,'Test2' => fn() => new Test2()]);
     * @param array $dependencies
     */
    public function __construct(array $dependencies = [])
    {
        $this->dependencies = $dependencies;
    }

    /**
     * @param string $containerId
     * @return Container
     */
    public static function getContainer(string $containerId): Container
    {
        if (!isset(self::$containers[$containerId])) {
            self::$containers[$containerId] = new Container();
        }
        return self::$containers[$containerId];
    }

    /**
     * @param string $containerId
     * @return Container
     */
    public static function G(string $containerId = 'App'): Container
    {
        return self::getContainer($containerId);
    }

    /**
     * $container->delete('foo', 'bar');
     * @param string ...$id
     * @return bool
     */
    public function delete(string ...$id): bool
    {
        $result = true;
        foreach ($id as $item) {
            if (isset($this->dependencies[$item])) {
                unset($this->dependencies[$item]);
            } else {
                $result = false;
            }
        }
        return $result;
    }

    /**
     * $container->get('foo');
     * @param string $id
     * @return mixed
     * @throws NotFoundException
     */
    public function get(string $id): mixed
    {
        if (!$this->has($id)) {
            throw new NotFoundException("Service not found: $id");
        }
        return $this->resolveDependencies($this->dependencies[$id]);
    }

    /**
     * $container->has('foo');
     * @return mixed
     */
    public function has(string $id): bool
    {
        return array_key_exists($id, $this->dependencies);
    }

    /**
     * $container->set('foo', Foo::class);
     * $container->set('foo', fn() => new Foo());
     * $container->set('foo', ['a'=>[]]);
     * @param string $id
     * @param mixed $value
     * @return void
     */
    public function set(string $id, mixed $value = null, $isClass = true): void
    {
        if ($value === null) {
            $value = $id;
        }
        if (is_string($value) && $isClass && class_exists($value)) {
            $value = fn() => $this->make($value);
        }
        $this->dependencies[$id] = $value;
    }

    /**
     * $container->make(Foo::class);
     * @param string $className
     * @return mixed
     * @throws NotFoundException
     * @throws ReflectionException
     */
    public function make(string $className, array $parameters = [], $autoMake = true): mixed
    {
        $reflectionClass = new ReflectionClass($className);
        $constructor = $reflectionClass->getConstructor();
        if ($constructor === null) {
            return $reflectionClass->newInstanceWithoutConstructor();
        }
        $refParameters = $constructor->getParameters();
        $dependencies = [];
        foreach ($refParameters as $parameter) {
            if (isset($parameters[$parameter->getName()])) {
                $dependencies[] = $parameters[$parameter->getName()];
                continue;
            }
            if ($parameter->isVariadic()) {
                throw new InvalidArgumentException("[{$className}::__construct]Variadic parameters are not supported: \${$parameter->getName()}");
            }
            $parameterType = $parameter->getType();
            if ($parameterType === null) {
                throw new \InvalidArgumentException("[{$className}::__construct]Cannot resolve parameter: \${$parameter->getName()}");
            }
            if ($parameterType instanceof \ReflectionUnionType || in_array($parameterType->getName(), ['int', 'string', 'float', 'bool', 'array', 'object', 'callable', 'iterable'])) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                    continue;
                } else {
                    throw new \InvalidArgumentException("[{$className}::__construct]Cannot resolve parameter: \${$parameter->getName()}");
                }
            }
            $parameterInterface = $parameterType->getName();
            if (!$this->has($parameterInterface)) {
                if ($autoMake) {
                    $this->set($parameterInterface, $parameterInterface);
                } else {
                    throw new NotFoundException("[{$className}::__construct]Dependency not found in container: $parameterInterface");
                }
            }
            $dependencies[] = $this->get($parameterInterface);
        }
        return $reflectionClass->newInstanceArgs($dependencies);
    }

    /**
     * $container->call([$foo, 'bar']);
     * $container->call(Foo::class);
     * $container->call([Foo::class,'bar']);
     * $container->call('Foo::bar');
     * $container->call('Foo::bar', ['id' => 1]);
     * $container->call(fn(Foo $foo, $id) => $foo->bar($id), ['id' => 1]);
     * @param mixed $callable
     * @param array $parameters
     * @return mixed
     * @throws ReflectionException
     * @throws NotFoundException|ReflectionException
     */
    public function call(mixed $callable, array $parameters = [], $autoMake = true): mixed
    {
        if (is_string($callable) && str_contains($callable, '::')) {
            $callable = explode('::', $callable, 2);
        }
        if (is_array($callable)) {
            [$class, $method] = $callable;
            if (is_string($class)) {
                $class = $this->make($class, [], $autoMake);
            }
            $reflection = new ReflectionMethod($class, $method);
        } else {
            $reflection = new ReflectionFunction($callable);
        }
        $dependencies = [];
        foreach ($reflection->getParameters() as $param) {
            $paramType = $param->getType();
            if ($paramType instanceof \ReflectionNamedType) {
                $paramTypeName = $paramType->getName();
            } else {
                $paramTypeName = null;
            }
            if ($paramTypeName !== null) {
                if (isset($parameters[$param->name])) {
                    $dependencies[] = $parameters[$param->name];
                    continue;
                }
                if (!$this->has($paramTypeName)) {
                    if (in_array($paramTypeName, ['int', 'string', 'float', 'bool', 'array', 'object', 'callable', 'iterable'])) {
                        if ($param->isDefaultValueAvailable()) {
                            $dependencies[] = $param->getDefaultValue();
                            continue;
                        } else {
                            throw new InvalidArgumentException("Cannot resolve parameter: \${$param->name}");
                        }
                    }
                    if ($autoMake) {
                        $this->set($paramTypeName);
                    } else {
                        throw new NotFoundException("Dependency not found in container: $paramTypeName");
                    }
                    $dependencies[] = $this->get($paramTypeName);
                } else {
                    $dependencies[] = $this->get($paramTypeName);
                }
            } elseif (isset($parameters[$param->name])) {
                $dependencies[] = $parameters[$param->name];
            } elseif ($param->isDefaultValueAvailable()) {
                $dependencies[] = $param->getDefaultValue();
            } else {
                throw new InvalidArgumentException("Cannot resolve parameter: \${$param->name}");
            }
        }
        if (isset($class)) {
            return $reflection->invokeArgs($class, $dependencies);
        } else {
            return $reflection->invokeArgs($dependencies);
        }
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function resolveDependencies(mixed $value): mixed
    {
        if (is_callable($value)) {
            return $value($this);
        }
        if (is_object($value)) {
            return $value;
        }
//        if (is_array($value)) {
//            $resolved = [];
//            foreach ($value as $key => $item) {
//                $resolved[$key] = $this->resolveDependencies($item);
//            }
//            return $resolved;
//        }
        return $value;
    }
}
