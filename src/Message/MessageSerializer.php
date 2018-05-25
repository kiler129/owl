<?php
declare(strict_types=1);

namespace noFlash\Owl\Message;

use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\CircularReferenceException;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Converts message arrays to actual objects
 */
class MessageSerializer
{
    private $messageTypes = [];

    /**
     * @var Serializer
     */
    private $serializer;

    public function __construct()
    {
        $this->serializer = $this->createSerializer();
    }

    private function createSerializer()
    {
        $jsonEncoder = new JsonEncoder();
        $nameNormalizer = new class extends ObjectNormalizer {
            /**
             * @inheritdoc
             */
            public function normalize($object, $format = null, array $context = [])
            {
                return ['name' => \get_class($object)::getName()] + parent::normalize($object, $format, $context);
            }
        };

        $serializer = new Serializer([$nameNormalizer], [$jsonEncoder]);

        return $serializer;
    }

    final public function addType(string $name, string $fqcn): void
    {
        if (isset($this->messageTypes[$name])) {
            throw new \LogicException(
                sprintf(
                    'Cannot add message named "%s" with class "%s" - that name is already registered with class "%s"',
                    $name,
                    $fqcn,
                    $this->messageTypes[$name]
                )
            );
        }

        $this->messageTypes[$name] = $fqcn;
    }

    public function createFromJson(string $json): MessageInterface
    {
        $decoded = $this->serializer->decode($json, JsonEncoder::FORMAT);
        if (empty($decoded['name'])) {
            throw new NotEncodableValueException('Message has no name - cannot determine denormalization target');
        }

        if (!isset($this->messageTypes[$decoded['name']])) {
            throw new NotEncodableValueException(
                sprintf('Message name "%s" does not match any type', $decoded['name'])
            );
        }

        return $this->serializer->denormalize(
            $decoded,
            $this->messageTypes[$decoded['name']],
            JsonEncoder::FORMAT,
            ['allow_extra_attributes' => false]
        );
    }

    public function serializeToJson(MessageInterface $message): string
    {
        return $this->serializer->serialize($message, JsonEncoder::FORMAT);
    }
}
