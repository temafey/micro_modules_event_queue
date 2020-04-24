<?php

declare(strict_types=1);

namespace MicroModule\EventQueue\Application\EventHandling;

use MicroModule\Base\Domain\Exception\CriticalException;

/**
 * Class EventDoesNotExists\.
 */
class EventDoesNotExists extends CriticalException
{
}
