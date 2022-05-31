<?php

declare(strict_types=1);

namespace MicroModule\EventQueue\Application\EventHandling;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventBus as EventBusInterface;
use Broadway\EventHandling\EventListener as EventListenerInterface;
use Exception;
use MicroModule\EventQueue\Application\EventHandling\QueueEventProducerException;
use MicroModule\EventQueue\Domain\EventHandling\QueueEventInterface;
use MicroModule\EventQueue\Domain\EventHandling\ShouldQueue;
use MicroModule\EventQueue\Domain\EventHandling\ShouldQueueDuplicate;

/**
 * This EventBus allows applying events by Projector and produce them into Queue in one flow
 */
class QueueEventBus implements EventBusInterface
{
    /**
     * EventBus object to process events immediately.
     */
    protected EventBusInterface $simpleEventBus;

    /**
     * Queue resolver to send events to queue.
     */
    protected ?QueueEventInterface $queueResolver;

    /**
     * Queue resolver to duplicate events to queue.
     */
    protected ?QueueEventInterface $queueResolverDuplicate;

    public function __construct(
        EventBusInterface $simpleEventBus,
        ?QueueEventInterface $queueResolver = null,
        ?QueueEventInterface $queueResolverDuplicate = null
    ) {
        $this->simpleEventBus = $simpleEventBus;
        $this->queueResolver = $queueResolver;
        $this->queueResolverDuplicate = $queueResolverDuplicate;
    }

    /**
     * {@inheritdoc}
     */
    public function subscribe(EventListenerInterface $eventListener): void
    {
        $this->simpleEventBus->subscribe($eventListener);
    }

    /**
     * @param DomainEventStream<int, DomainMessage> $domainMessages
     *
     * @throws Exception
     *
     * @psalm-suppress InvalidArgument
     */
    public function publish(DomainEventStream $domainMessages): void
    {
        $eventStreamIterator = $domainMessages->getIterator();
        $nonQueuedEvents = [];

        foreach ($eventStreamIterator as $domainMessage) {
            if (false === $domainMessage instanceof DomainMessage) {
                $nonQueuedEvents[] = $domainMessage;

                continue;
            }
            $event = $domainMessage->getPayload();

            /** Determine if the given command should be duplicate to queue  */
            if ($event instanceof ShouldQueueDuplicate) {
                if (null === $this->queueResolverDuplicate) {
                    throw new QueueEventProducerException("Queue resolver to duplicate events to queue was not set.");
                }
                $this->queueResolverDuplicate->publishEventToQueue($event);
            }

            /** Determine if the given command should be queued */
            if ($event instanceof ShouldQueue) {
                if (null === $this->queueResolver) {
                    throw new QueueEventProducerException("Queue resolver to publish events to queue was not set.");
                }
                $this->queueResolver->publishEventToQueue($event);

                continue;
            }
            $nonQueuedEvents[] = $domainMessage;
        }

        if ([] === $nonQueuedEvents) {
            return;
        }
        $eventStream = new DomainEventStream($nonQueuedEvents);
        $this->simpleEventBus->publish($eventStream);
    }
}
