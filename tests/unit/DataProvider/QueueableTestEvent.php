<?php

declare(strict_types=1);

namespace MicroModule\EventQueue\Tests\Unit\DataProvider;

use Broadway\Serializer\Serializable;
use MicroModule\EventQueue\Domain\EventHandling\ShouldQueue;

class QueueableTestEvent implements Serializable, ShouldQueue
{
    /**
     * @var mixed[]
     */
    private $data;

    /**
     * QueueableTestEvent constructor.
     *
     * @param mixed[] $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function deserialize(array $data)
    {
        return new self($data['data']);
    }

    /**
     * @return array<string, mixed>
     */
    public function serialize(): array
    {
        return ['data' => $this->data];
    }
}
