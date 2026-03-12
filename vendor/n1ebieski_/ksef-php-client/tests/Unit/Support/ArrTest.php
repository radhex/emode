<?php

declare(strict_types=1);

use N1ebieski\KSEFClient\Support\Arr;
use N1ebieski\KSEFClient\Support\Optional;

test('filter recursive', function (): void {
    $array = [
        'a' => 'b',
        'c' => [
            'd' => new Optional(),
            'f' => [
                'g' => 'h',
                'i' => [
                    'j' => new Optional(),
                ],
            ],
        ],
    ];

    $expectedArray = [
        'a' => 'b',
        'c' => [
            'f' => [
                'g' => 'h'
            ],
        ],
    ];

    $result = Arr::filterRecursive($array, fn (mixed $value): bool => ! $value instanceof Optional);

    expect($result)->toBe($expectedArray);
});
