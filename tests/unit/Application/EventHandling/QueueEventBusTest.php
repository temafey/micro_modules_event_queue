<?php

declare(strict_types=1);

namespace MicroModule\EventQueue\Tests\Unit\Application\EventHandling;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventBus;
use Broadway\EventHandling\TraceableEventBus;
use Broadway\Serializer\Serializable;
use Enqueue\Client\ProducerInterface;
use Enqueue\Client\TraceableProducer;
use InvalidArgumentException;
use MicroModule\EventQueue\Application\EventHandling\QueueEventBus;
use MicroModule\EventQueue\Application\EventHandling\QueueEventProducer;
use MicroModule\EventQueue\Application\EventHandling\QueueEventProducerException;
use MicroModule\EventQueue\Domain\EventHandling\QueueEventInterface;
use MicroModule\EventQueue\Domain\EventHandling\ShouldQueue;
use MicroModule\EventQueue\Domain\EventHandling\ShouldQueueDuplicate;
use MicroModule\EventQueue\Tests\Unit\DataProvider\QueueableTestEvent;
use MicroModule\EventQueue\Tests\Unit\DataProvider\SimpleTestEvent;
use MicroModule\EventQueue\Tests\Unit\DataProvider\TestEventProcessor;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Event bus that is able to publish events to queue.
 */
class QueueEventBusTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @test
     *
     * @group unit
     *
     * @covers       \MicroModule\EventQueue\Application\EventHandling\QueueEventBus::publish
     *
     * @dataProvider \MicroModule\EventQueue\Tests\Unit\DataProvider\EventQueueDataProvider::getData()
     *
     * @param mixed[] $meta
     * @param mixed[] $payload
     */
    public function simpleEventPublishedToEventBusTest(int $id, int $playhead, array $meta, array $payload): void
    {
        $producerMock = $this->makeProducerMock(0);
        $eventBusMock = $this->makeSimpleBusMock(1);
        $testEventProcessor = $this->makeTestEventProcessorMock();
        $traceableProducer = new TraceableProducer($producerMock);
        $traceableEventBus = new TraceableEventBus($eventBusMock);
        $queueProducer = new QueueEventProducer($traceableProducer, $testEventProcessor);
        $queueEventBus = new QueueEventBus($traceableEventBus, $queueProducer);
        $domainStream = new DomainEventStream([
            new DomainMessage($id, $playhead, new Metadata($meta), new SimpleTestEvent($payload), DateTime::now()),
        ]);
        $traceableEventBus->trace();
        $queueEventBus->publish($domainStream);
        $events = $traceableEventBus->getEvents();

        self::assertCount(1, $events);
    }

    /**
     * @test
     *
     * @group unit
     *
     * @covers       \MicroModule\EventQueue\Application\EventHandling\QueueEventBus::publish
     *
     * @dataProvider \MicroModule\EventQueue\Tests\Unit\DataProvider\EventQueueDataProvider::getData()
     *
     * @param mixed[] $meta
     * @param mixed[] $payload
     */
    public function queueableEventPublishedToEventBusTest(int $id, int $playhead, array $meta, array $payload): void
    {
        $producerMock = $this->makeProducerMock(1);
        $eventBusMock = $this->makeSimpleBusMock(0);
        $testEventProcessor = $this->makeTestEventProcessorMock();
        $traceableProducer = new TraceableProducer($producerMock);
        $traceableEventBus = new TraceableEventBus($eventBusMock);
        $queueProducer = new QueueEventProducer($traceableProducer, $testEventProcessor);
        $queueEventBus = new QueueEventBus($traceableEventBus, $queueProducer);
        $domainStream = new DomainEventStream([
            new DomainMessage($id, $playhead, new Metadata($meta), new QueueableTestEvent($payload), DateTime::now()),
        ]);
        $traceableEventBus->trace();
        $queueEventBus->publish($domainStream);
        $traces = $traceableProducer->getTraces();

        self::assertCount(1, $traces);
        self::assertEquals(QueueableTestEvent::class, $traces[0]['body']['event']);
        self::assertEquals($payload, $traces[0]['body']['serialized']['data']);
    }

    /**
     * Return ProducerInterface mock object.
     *
     * @return MockInterface|ProducerInterface
     */
    protected function makeProducerMock(int $times): MockInterface
    {
        $mock = Mockery::mock(ProducerInterface::class);
        $mock
            ->shouldReceive('sendEvent')
            ->times($times)
            ->andReturn(null);

        return $mock;
    }

    /**
     * Return EventBus mock object.
     *
     * @return MockInterface|EventBus
     */
    protected function makeSimpleBusMock(int $times): MockInterface
    {
        $mock = Mockery::mock(EventBus::class);
        $mock
            ->shouldReceive('publish')
            ->times($times);

        return $mock;
    }

    /**
     * Return EventBus mock object.
     *
     * @return MockInterface|TestEventProcessor
     */
    protected function makeTestEventProcessorMock(): MockInterface
    {
        $mock = Mockery::mock(TestEventProcessor::class);
        $mock
            ->shouldReceive('getTopic')
            ->zeroOrMoreTimes();

        return $mock;
    }

    // =========================================================================
    // VP-2 mode parameter tests
    // =========================================================================

    /**
     * Helper: wrap a payload in a single-event DomainEventStream.
     */
    private function makeStream(object $event): DomainEventStream
    {
        return new DomainEventStream([
            DomainMessage::recordNow('aggregate-id', 0, new Metadata([]), $event),
        ]);
    }

    /**
     * Scenario 1: STRICT + null resolver + ShouldQueue event → QueueEventProducerException.
     *
     * @test
     * @group unit
     */
    public function strictModeThrowsWhenQueueResolverIsNullForShouldQueueEvent(): void
    {
        $event = new class implements ShouldQueue, Serializable {
            public function serialize(): array { return []; }
            public static function deserialize(array $data): static { return new static(); }
        };

        $simpleEventBus = Mockery::mock(EventBus::class);
        $bus = new QueueEventBus(
            simpleEventBus: $simpleEventBus,
            queueResolver: null,
            queueResolverDuplicate: null,
            mode: QueueEventBus::MODE_STRICT,
        );

        $this->expectException(QueueEventProducerException::class);
        $this->expectExceptionMessage('Queue resolver to publish events to queue was not set.');

        $bus->publish($this->makeStream($event));
    }

    /**
     * Scenario 2: STRICT + null resolverDuplicate + ShouldQueueDuplicate event → QueueEventProducerException.
     *
     * @test
     * @group unit
     */
    public function strictModeThrowsWhenQueueResolverDuplicateIsNullForShouldQueueDuplicateEvent(): void
    {
        $event = new class implements ShouldQueueDuplicate, Serializable {
            public function serialize(): array { return []; }
            public static function deserialize(array $data): static { return new static(); }
        };

        $simpleEventBus = Mockery::mock(EventBus::class);
        $bus = new QueueEventBus(
            simpleEventBus: $simpleEventBus,
            queueResolver: null,
            queueResolverDuplicate: null,
            mode: QueueEventBus::MODE_STRICT,
        );

        $this->expectException(QueueEventProducerException::class);
        $this->expectExceptionMessage('Queue resolver to duplicate events to queue was not set.');

        $bus->publish($this->makeStream($event));
    }

    /**
     * Scenario 3: PERMISSIVE + null resolver + ShouldQueue event → no throw, debug logged once.
     *
     * @test
     * @group unit
     */
    public function permissiveModeSkipsShouldQueueEventAndLogsDebugWhenResolverIsNull(): void
    {
        $event = new class implements ShouldQueue, Serializable {
            public function serialize(): array { return []; }
            public static function deserialize(array $data): static { return new static(); }
        };

        /** @var LoggerInterface&MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('debug')
            ->with(
                'ShouldQueue event skipped (permissive mode)',
                $this->arrayHasKey('event_class')
            );

        $simpleEventBus = Mockery::mock(EventBus::class);
        $bus = new QueueEventBus(
            simpleEventBus: $simpleEventBus,
            queueResolver: null,
            queueResolverDuplicate: null,
            mode: QueueEventBus::MODE_PERMISSIVE,
            logger: $logger,
        );

        $bus->publish($this->makeStream($event));
    }

    /**
     * Scenario 4: PERMISSIVE + null resolverDuplicate + ShouldQueueDuplicate event → no throw, debug logged once.
     *
     * @test
     * @group unit
     */
    public function permissiveModeSkipsShouldQueueDuplicateEventAndLogsDebugWhenResolverIsNull(): void
    {
        $event = new class implements ShouldQueueDuplicate, Serializable {
            public function serialize(): array { return []; }
            public static function deserialize(array $data): static { return new static(); }
        };

        /** @var LoggerInterface&MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('debug')
            ->with(
                'ShouldQueueDuplicate event skipped (permissive mode)',
                $this->arrayHasKey('event_class')
            );

        $simpleEventBus = Mockery::mock(EventBus::class);
        // ShouldQueueDuplicate-only events still fall through to local bus (not ShouldQueue, so no `continue`)
        $simpleEventBus->shouldReceive('publish')->once();
        $bus = new QueueEventBus(
            simpleEventBus: $simpleEventBus,
            queueResolver: null,
            queueResolverDuplicate: null,
            mode: QueueEventBus::MODE_PERMISSIVE,
            logger: $logger,
        );

        $bus->publish($this->makeStream($event));
    }

    /**
     * Scenario 5: STRICT + present resolver + ShouldQueue event → resolver->publishEventToQueue() called once.
     *
     * @test
     * @group unit
     */
    public function strictModeCallsResolverPublishEventToQueueWhenResolverIsPresent(): void
    {
        $event = new class implements ShouldQueue, Serializable {
            public function serialize(): array { return []; }
            public static function deserialize(array $data): static { return new static(); }
        };

        /** @var QueueEventInterface&MockObject $resolver */
        $resolver = $this->createMock(QueueEventInterface::class);
        $resolver->expects($this->once())
            ->method('publishEventToQueue')
            ->with($event);

        $simpleEventBus = Mockery::mock(EventBus::class);
        $bus = new QueueEventBus(
            simpleEventBus: $simpleEventBus,
            queueResolver: $resolver,
            queueResolverDuplicate: null,
            mode: QueueEventBus::MODE_STRICT,
        );

        $bus->publish($this->makeStream($event));
    }

    /**
     * Scenario 6: Constructor with invalid mode string → InvalidArgumentException.
     *
     * @test
     * @group unit
     */
    public function constructorThrowsInvalidArgumentExceptionForUnknownMode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid QueueEventBus mode "bogus".');

        $simpleEventBus = Mockery::mock(EventBus::class);
        new QueueEventBus(
            simpleEventBus: $simpleEventBus,
            mode: 'bogus',
        );
    }

    // =========================================================================
    // OBX2-DP $directPublishEnabled parameter tests
    // =========================================================================

    /**
     * Default ctor (no $directPublishEnabled arg) preserves 0.7.x behaviour — resolver is called.
     *
     * @test
     * @group unit
     */
    public function publishesDirectlyWhenFlagOmitted(): void
    {
        $event = new class implements ShouldQueue, Serializable {
            public function serialize(): array { return []; }
            public static function deserialize(array $data): static { return new static(); }
        };

        /** @var QueueEventInterface&MockObject $resolver */
        $resolver = $this->createMock(QueueEventInterface::class);
        $resolver->expects($this->once())
            ->method('publishEventToQueue')
            ->with($event);

        $simpleEventBus = Mockery::mock(EventBus::class);
        $bus = new QueueEventBus(
            simpleEventBus: $simpleEventBus,
            queueResolver: $resolver,
        );

        $bus->publish($this->makeStream($event));
    }

    /**
     * $directPublishEnabled=false: resolver is NOT called even when present; simpleEventBus
     * receives the event (fall-through to $nonQueuedEvents).
     *
     * @test
     * @group unit
     */
    public function skipsDirectPublishWhenFlagFalse(): void
    {
        $event = new class implements ShouldQueue, Serializable {
            public function serialize(): array { return []; }
            public static function deserialize(array $data): static { return new static(); }
        };

        /** @var QueueEventInterface&MockObject $resolver */
        $resolver = $this->createMock(QueueEventInterface::class);
        $resolver->expects($this->never())
            ->method('publishEventToQueue');

        $simpleEventBus = Mockery::mock(EventBus::class);
        $simpleEventBus->shouldReceive('publish')->once();

        $bus = new QueueEventBus(
            simpleEventBus: $simpleEventBus,
            queueResolver: $resolver,
            queueResolverDuplicate: null,
            mode: QueueEventBus::MODE_STRICT,
            logger: null,
            directPublishEnabled: false,
        );

        $bus->publish($this->makeStream($event));
    }

    /**
     * Behaviour-change guard: when $directPublishEnabled=false, the `continue` on the ShouldQueue
     * branch is gated by the outer `if`, so ShouldQueue events fall through to projectors via
     * $simpleEventBus (they no longer bypass the simple bus).
     *
     * @test
     * @group unit
     */
    public function shouldQueueFallsThroughToProjectorsWhenDirectDisabled(): void
    {
        $event = new class implements ShouldQueue, Serializable {
            public function serialize(): array { return []; }
            public static function deserialize(array $data): static { return new static(); }
        };

        /** @var QueueEventInterface&MockObject $resolver */
        $resolver = $this->createMock(QueueEventInterface::class);
        $resolver->expects($this->never())
            ->method('publishEventToQueue');

        /** @var QueueEventInterface&MockObject $resolverDuplicate */
        $resolverDuplicate = $this->createMock(QueueEventInterface::class);
        $resolverDuplicate->expects($this->never())
            ->method('publishEventToQueue');

        // The assertion that PROVES the fall-through: simpleEventBus receives a stream
        // containing exactly 1 ShouldQueue event.
        $simpleEventBus = Mockery::mock(EventBus::class);
        $simpleEventBus->shouldReceive('publish')
            ->once()
            ->with(Mockery::on(function (DomainEventStream $stream): bool {
                $messages = iterator_to_array($stream->getIterator());
                return count($messages) === 1
                    && $messages[0]->getPayload() instanceof ShouldQueue;
            }));

        $bus = new QueueEventBus(
            simpleEventBus: $simpleEventBus,
            queueResolver: $resolver,
            queueResolverDuplicate: $resolverDuplicate,
            mode: QueueEventBus::MODE_STRICT,
            logger: null,
            directPublishEnabled: false,
        );

        $bus->publish($this->makeStream($event));
    }
}
