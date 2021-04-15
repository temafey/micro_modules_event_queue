<?php

declare(strict_types=1);

namespace MicroModule\EventQueue\Tests\Unit\DataProvider;

class EventFactoryDataProvider
{
    public function getMakeEventReturnsAllowedEventData(): array
    {
        return [
            [
                ['MicroModule\EventQueue\Tests\Unit\DataProvider\SimpleTestEvent' => 'MicroModule\EventQueue\Tests\Unit\DataProvider\SimpleTestEvent'],
                'MicroModule\EventQueue\Tests\Unit\DataProvider\SimpleTestEvent',
                [
                    'data' => [
                        'hello',
                        'it is a test'
                    ]
                ],
                'MicroModule\EventQueue\Tests\Unit\DataProvider\SimpleTestEvent'
            ]
        ];
    }
}
