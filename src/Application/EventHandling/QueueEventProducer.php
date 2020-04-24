<?php

declare(strict_types=1);

namespace MicroModule\EventQueue\Application\EventHandling;

use MicroModule\EventQueue\Domain\EventHandling\QueueEventInterface;
use Broadway\Serializer\Serializable;
use Enqueue\Client\ProducerInterface;

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
     *
     * @param ProducerInterface   $queueProducer
     * @param QueueEventProcessor $queueEventProcessor
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
     *
     * @param Serializable $event
     */
    public function publishEventToQueue(Serializable $event): void
    {
        $message = ['event' => get_class($event), 'serialize' => $event->serialize()];
        $this->queueProducer->sendEvent($this->queueEventProcessor::getTopic(), $message);
    }
}
