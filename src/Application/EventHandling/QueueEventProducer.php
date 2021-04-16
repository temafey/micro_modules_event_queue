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
    /**
     * Queue producer.
     *
     * @var ProducerInterface
     */
    private $queueProducer;

    /**
     * @var QueueEventProcessor
     */
    private $queueEventProcessor;

    /**
     * QueueEventProducer constructor.
     */
    public function __construct(
        ProducerInterface $queueProducer,
        QueueEventProcessor $queueEventProcessor
    ) {
        $this->queueProducer = $queueProducer;
        $this->queueEventProcessor = $queueEventProcessor;
    }

    /**
     * Send job event to queue.
     */
    public function publishEventToQueue(Serializable $event): void
    {
        $message = ['event' => get_class($event), 'serialize' => $event->serialize()];
        $this->queueProducer->sendEvent($this->queueEventProcessor::getTopic(), $message);
    }
}
