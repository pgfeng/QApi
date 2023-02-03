<?php

namespace QApi\Event;


use Psr\EventDispatcher\StoppableEventInterface;

class EventDispatcher implements EventDispatcherInterface
{
    /**
     * @var array
     */
    private array $listeners = [];
    /**
     * @var array
     */
    private array $sorted = [];

    public function dispatch(object $event, string $eventName = null): object
    {
        $eventName ??= $event::class;
        $listeners = $this->getListeners($eventName);
        if ($listeners) {
            $this->callListeners($listeners, $eventName, $event);
        }
        return $event;
    }

    /**
     * @param string $eventName
     * @param callable|array $listener
     * @param int $priority
     * @return void
     */
    public function addListener(string $eventName, callable|array $listener, int $priority = 0)
    {
        $this->listeners[$eventName][$priority][] = $listener;
        unset($this->sorted[$eventName]);
    }

    /**
     * @param EventSubscriberInterface $subscriber
     * @return void
     */
    public function addSubscriber(EventSubscriberInterface $subscriber)
    {
        foreach ($subscriber->getSubscribedEvents() as $eventName => $params) {
            if (\is_string($params)) {
                $this->addListener($eventName, [$subscriber, $params]);
            } elseif (\is_string($params[0])) {
                $this->addListener($eventName, [$subscriber, $params[0]], $params[1] ?? 0);
            } else {
                foreach ($params as $listener) {
                    $this->addListener($eventName, [$subscriber, $listener[0]], $listener[1] ?? 0);
                }
            }
        }
    }

    /**
     * @param EventSubscriberInterface $subscriber
     * @return void
     */
    public function removeSubscriber(EventSubscriberInterface $subscriber)
    {
        foreach ($subscriber->getSubscribedEvents() as $eventName => $params) {
            if (\is_array($params) && \is_array($params[0])) {
                foreach ($params as $listener) {
                    $this->removeListener($eventName, [$subscriber, $listener[0]]);
                }
            } else {
                $this->removeListener($eventName, [$subscriber, \is_string($params) ? $params : $params[0]]);
            }
        }
    }


    /**
     * @param string|null $eventName
     * @return array
     */
    public function getListeners(string $eventName = null): array
    {
        if (null !== $eventName) {
            if (empty($this->listeners[$eventName])) {
                return [];
            }

            if (!isset($this->sorted[$eventName])) {
                $this->sortListeners($eventName);
            }

            return $this->sorted[$eventName];
        }

        foreach ($this->listeners as $eventName => $eventListeners) {
            if (!isset($this->sorted[$eventName])) {
                $this->sortListeners($eventName);
            }
        }
        return array_filter($this->sorted);
    }

    /**
     * Sorts the internal list of listeners for the given event by priority.
     */
    private function sortListeners(string $eventName)
    {
        krsort($this->listeners[$eventName]);
        $this->sorted[$eventName] = [];
        foreach ($this->listeners[$eventName] as &$listeners) {
            foreach ($listeners as &$listener) {
                if (\is_array($listener) && isset($listener[0]) && $listener[0] instanceof \Closure && 2 >= \count($listener)) {
                    $listener[0] = $listener[0]();
                    $listener[1] ??= '__invoke';
                }
                $this->sorted[$eventName][] = $listener;
            }
        }
    }

    public function getListenerPriority(string $eventName, callable $listener): ?int
    {
        if (empty($this->listeners[$eventName])) {
            return null;
        }

        if (\is_array($listener) && isset($listener[0]) && $listener[0] instanceof \Closure && 2 >= \count($listener)) {
            $listener[0] = $listener[0]();
            $listener[1] ??= '__invoke';
        }

        foreach ($this->listeners[$eventName] as $priority => &$listeners) {
            foreach ($listeners as &$v) {
                if ($v !== $listener && \is_array($v) && isset($v[0]) && $v[0] instanceof \Closure && 2 >= \count($v)) {
                    $v[0] = $v[0]();
                    $v[1] ??= '__invoke';
                }
                if ($v === $listener || ($listener instanceof \Closure && $v == $listener)) {
                    return $priority;
                }
            }
        }

        return null;
    }

    public function hasListeners(string $eventName = null): bool
    {
        if (null !== $eventName) {
            return !empty($this->listeners[$eventName]);
        }
        foreach ($this->listeners as $eventListeners) {
            if ($eventListeners) {
                return true;
            }
        }
        return false;
    }

    public function callListeners(iterable $listeners, string $eventName, object $event)
    {
        $stoppable = $event instanceof StoppableEventInterface;
        foreach ($listeners as $listener) {
            if ($stoppable && $event->isPropagationStopped()) {
                break;
            }
            $listener($event, $eventName, $this);
        }
    }

    /**
     * @param string $eventName
     * @param callable|array $listener
     * @return void
     */
    public function removeListener(string $eventName, callable|array $listener)
    {
        if (empty($this->listeners[$eventName])) {
            return;
        }

        if (\is_array($listener) && isset($listener[0]) && $listener[0] instanceof \Closure && 2 >= \count($listener)) {
            $listener[0] = $listener[0]();
            $listener[1] ??= '__invoke';
        }

        foreach ($this->listeners[$eventName] as $priority => &$listeners) {
            foreach ($listeners as $k => &$v) {
                if ($v !== $listener && \is_array($v) && isset($v[0]) && $v[0] instanceof \Closure && 2 >= \count($v)) {
                    $v[0] = $v[0]();
                    $v[1] ??= '__invoke';
                }
                if ($v === $listener || ($listener instanceof \Closure && $v == $listener)) {
                    unset($listeners[$k], $this->sorted[$eventName]);
                }
            }

            if (!$listeners) {
                unset($this->listeners[$eventName][$priority]);
            }
        }
    }
}