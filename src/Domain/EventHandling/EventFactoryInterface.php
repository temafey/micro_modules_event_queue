<?php

declare(strict_types=1);

namespace MicroModule\EventQueue\Domain\EventHandling;

use Broadway\Domain\DomainEventStream;

/**
 * Factory events.
 */
interface EventFactoryInterface
{
    /**
     * Make and return event.
     *
     * @param string  $eventName
     * @param mixed[] $serialized
     *
     * @return EventInterface
     */
    public function makeEvent(string $eventName, array $serialized): EventInterface;

    /**
     * Make and return event stream aggregator.
     *
     * @param EventInterface $event
     *
     * @return DomainEventStream
     */
    public function makeEventStream(EventInterface $event): DomainEventStream;
}
