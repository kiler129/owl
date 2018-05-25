<?php
declare(strict_types=1);

namespace noFlash\Owl\UseCase;

use noFlash\Owl\Message\ChannelMessage;
use Psr\Log\LoggerInterface;
use Ratchet\ConnectionInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\TransportInterface;

class ChannelMessageHandler
{
    /**
     * @var \SplObjectStorage[]
     */
    private $channels = [];

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function subscribe(string $channel, ConnectionInterface $connection): bool
    {
        $this->logger->debug(
            'Subscribing {conn} to {channel} channel',
            ['conn' => \spl_object_hash($connection), 'channel' => $channel]
        );

        if (!isset($this->channels[$channel])) {
            $this->channels[$channel] = new \SplObjectStorage();
        }

        if ($this->channels[$channel]->contains($connection)) {
            return false; //Already subscribed
        }

        $this->channels[$channel]->attach($connection);

        return true;
    }

    public function unsubscribe(string $channel, ConnectionInterface $connection): bool
    {
        $this->logger->debug(
            'Unsubscribing {conn} from {channel} channel',
            ['conn' => \spl_object_hash($connection), 'channel' => $channel]
        );

        if (!isset($this->channels[$channel])) {
            return false; //No such channel
        }

        if (!$this->channels[$channel]->contains($connection)) {
            return false; //Not subscribed
        }

        $this->channels[$channel]->detach($connection);

        return true;
    }

    public function unsubscribeFromAll(ConnectionInterface $connection): void
    {
        foreach ($this->channels as $name => $channel) {
            if ($channel->contains($connection)) {
                $this->unsubscribe($name, $connection);
            }
        }
    }

    public function __invoke(ChannelMessage $message)
    {
        $messageChannels = $message->getChannels();
        $this->logger->debug(
            'Dispatching {type} message to [{channels}] channels',
            ['type' => \get_class($message), 'channels' => implode(', ', $message->getChannels())]
        );

        $serializedMessage = $message->serialize();

        foreach ($messageChannels as $channelName) {
            if (!isset($this->channels[$channelName])) {
                continue; //No subscriptions (or no channel at all)
            }

            /** @var ConnectionInterface $channel */
            foreach ($this->channels[$channelName] as $connection) {
                $this->logger->debug(
                    'Sending to {connection} on {channel} channel',
                    ['connection' => \spl_object_hash($connection), 'channel' => $connection]
                );

                $channel->send($serializedMessage);
            }
        }
    }
}
