<?php

declare(strict_types=1);

namespace MicroModule\EventQueue\Domain\EventHandling;

use MicroModule\ValueObject\Identity\UUID;
use Ramsey\Uuid\UuidInterface;

/**
 * EventInterface.
 */
interface EventInterface
{
    /**
     * Return UUID ValueObject.
     */
    public function getUuid(): UuidInterface;
}
