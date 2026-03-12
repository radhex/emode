<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Validator\Rules\Date;

use DateTimeInterface;
use InvalidArgumentException;
use N1ebieski\KSEFClient\Validator\Rules\AbstractRule;

final class AfterRule extends AbstractRule
{
    public function __construct(private readonly DateTimeInterface $after)
    {
    }

    public function handle(DateTimeInterface $value, ?string $attribute = null): void
    {
        if ($value < $this->after) {
            throw new InvalidArgumentException(
                $this->getMessage(
                    sprintf('Date must be after %s.', $this->after->format('Y-m-d H:i:s')),
                    $attribute
                )
            );
        }
    }
}
