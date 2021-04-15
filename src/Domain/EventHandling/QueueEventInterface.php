<?php

declare(strict_types=1);

namespace MicroModule\EventQueue\Domain\EventHandling;

use Broadway\Serializer\Serializable;

/**
 * Simple synchronous publishing of events.
 */
interface QueueEventInterface
{
    /**
     * Publish event to queue.
     */
    public function publishEventToQueue(Serializable $event): void;
}
