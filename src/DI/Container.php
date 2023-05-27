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
     * @param string $id
     * @param string|\Closure $value
     * @return void
     */
    public function set(string $id, string|\Closure $value): void
    {
        if (is_string($value) && class_exists($value)) {
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
    public function make(string $className) : mixed
    {
        $reflectionClass = new ReflectionClass($className);

        $constructor = $reflectionClass->getConstructor();

        if ($constructor === null) {
            return $reflectionClass->newInstanceWithoutConstructor();
        }

        $parameters = $constructor->getParameters();

        $dependencies = [];

        foreach ($parameters as $parameter) {
            if ($parameter->isVariadic()) {
                throw new \InvalidArgumentException("Variadic arguments are not supported");
            }

            $parameterType = $parameter->getType();

            if ($parameterType === null) {
                throw new \InvalidArgumentException("Parameter type is not defined");
            }

            $parameterInterface = $parameterType->getName();

            if (!$this->has($parameterInterface)) {
                throw new NotFoundException("Dependency not found in container: $parameterInterface");
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
    public function call(mixed $callable, array $parameters = []): mixed
    {
        if (is_string($callable) && str_contains($callable, '::')) {
            $callable = explode('::', $callable, 2);
        }

        if (is_array($callable)) {
            [$class, $method] = $callable;

            if (is_string($class)) {
                $class = $this->make($class);
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
                $dependencies[] = $this->get($paramTypeName);
            } elseif (array_key_exists($param->name, $parameters)) {
                $dependencies[] = $parameters[$param->name];
            } elseif ($param->isDefaultValueAvailable()) {
                $dependencies[] = $param->getDefaultValue();
            } else {
                throw new InvalidArgumentException("Cannot resolve parameter: \${$param->name}");
            }
        }
        if (isset($class)) {
            return $reflection->invokeArgs($class, $dependencies);
        }else{
            return $reflection->invokeArgs($dependencies);
        }
    }


    /**
     * @param mixed $value
     * @return mixed
     */
    private function resolveDependencies(mixed $value) : mixed
    {
        if (is_callable($value)) {
            return $value($this);
        }
        if (is_object($value)) {
            return $value;
        }
        if (is_array($value)) {
            $resolved = [];
            foreach ($value as $key => $item) {
                $resolved[$key] = $this->resolveDependencies($item);
            }
            return $resolved;
        }
        return $value;
    }
}
