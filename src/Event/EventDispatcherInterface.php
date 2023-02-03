<?php

namespace QApi\Event;

interface EventDispatcherInterface extends \Psr\EventDispatcher\EventDispatcherInterface
{
    /**
     * Adds an event listener that listens on the specified events.
     *
     * @param int $priority The higher this value, the earlier an event
     *                      listener will be triggered in the chain (defaults to 0)
     */
    public function addListener(string $eventName, callable $listener, int $priority = 0);

    /**
     * Adds an event subscriber.
     *
     * The subscriber is asked for all the events it is
     * interested in and added as a listener for these events.
     */
    public function addSubscriber(EventSubscriberInterface $subscriber);

    /**
     * Removes an event listener from the specified events.
     */
    public function removeListener(string $eventName, callable $listener);

    /**
     * @param EventSubscriberInterface $subscriber
     */
    public function removeSubscriber(EventSubscriberInterface $subscriber);

    /**
     * Gets the listeners of a specific event or all listeners sorted by descending priority.
     *
     * @param string|null $eventName
     * @return array
     */
    public function getListeners(string $eventName = null): array;

    /**
     * Calls the listeners
     * @param iterable $listeners
     * @param string $eventName
     * @param object $event
     */
    public function callListeners(iterable $listeners, string $eventName, object $event);

    /**
     * Gets the listener priority for a specific event.
     *
     * Returns null if the event or the listener does not exist.
     */
    public function getListenerPriority(string $eventName, callable $listener): ?int;

    /**
     * Checks whether an event has any registered listeners.
     */
    public function hasListeners(string $eventName = null): bool;
}