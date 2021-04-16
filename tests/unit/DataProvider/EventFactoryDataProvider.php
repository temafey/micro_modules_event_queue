<?php

declare(strict_types=1);

namespace MicroModule\EventQueue\Tests\Unit\DataProvider;

class EventFactoryDataProvider
{
    /**
     * @return mixed[]
     */
    public function getMakeEventReturnsAllowedEventData(): array
    {
        return [
            [
                ['MicroModule\EventQueue\Tests\Unit\DataProvider\SimpleTestEvent' => 'MicroModule\EventQueue\Tests\Unit\DataProvider\SimpleTestEvent'],
                'MicroModule\EventQueue\Tests\Unit\DataProvider\SimpleTestEvent',
                [
                    'data' => [
                        'hello',
                        'it is a test',
                    ],
                ],
                'MicroModule\EventQueue\Tests\Unit\DataProvider\SimpleTestEvent',
            ],
        ];
    }

    /**
     * @return mixed[]
     */
    public function getMakeEventThrowsExceptionIfNotAllowedEventData(): array
    {
        return [
            [
                'MicroModule\EventQueue\Tests\Unit\DataProvider\SimpleTestEventNonExistent',
                [
                    'data' => [
                        'hello',
                        'it is a test',
                    ],
                ],
            ],
        ];
    }
}
