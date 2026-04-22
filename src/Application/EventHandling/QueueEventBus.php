<?php

declare(strict_types=1);

namespace MicroModule\EventQueue\Application\EventHandling;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventBus as EventBusInterface;
use Broadway\EventHandling\EventListener as EventListenerInterface;
use InvalidArgumentException;
use MicroModule\EventQueue\Domain\EventHandling\QueueEventInterface;
use MicroModule\EventQueue\Domain\EventHandling\ShouldQueue;
use MicroModule\EventQueue\Domain\EventHandling\ShouldQueueDuplicate;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * This EventBus allows applying events by Projector and producing them into a queue in one flow.
 *
 * Mode semantics:
 *  - MODE_STRICT (default)  : throws QueueEventProducerException if a required queue resolver is null.
 *                             Preserves legacy V1 behavior — fails fast on misconfiguration.
 *  - MODE_PERMISSIVE (opt-in): skips the publish silently with a DEBUG log entry when the
 *                             resolver is null. Intended for outbox-only projects where the
 *                             outbox worker is the sole publisher and the bus is wired only for
 *                             non-queued side-effects.
 */
final class QueueEventBus implements EventBusInterface
{
    /** Throw on misconfigured queue resolvers (preserves V1 behavior). */
    public const string MODE_STRICT = 'strict';

    /** Skip publish silently when the queue resolver is null (opt-in). */
    public const string MODE_PERMISSIVE = 'permissive';

    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly EventBusInterface $simpleEventBus,
        private readonly ?QueueEventInterface $queueResolver = null,
        private readonly ?QueueEventInterface $queueResolverDuplicate = null,
        private readonly string $mode = self::MODE_STRICT,
        ?LoggerInterface $logger = null,
        private readonly bool $directPublishEnabled = true,
    ) {
        if (!in_array($mode, [self::MODE_STRICT, self::MODE_PERMISSIVE], true)) {
            throw new InvalidArgumentException(
                sprintf('Invalid QueueEventBus mode "%s". Use MODE_STRICT or MODE_PERMISSIVE.', $mode)
            );
        }
        $this->logger = $logger ?? new NullLogger();
    }

    public function subscribe(EventListenerInterface $eventListener): void
    {
        $this->simpleEventBus->subscribe($eventListener);
    }

    public function publish(DomainEventStream $domainMessages): void
    {
        $nonQueuedEvents = [];

        foreach ($domainMessages->getIterator() as $domainMessage) {
            if (!$domainMessage instanceof DomainMessage) {
                $nonQueuedEvents[] = $domainMessage;

                continue;
            }

            $event = $domainMessage->getPayload();

            if ($this->directPublishEnabled && $event instanceof ShouldQueueDuplicate) {
                if ($this->queueResolverDuplicate !== null) {
                    $this->queueResolverDuplicate->publishEventToQueue($event);
                } elseif ($this->mode === self::MODE_STRICT) {
                    throw new QueueEventProducerException(
                        'Queue resolver to duplicate events to queue was not set.'
                    );
                } else {
                    $this->logger->debug('ShouldQueueDuplicate event skipped (permissive mode)', [
                        'event_class' => $event::class,
                    ]);
                }
            }

            if ($this->directPublishEnabled && $event instanceof ShouldQueue) {
                if ($this->queueResolver !== null) {
                    $this->queueResolver->publishEventToQueue($event);

                    continue;
                } elseif ($this->mode === self::MODE_STRICT) {
                    throw new QueueEventProducerException(
                        'Queue resolver to publish events to queue was not set.'
                    );
                } else {
                    $this->logger->debug('ShouldQueue event skipped (permissive mode)', [
                        'event_class' => $event::class,
                    ]);

                    continue;
                }
            }

            $nonQueuedEvents[] = $domainMessage;
        }

        if ([] !== $nonQueuedEvents) {
            $this->simpleEventBus->publish(new DomainEventStream($nonQueuedEvents));
        }
    }
}
