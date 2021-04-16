<?php

declare(strict_types=1);

namespace MicroModule\EventQueue\Tests\Unit\Application\EventHandling;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventBus;
use Broadway\EventHandling\TraceableEventBus;
use Enqueue\Client\ProducerInterface;
use Enqueue\Client\TraceableProducer;
use MicroModule\EventQueue\Application\EventHandling\QueueEventBus;
use MicroModule\EventQueue\Application\EventHandling\QueueEventProducer;
use MicroModule\EventQueue\Tests\Unit\DataProvider\QueueableTestEvent;
use MicroModule\EventQueue\Tests\Unit\DataProvider\SimpleTestEvent;
use MicroModule\EventQueue\Tests\Unit\DataProvider\TestEventProcessor;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

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
        self::assertEquals($payload, $traces[0]['body']['serialize']['data']);
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
}
