<?php

declare(strict_types=1);

namespace MicroModule\EventQueue\Application\EventHandling;

use Assert\Assertion;
use Assert\AssertionFailedException;
use Broadway\EventHandling\EventBus;
use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\Util\JSON;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Processor;
use MicroModule\EventQueue\Domain\EventHandling\EventFactoryInterface;
use MicroModule\EventQueue\Domain\EventHandling\EventInterface;
use Psr\Log\LoggerInterface;

/**
 * Class QueueEventProcessor.
 *
 * @category Infrastructure\Event\Consumer
 * @SuppressWarnings(PHPMD)
 */
abstract class QueueEventProcessor implements Processor, TopicSubscriberInterface
{
    /**
     * EventBus object.
     *
     * @var EventBus
     */
    private $eventBus;

    /**
     * EventFactory object.
     *
     * @var EventFactoryInterface
     */
    private $eventFactory;

    /**
     * @var LoggerInterface
     */
    private $log;

    /**
     * QueueEventProcessor constructor.
     */
    public function __construct(
        EventBus $eventBus,
        EventFactoryInterface $eventFactory,
        LoggerInterface $log
    ) {
        $this->eventBus = $eventBus;
        $this->eventFactory = $eventFactory;
        $this->log = $log;
    }

    /**
     * Process enqueue message.
     *
     * @return object|string
     */
    public function process(Message $message, Context $context)
    {
        try {
            /** @var EventInterface $event */
            [$type, $event] = $this->makeEvent($message);
            $this->log->info('Receive event:', [$type]);
            $eventStream = $this->eventFactory->makeEventStream($event);
            $this->eventBus->publish($eventStream);

            return self::ACK;
        } catch (\Throwable $e) {
            $this->log->info('Error processing event:', ['exception' => $e]);

            return self::REJECT;
        }
    }

    /**
     * Make EventBus event.
     *
     * @return mixed[]
     *
     * @throws AssertionFailedException
     */
    private function makeEvent(Message $message): array
    {
        $data = JSON::decode($message->getBody());
        Assertion::keyExists($data, 'event');
        Assertion::keyExists($data, 'serialized');
        $eventName = $data['event'];
        $serialized = $data['serialized'];

        if (!is_array($serialized)) {
            $serialized = [$serialized];
        }
        $event = $this->eventFactory->makeEvent($eventName, $serialized);

        return [$eventName, $event];
    }

    /**
     * Return enqueue command routers.
     */
    public static function getSubscribedTopics(): string
    {
        return static::getTopic();
    }

    abstract public static function getTopic(): string;
}
