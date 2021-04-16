<?php

declare(strict_types=1);

namespace MicroModule\EventQueue\Application\EventHandling;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventBus;
use Broadway\EventHandling\EventListener;
use MicroModule\EventQueue\Domain\EventHandling\QueueEventInterface;
use MicroModule\EventQueue\Domain\EventHandling\ShouldQueue;

/**
 * Event bus that is able to publish events to queue.
 */
final class QueueEventBus implements EventBus
{
    /**
     * EventBus object to process events immediately.
     *
     * @var EventBus
     */
    private $eventBus;

    /**
     * Queue resolver to send events to queue.
     *
     * @var QueueEventInterface
     */
    private $queueResolver;

    /**
     * QueueEventBus constructor.
     */
    public function __construct(EventBus $simpleEventBus, QueueEventInterface $queueResolver)
    {
        $this->eventBus = $simpleEventBus;
        $this->queueResolver = $queueResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function subscribe(EventListener $eventListener): void
    {
        $this->eventBus->subscribe($eventListener);
    }

    /**
     * @param DomainEventStream<int, DomainMessage> $domainMessages
     *
     * @psalm-suppress InvalidArgument
     */
    public function publish(DomainEventStream $domainMessages): void
    {
        $eventStreamIterator = $domainMessages->getIterator();
        $nonQueuedEvents = [];

        foreach ($eventStreamIterator as $domainMessage) {
            if (!$domainMessage instanceof DomainMessage) {
                $nonQueuedEvents[] = $domainMessage;

                continue;
            }
            $event = $domainMessage->getPayload();
            //Determine if the given command should be queued.
            if ($event instanceof ShouldQueue) {
                $this->queueResolver->publishEventToQueue($event);

                continue;
            }
            $nonQueuedEvents[] = $domainMessage;
        }
        if (!empty($nonQueuedEvents)) {
            $eventStream = new DomainEventStream($nonQueuedEvents);
            $this->eventBus->publish($eventStream);
        }
    }
}
