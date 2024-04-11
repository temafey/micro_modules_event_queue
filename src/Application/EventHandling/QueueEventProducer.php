<?php

declare(strict_types=1);

namespace MicroModule\EventQueue\Application\EventHandling;

use Broadway\Serializer\Serializable;
use Enqueue\Client\ProducerInterface;
use MicroModule\EventQueue\Domain\EventHandling\QueueEventInterface;

/**
 * Class QueueEventProducer.
 *
 * @category Infrastructure\Query\Queue
 */
class QueueEventProducer implements QueueEventInterface
{
    protected const FIELD_EVENT = 'event';
    protected const FIELD_SERIALIZED = 'serialized';

    protected ProducerInterface $queueProducer;

    /**
     * Queue topic name to producer events.
     */
    protected string|QueueEventProcessor $topic;

    public function __construct(
        ProducerInterface $queueProducer,
        string|QueueEventProcessor $topic
    ) {
        $this->queueProducer = $queueProducer;
        $this->topic = $topic;
    }

    /**
     * Publish event to queue.
     */
    public function publishEventToQueue(Serializable $event): void
    {
        $topicName = ($this->topic instanceof QueueEventProcessor) ? $this->topic::getTopic() : $this->topic;
        $message = $event->serialize();
        array_walk_recursive($message, function (&$value) {
            if (! is_scalar($value) && $value !== null) {
                if ($value instanceof \DateTimeInterface) {
                    $value = $value->format(\DateTime::ATOM);
                }
            }
        });
        $this->queueProducer->sendEvent(
            $topicName,
            [
                self::FIELD_EVENT => get_class($event),
                self::FIELD_SERIALIZED => $message,
            ]
        );
    }
}
