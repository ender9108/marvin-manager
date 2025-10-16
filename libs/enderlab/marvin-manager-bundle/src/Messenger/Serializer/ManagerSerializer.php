<?php
namespace EnderLab\MarvinManagerBundle\Messenger\Serializer;

use EnderLab\MarvinManagerBundle\Reference\ManagerActionReference;
use EnderLab\MarvinManagerBundle\Messenger\ManagerRequestCommand;
use EnderLab\MarvinManagerBundle\Messenger\ManagerResponseCommand;
use Exception;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Stamp\AckStamp;
use Symfony\Component\Messenger\Stamp\BusNameStamp;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ManagerSerializer implements SerializerInterface
{
    /**
     * @throws InvalidArgumentException
     */
    public function __construct(
        private readonly ValidatorInterface $validator,
    ) {
    }

    /**
     * @throws Exception
     */
    public function decode(array $encodedEnvelope): Envelope
    {
        $body = json_decode($encodedEnvelope['body'], true, 512, JSON_THROW_ON_ERROR);
        $headers = $encodedEnvelope['headers'];

        $this->validateEnvelope($body, $headers);

        $messageType = $this->handlers[$headers['type']] ?? null;

        if (null === $messageType) {
            throw new MessageDecodingFailedException(sprintf('Message class not found (type: %s)', $headers['type']));
        }

        $messageClass = match (true) {
            $messageType === ManagerActionReference::ACTION_START_DOCKER->value => ManagerRequestCommand::class,
        };

        $message = new $messageClass($body);
        $violations = $this->validator->validate($message);

        if (count($violations) > 0) {
            $message = '';

            foreach ($violations as $violation) {
                $message .= $violation->getMessage() . "\n";
            }

            throw new ValidationFailedException($message, $violations);
        }

        return new Envelope($message);
    }

    /**
     * @throws Exception
     */
    public function encode(Envelope $envelope): array
    {
        /** @var ManagerResponseCommand|ManagerRequestCommand $message */
        $message = $envelope->getMessage();

        $violations = $this->validator->validate($message);

        if (count($violations) > 0) {
            $message = '';

            foreach ($violations as $violation) {
                $message .= $violation->getMessage() . "\n";
            }

            throw new ValidationFailedException($message, $violations);
        }

        $envelope = match (true) {
            $message instanceof ManagerResponseCommand => $envelope->withoutStampsOfType(BusNameStamp::class),
            $message instanceof ManagerRequestCommand => $envelope->withoutStampsOfType(AckStamp::class),
            default => throw new Exception(sprintf('Invalid message type %s', get_class($message))),
        };

        return [
            'body' => json_encode([
                'containerId' => $message->containerId,
                'correlationId' => $message->correlationId,
                'action' => $message->action,
                'command' => $message->command,
                'args' => $message->args,
                'timeout' => $message->timeout,
            ]),
            'headers' => [
                'type' => $message->action,
                'stamps' => serialize($envelope->all())
            ],
        ];
    }

    /**
     * @throws Exception
     */
    private function validateEnvelope(array $message, array $headers): void
    {
        if (
            !isset($headers['type'])
            || !in_array($headers['type'], ManagerActionReference::values(), true)
        ) {
            throw new MessageDecodingFailedException('Unsupported message type.');
        }
    }
}
