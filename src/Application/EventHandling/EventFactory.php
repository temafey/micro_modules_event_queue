<?php

declare(strict_types=1);

namespace MicroModule\EventQueue\Application\EventHandling;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use MicroModule\EventQueue\Domain\EventHandling\EventFactoryInterface;
use MicroModule\EventQueue\Domain\EventHandling\EventInterface;

/**
 * Class EventFactory.
 */
class EventFactory implements EventFactoryInterface
{
    /**
     * Allowed events in format eventKey => FQCN.
     *
     * @var array<string, string>
     */
    protected array $allowedEvents;

    /**
     * EventFactory constructor.
     *
     * @param array<string, string> $allowedEvents
     */
    public function __construct(array $allowedEvents = [])
    {
        $this->allowedEvents = $allowedEvents;
    }

    /**
     * Make and return event.
     *
     * @param mixed[] $serialized
     *
     * @throws EventDoesNotExist
     */
    public function makeEvent(string $eventName, array $serialized): EventInterface
    {
        if (!isset($this->allowedEvents[$eventName])) {
            throw new EventDoesNotExist(sprintf('Event \'%s\' doesn\'t exist.', $eventName));
        }
        $event = $this->allowedEvents[$eventName];

        return $event::deserialize($serialized);
    }

    /**
     * Make and return event stream aggregator.
     *
     * @return DomainEventStream<int, DomainMessage>
     */
    public function makeEventStream(EventInterface $event): DomainEventStream
    {
        $domainMessage = DomainMessage::recordNow($event->getUuid()->toString(), 1, new Metadata([]), $event);

        return new DomainEventStream([$domainMessage]);
    }
}
