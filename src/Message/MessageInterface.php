<?php
declare(strict_types=1);

namespace noFlash\Owl\Message;

/**
 * @todo Add JSON schema
 */
interface MessageInterface
{
    public function getId(): string;
    public function isAckRequired(): bool;
    public static function getName(): string;
}
