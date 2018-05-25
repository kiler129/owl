<?php
declare(strict_types=1);

namespace noFlash\Owl\WebSocket;

use noFlash\Owl\Message\MessageSerializer;
use noFlash\Owl\Message\Protocol\AcknowledgeMessage;
use noFlash\Owl\Message\Protocol\ErrorMessage;
use noFlash\Owl\UseCase\ChannelMessageHandler;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

/**
 * Controller for WebSocket subscriptions
 */
class ChannelController implements MessageComponentInterface
{
    /**
     * @var ChannelMessageHandler
     */
    private $messageHandler;

    /**
     * @var MessageSerializer
     */
    private $messageSerializer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(ChannelMessageHandler $messageHandler, MessageSerializer $messageSerializer, LoggerInterface $logger)
    {
        $this->messageHandler = $messageHandler;
        $this->messageSerializer = $messageSerializer;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    function onOpen(ConnectionInterface $conn)
    {
        //Bloody Ratchet... just expose the raw connection :F
        $connRef = new \ReflectionObject($conn);
        $wrpConnProp = $connRef->getProperty('wrappedConn');
        $wrpConnProp->setAccessible(true);
        $wrpConn = $wrpConnProp->getValue($conn);
        $wrpConnRef = new \ReflectionObject($wrpConn);
        $realConn = $wrpConnRef->getProperty('conn');
        $realConn->setAccessible(true);
        /** @var \React\Socket\ConnectionInterface $realConn */
        $realConn = $realConn->getValue($wrpConn);
        $this->logger->info('Got new connection from {remote} at {local}', ['remote' => $realConn->getRemoteAddress(), 'local' => $realConn->getLocalAddress()]);

        /** @var RequestInterface $handshakeReq */
        $handshakeReq = $conn->httpRequest;
        $path = trim($handshakeReq->getUri()->getPath(), '/');
        if (strpos($path, '/') !== false) {
            $this->logger->warning('Dropping client - invalid path: ' . $path);
            $conn->close();
            return;
        }

        $this->logger->info('Accepted new conn');

        if (!empty($path)) {
            $this->messageHandler->subscribe($path, $conn);
        }
    }

    /**
     * @inheritdoc
     */
    function onClose(ConnectionInterface $conn)
    {
        $this->messageHandler->unsubscribeFromAll($conn);

        $this->logger->info('Conn closed');
    }

    /**
     * @inheritdoc
     */
    function onError(ConnectionInterface $conn, \Exception $e): void
    {
        $this->logger->error('An error has occurred: ' . $e->getMessage());

        $conn->close();
    }

    /**
     * @inheritdoc
     */
    function onMessage(ConnectionInterface $from, $msg): void
    {
        $this->logger->debug('Got new raw msg: ' . $msg);

        try {
            $message = $this->messageSerializer->createFromJson($msg);
        } catch (\Throwable $t) {
            $this->logger->error('Message failed to decode: ' . $t->getMessage());
            $from->send(
                $this->messageSerializer->serializeToJson(
                    (new ErrorMessage(null, 'Failed to decode last message: ' . $t->getMessage()))
                )
            );
            return;
        }

        if ($message->isAckRequired()) {
            $from->send(
                $this->messageSerializer->serializeToJson(
                    AcknowledgeMessage::createFor($message)
                )
            );
        }

        dump($message);
    }
}
