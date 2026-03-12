<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Support;

use Closure;
use RuntimeException;

final class Utility
{
    public static function retry(Closure $closure, int $backoff = 10, int $retryUntil = 120): mixed
    {
        $seconds = 0;

        while (true) {
            $result = $closure();

            if ($result !== null) {
                return $result;
            }

            $seconds += $backoff;

            if ($seconds > $retryUntil) {
                throw new RuntimeException("Operation did not return a result after retrying for {$retryUntil} seconds.");
            }

            sleep($backoff);
        }
    }

    /**
     * Get the path to the base of the install.
     */
    public static function basePath(string $path = ''): string
    {
        return Utility::normalizePath(__DIR__ . '/../../' . $path);
    }

    /**
     * Get normalized path, like realpath() for non-existing path or file
     */
    public static function normalizePath(string $path): string
    {
        /** @var string */
        return array_reduce(explode('/', $path), function ($a, $b) {
            if ($a === null) {
                $a = "/";
            }

            if ($b === "" || $b === ".") {
                return $a;
            }

            if ($b === "..") {
                return dirname($a); //@phpstan-ignore-line
            }

            return preg_replace("/\/+/", "/", "{$a}/{$b}"); //@phpstan-ignore-line
        });
    }

    /**
     * Return the default value of the given value.
     *
     * @template TValue
     * @template TArgs
     *
     * @param TValue|Closure(TArgs):TValue $value
     * @param  TArgs  ...$args
     * @return TValue
     */
    public static function value($value, ...$args)
    {
        return $value instanceof Closure ? $value(...$args) : $value;
    }
}
