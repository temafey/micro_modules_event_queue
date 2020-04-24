<?php

declare(strict_types=1);

namespace MicroModule\EventQueue\Application\EventHandling;

use MicroModule\EventQueue\Domain\EventHandling\EventFactoryInterface;
use MicroModule\EventQueue\Domain\EventHandling\EventInterface;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;

/**
 * Class EventFactory.
 */
class EventFactory implements EventFactoryInterface
{
    /**
     * Make and return event.
     *
     * @param string  $eventName
     * @param mixed[] $serialized
     *
     * @return EventInterface
     *
     * @throws EventDoesNotExists
     */
    public function makeEvent(string $eventName, array $serialized): EventInterface
    {
        if (!class_exists($eventName)) {
            throw new EventDoesNotExists(sprintf('Event \'%s\' doesn\'t exists.', $eventName));
        }

        return $eventName::deserialize($serialized);
    }

    /**
     * Make and return event stream aggregator.
     *
     * @param EventInterface $event
     *
     * @return DomainEventStream
     */
    public function makeEventStream(EventInterface $event): DomainEventStream
    {
        $domainMessage = DomainMessage::recordNow($event->getUuid()->toString(), 1, new Metadata([]), $event);

        return new DomainEventStream([$domainMessage]);
    }
}
