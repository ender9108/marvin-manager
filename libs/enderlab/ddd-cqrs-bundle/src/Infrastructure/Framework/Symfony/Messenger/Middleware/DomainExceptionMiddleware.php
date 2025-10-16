<?php

namespace EnderLab\DddCqrsBundle\Infrastructure\Framework\Symfony\Messenger\Middleware;

use EnderLab\DddCqrsBundle\Domain\Exception\DomainException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final readonly class DomainExceptionMiddleware implements MiddlewareInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        try {
            return $stack->next()->handle($envelope, $stack);
        } catch (DomainException $exception) {
            $this->logger->error(
                'File ' . $exception->getFile() . ' on line ' . $exception->getLine() . $exception->getMessage()
            );

            return $envelope;
        }
    }
}
