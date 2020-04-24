<?php

declare(strict_types=1);

namespace MicroModule\EventQueue\Tests\Unit\DataProvider;

use Broadway\Serializer\Serializable;

class SimpleTestEvent implements Serializable
{
    /**
     * @var mixed[]
     */
    private $data;

    /**
     * SimpleTestEvent constructor.
     *
     * @param mixed[] $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * {@inheritdoc}
     */
    public static function deserialize(array $data)
    {
        return new static($data['data']);
    }

    /**
     * {@inheritdoc}
     */
    public function serialize(): array
    {
        return ['data' => $this->data];
    }
}
