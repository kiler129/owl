<?php
declare(strict_types=1);

namespace noFlash\Owl\Message\Protocol;

use noFlash\Owl\Message\AbstractMessage;
use noFlash\Owl\Message\MessageInterface;

final class ErrorMessage extends AbstractMessage
{
    /**
     * @var string In response to what message this was sent
     */
    private $for;

    /**
     * @var string|null
     */
    private $description;

    public function __construct(string $forId = null, string $description = null)
    {
        $this->for = $forId; //This is string since error may happen before MessageInterface object is created
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getFor()
    {
        return $this->for;
    }

    /**
     * @return null|string
     */
    public function getDescription()
    {
        return $this->description;
    }

    public static function getName(): string
    {
        return 'error';
    }
}
