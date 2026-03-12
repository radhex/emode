<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Contracts\Exception;

use N1ebieski\KSEFClient\Exceptions\AbstractException;

interface ExceptionHandlerInterface
{
    public function handle(AbstractException $exception): void;
}
