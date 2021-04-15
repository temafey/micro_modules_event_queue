<?php

declare(strict_types=1);

namespace MicroModule\EventQueue\Domain\EventHandling;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;

/**
 * Factory events.
 */
interface EventFactoryInterface
{
    /**
     * Make and return event.
     *
     * @param mixed[] $serialized
     */
    public function makeEvent(string $eventName, array $serialized): EventInterface;

    /**
     * Make and return event stream aggregator.
     *
     * @return DomainEventStream<int, DomainMessage>
     */
    public function makeEventStream(EventInterface $event): DomainEventStream;
}
