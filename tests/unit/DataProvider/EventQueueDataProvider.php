<?php

declare(strict_types=1);

namespace MicroModule\EventQueue\Tests\Unit\DataProvider;

/**
 * Class EventQueueDataProvider.
 *
 * @category Tests\Unit\DataProvider
 */
class EventQueueDataProvider
{
    /**
     * Return error data fixture.
     *
     * @return mixed[]
     */
    public function getData(): array
    {
        return [
            [1, 1, [], ['event 1']],
            [2, 1, [], ['event 2']],
        ];
    }
}
