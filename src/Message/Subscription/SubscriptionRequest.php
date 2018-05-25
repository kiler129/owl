<?php
declare(strict_types=1);

namespace noFlash\Owl\Message\Subscription;

use noFlash\Owl\Message\AbstractMessage;

class SubscriptionRequest extends AbstractMessage
{
    /**
     * @var string
     */
    private $channelName;

    /**
     * @inheritdoc
     */
    protected $ackRequired = true;

    public function __construct(string $channelName)
    {
        if ($channelName === '') {
            throw new \LogicException('You cannot create subscription request with empty channel name');
        }

        $this->channelName = $channelName;
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'ch-sub';
    }
}
