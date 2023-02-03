<?php

namespace QApi\Event;

use JetBrains\PhpStorm\Pure;
use Psr\EventDispatcher\StoppableEventInterface;

class Event implements \ArrayAccess, \IteratorAggregate, StoppableEventInterface
{

    /**
     * @var string|mixed
     */
    protected string $subject;

    /**
     * @var array
     */
    protected array $arguments;

    /**
     * @var bool
     */
    private bool $propagationStopped = false;

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    /**
     * Stops the propagation of the event to further event listeners.
     *
     * If multiple event listeners are connected to the same event, no
     * further event listener will be triggered once any trigger calls
     * stopPropagation().
     */
    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }

    /**
     * Encapsulate an event with $subject and $args.
     *
     * @param mixed $subject The subject of the event, usually an object or a callable
     * @param array $arguments Arguments to store in the event
     */
    public
    function __construct(mixed $subject = null, array $arguments = [])
    {
        $this->subject = $subject;
        $this->arguments = $arguments;
    }

    /**
     * Getter for subject property.
     */
    public
    function getSubject(): mixed
    {
        return $this->subject;
    }

    /**
     * Get argument by key.
     *
     * @throws \InvalidArgumentException if key is not found
     */
    public
    function getArgument(string $key): mixed
    {
        if ($this->hasArgument($key)) {
            return $this->arguments[$key];
        }

        throw new \InvalidArgumentException(sprintf('Argument "%s" not found.', $key));
    }

    /**
     * Add argument to event.
     *
     * @return $this
     */
    public
    function setArgument(string|null $key, mixed $value): static
    {
        if ($key == null) {
            $this->arguments[] = $value;
        } else {
            $this->arguments[$key] = $value;
        }

        return $this;
    }

    /**
     * Getter for all arguments.
     */
    public
    function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Set args property.
     *
     * @return $this
     */
    public
    function setArguments(array $args = []): static
    {
        $this->arguments = $args;

        return $this;
    }

    /**
     * Has argument.
     */
    public
    function hasArgument(string $key): bool
    {
        return \array_key_exists($key, $this->arguments);
    }

    /**
     * ArrayAccess for argument getter.
     *
     * @param string $key Array key
     *
     * @throws \InvalidArgumentException if key does not exist in $this->args
     */
    public
    function offsetGet(mixed $key): mixed
    {
        return $this->getArgument($key);
    }

    /**
     * ArrayAccess for argument setter.
     *
     * @param string $key Array key to set
     */
    public
    function offsetSet(mixed $key, mixed $value): void
    {
        $this->setArgument($key, $value);
    }

    /**
     * ArrayAccess for unset argument.
     *
     * @param string $key Array key
     */
    public
    function offsetUnset(mixed $key): void
    {
        if ($this->hasArgument($key)) {
            unset($this->arguments[$key]);
        }
    }

    /**
     * ArrayAccess has argument.
     *
     * @param string $key Array key
     */
    #[Pure] public
    function offsetExists(mixed $key): bool
    {
        return $this->hasArgument($key);
    }

    /**
     * IteratorAggregate for iterating over the object like an array.
     *
     * @return \ArrayIterator<string, mixed>
     */
    public
    function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->arguments);
    }
}
