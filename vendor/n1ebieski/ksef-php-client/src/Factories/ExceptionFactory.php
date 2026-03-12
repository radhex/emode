<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Factories;

use N1ebieski\KSEFClient\Exceptions\HttpClient\BadRequestException;
use N1ebieski\KSEFClient\Exceptions\HttpClient\ClientException;
use N1ebieski\KSEFClient\Exceptions\HttpClient\Exception;
use N1ebieski\KSEFClient\Exceptions\HttpClient\InternalServerException;
use N1ebieski\KSEFClient\Exceptions\HttpClient\RateLimitException;
use N1ebieski\KSEFClient\Exceptions\HttpClient\ServerException;
use N1ebieski\KSEFClient\Exceptions\HttpClient\UnknownSystemException;
use N1ebieski\KSEFClient\Factories\AbstractFactory;
use N1ebieski\KSEFClient\Support\Utility;

final class ExceptionFactory extends AbstractFactory
{
    /**
     * @param null|object{exception?: object{exceptionDetailList: array<int, object{exceptionCode: int, exceptionDescription: string}>}, status?: object{code: int, description: string, details: array<int, string>}, message?: string} $exceptionResponse
     */
    public static function make(
        int $statusCode,
        ?object $exceptionResponse
    ): Exception {
        $message = match (true) {
            isset($exceptionResponse->exception) => self::getExceptionMessage($exceptionResponse),
            isset($exceptionResponse->status) => self::getStatusMessage($exceptionResponse),
            isset($exceptionResponse->message) => $exceptionResponse->message,
            default => null
        };

        /** @var class-string<Exception> $exceptionNamespace */
        $exceptionNamespace = match (true) {
            $statusCode === 400 => BadRequestException::class,
            $statusCode === 500 => InternalServerException::class,
            $statusCode === 501 => UnknownSystemException::class,
            $statusCode === 401 => Utility::value(function () use (&$message): string {
                $message ??= 'Unauthorized';

                return ClientException::class;
            }),
            $statusCode === 404 => Utility::value(function () use (&$message): string {
                $message ??= 'Not found';

                return ClientException::class;
            }),
            $statusCode === 429 => RateLimitException::class,
            $statusCode > 400 && $statusCode < 500 => ClientException::class,
            $statusCode > 500 => ServerException::class,
            default => Exception::class
        };

        return new $exceptionNamespace(
            message: $message ?? '',
            code: $statusCode,
            context: $exceptionResponse
        );
    }

    /**
     *
     * @param object{status: object{code: int, description: string, details: array<int, string>}} $exceptionResponse
     */
    private static function getStatusMessage(object $exceptionResponse): string
    {
        return "{$exceptionResponse->status->code} {$exceptionResponse->status->description}";
    }

    /**
     * @param object{exception: object{exceptionDetailList: array<int, object{exceptionCode: int, exceptionDescription: string}>}} $exceptionResponse
     */
    private static function getExceptionMessage(object $exceptionResponse): ?string
    {
        $exceptions = $exceptionResponse->exception->exceptionDetailList;

        $firstException = $exceptions[0] ?? null;

        if ($firstException !== null) {
            return "{$firstException->exceptionCode} {$firstException->exceptionDescription}";
        }

        return null;
    }
}
