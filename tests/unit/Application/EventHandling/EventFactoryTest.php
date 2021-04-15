<?php

declare(strict_types=1);

namespace MicroModule\EventQueue\Tests\Unit\Application\EventHandling;

use MicroModule\EventQueue\Application\EventHandling\EventDoesNotExist;
use MicroModule\EventQueue\Application\EventHandling\EventFactory;
use PHPUnit\Framework\TestCase;

class EventFactoryTest extends TestCase
{
    /**
     * @group unit
     *
     * @covers       \MicroModule\EventQueue\Application\EventHandling\EventFactory::makeEvent
     *
     * @dataProvider \MicroModule\EventQueue\Tests\Unit\DataProvider\EventFactoryDataProvider::getMakeEventReturnsAllowedEventData
     *
     * @param mixed[] $allowedEvents
     * @param mixed[] $serialized
     */
    public function testMakeEventReturnsAllowedEvent(
        array $allowedEvents,
        string $eventName,
        array $serialized,
        string $expectedEvent
    ): void {
        $eventFactory = new EventFactory($allowedEvents);
        $event = $eventFactory->makeEvent($eventName, $serialized);

        self::assertInstanceOf($expectedEvent, $event);
    }

    /**
     * @group unit
     *
     * @covers \MicroModule\EventQueue\Application\EventHandling\EventFactory::makeEvent
     *
     * @dataProvider \MicroModule\EventQueue\Tests\Unit\DataProvider\EventFactoryDataProvider::getMakeEventReturnsAllowedEventData
     *
     * @param mixed[] $allowedEvents
     * @param mixed[] $serialized
     */
    public function testMakeEventThrowsExceptionIfNotAllowedEvent(
        array $allowedEvents,
        string $eventName,
        array $serialized,
        string $expectedEvent
    ): void {
        $eventFactory = new EventFactory();

        $this->expectException(EventDoesNotExist::class);
        $eventFactory->makeEvent($eventName, $serialized);
    }
}
