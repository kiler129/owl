<?php
declare(strict_types=1);

namespace noFlash\Owl\Message\Protocol;

use noFlash\Owl\Message\AbstractMessage;
use noFlash\Owl\Message\MessageInterface;

final class AcknowledgeMessage extends AbstractMessage
{
    /**
     * @var string In response to what message this was sent
     */
    private $for;

    private function __construct()
    {
        //noop - ACK can be only created from another message with ID
    }

    public static function createFor(MessageInterface $message): self
    {
        $m = new self();
        $m->for = $message->getId();

        return $m;
    }

    public function isAckRequired(): bool
    {
        return false; //It would be stupid to set it to true ;)
    }

    /**
     * @return string
     */
    public function getFor()
    {
        return $this->for;
    }

    public static function getName(): string
    {
        return 'ack';
    }
}
