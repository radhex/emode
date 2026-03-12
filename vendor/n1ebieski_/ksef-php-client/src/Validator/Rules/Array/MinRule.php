<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Validator\Rules\Array;

use InvalidArgumentException;
use N1ebieski\KSEFClient\Validator\Rules\AbstractRule;

final class MinRule extends AbstractRule
{
    public function __construct(
        private readonly int $min
    ) {
    }

    /**
     * @param array<int, mixed> $value
     */
    public function handle(array $value, ?string $attribute = null): void
    {
        if (count($value) < $this->min) {
            throw new InvalidArgumentException(
                $this->getMessage(
                    sprintf('Value must have at least %d elements.', $this->min),
                    $attribute
                )
            );
        }
    }
}
