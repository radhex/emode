<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Exceptions;

use N1ebieski\KSEFClient\Contracts\Exception\ExceptionHandlerInterface;
use Psr\Log\LoggerInterface;

final class ExceptionHandler implements ExceptionHandlerInterface
{
    public function __construct(
        private readonly ?LoggerInterface $logger = null
    ) {
    }

    public function handle(AbstractException $exception): void
    {
        if ($this->logger instanceof LoggerInterface) {
            $this->logger->error($exception->getMessage(), $exception->toArray());
        }

        throw $exception;
    }
}
