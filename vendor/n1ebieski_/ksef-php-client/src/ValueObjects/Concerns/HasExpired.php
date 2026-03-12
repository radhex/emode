<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\ValueObjects\Concerns;

use DateTimeImmutable;
use DateTimeZone;
use DateTimeInterface;

/**
 * @property-read DateTimeInterface | null $validUntil
 */
trait HasExpired
{
    public function isExpired(): bool
    {
        return $this->validUntil instanceof DateTimeInterface
            && $this->validUntil < new DateTimeImmutable('-1 minute', timezone: new DateTimeZone('UTC'));
    }
}
