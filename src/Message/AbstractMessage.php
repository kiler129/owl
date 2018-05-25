<?php
declare(strict_types=1);

namespace noFlash\Owl\Message;

abstract class AbstractMessage implements MessageInterface
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var bool
     */
    protected $ackRequired = false;

    public function getId(): string
    {
        if ($this->id === null) {
            $this->setId(uniqid(static::getName(), true));
        }

        return $this->id;
    }

    public function setId(string $id): void
    {
        if ($this->id !== null) {
            throw new \RuntimeException('Message ID was already set - it cannot be changed');
        }

        if (empty($id)) {
            throw new \InvalidArgumentException('You cannot set empty ID');
        }

        $this->id = $id;
    }

    public function setAckRequired(bool $ack): void
    {
        $this->ackRequired = $ack;
    }

    public function isAckRequired(): bool
    {
        return $this->ackRequired;
    }
}
