<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Support;

use Symfony\Component\Uid\Uuid;

final class Str
{
    public static function base64URLEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    public static function base64URLDecode(string $value): string
    {
        $padding = 4 - (strlen($value) % 4);

        if ($padding !== 4) {
            $value .= str_repeat('=', $padding);
        }

        return base64_decode(strtr($value, '-_', '+/'));
    }

    public static function snake(string $value): string
    {
        /** @var string $replace */
        $replace = preg_replace('/([a-z])([A-Z])/', '$1_$2', $value);

        return strtolower($replace);
    }

    public static function camel(string $value): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $value))));
    }

    public static function guid(): string
    {
        return 'ID-' . Uuid::v4();
    }
}
