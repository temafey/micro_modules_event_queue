<?php

declare(strict_types=1);

namespace MicroModule\EventQueue\Tests\Unit\DataProvider;

use MicroModule\EventQueue\Application\EventHandling\QueueEventProcessor;

/**
 * Class TestEventProcessor.
 */
class TestEventProcessor extends QueueEventProcessor
{
    /**
     * @return string
     */
    public static function getTopic(): string
    {
        return 'yes';
    }
}
