<?php

namespace N1ebieski\KSEFClient\Tests;

use DateTimeImmutable;
use DateTimeInterface;
use Mockery;
use N1ebieski\KSEFClient\Contracts\HttpClient\HttpClientInterface;
use N1ebieski\KSEFClient\Contracts\Resources\ClientResourceInterface;
use N1ebieski\KSEFClient\Contracts\ValueAwareInterface;
use N1ebieski\KSEFClient\DTOs\Config;
use N1ebieski\KSEFClient\Exceptions\ExceptionHandler;
use N1ebieski\KSEFClient\Exceptions\HttpClient\BadRequestException;
use N1ebieski\KSEFClient\Factories\EncryptionKeyFactory;
use N1ebieski\KSEFClient\HttpClient\Response;
use N1ebieski\KSEFClient\Resources\ClientResource;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\AbstractResponseFixture;
use N1ebieski\KSEFClient\Tests\AbstractTestCase;
use N1ebieski\KSEFClient\ValueObjects\HttpClient\BaseUri;
use N1ebieski\KSEFClient\ValueObjects\Mode;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

uses(AbstractTestCase::class)->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

/**
* @param array<string, mixed> $data
*/
expect()->extend('toBeFixture', $toFixture = function (array $data, ?object $object = null) use (&$toFixture): void {
    if ($object === null) {
        $object = $this->value; //@phpstan-ignore-line
    }

    foreach ($data as $key => $value) {
        expect($object)->toHaveProperty($key);

        if (is_array($value) && is_array($object->{$key}) && isset($object->{$key}[0]) && is_object($object->{$key}[0])) {
            foreach ($object->{$key} as $itemKey => $itemValue) {
                if (is_string($value[$itemKey])) {
                    $value[$itemKey] = ['value' => $value[$itemKey]];
                }

                /**
                 * @var array<string, array<string, mixed>> $value
                 * @var string $itemKey
                 * @var object $itemValue
                 */
                $toFixture($value[$itemKey], $itemValue);
            }

            continue;
        }

        if (is_array($value) && is_object($object->{$key})) {
            /** @var array<string, mixed> $value */
            $toFixture($value, $object->{$key});

            continue;
        }

        $expected = match (true) {
            //@phpstan-ignore-next-line
            $object->{$key} instanceof DateTimeInterface => new DateTimeImmutable($value),
            //@phpstan-ignore-next-line
            $object->{$key} instanceof ValueAwareInterface && $object->{$key}->value instanceof DateTimeInterface => new DateTimeImmutable($value),
            default => $value,
        };

        $actual = match (true) {
            $object->{$key} instanceof DateTimeInterface => $object->{$key},
            $object->{$key} instanceof ValueAwareInterface => $object->{$key}->value,
            default => $object->{$key},
        };

        expect($actual)->toEqual($expected);
    }
});

expect()->extend('toBeExceptionFixture', function (array $data): void {
    /** @var array{exception: array{exceptionCode: string, exceptionDescription: string, exceptionDetailList: array<array{exceptionCode: string, exceptionDescription: string}>}} $data */
    $firstException = $data['exception']['exceptionDetailList'][0];

    //@phpstan-ignore-next-line
    expect($this->value)->toThrow(new BadRequestException(
        message: "{$firstException['exceptionCode']} {$firstException['exceptionDescription']}",
        code: 400,
        context: (object) $data
    ));
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function getClientStub(AbstractResponseFixture $response): ClientResourceInterface
{
    $streamStub = Mockery::mock(StreamInterface::class);
    $streamStub->shouldReceive('getContents')->andReturn($response->toContents());

    $responseStub = Mockery::mock(ResponseInterface::class);
    $responseStub->shouldReceive('getStatusCode')->andReturn($response->statusCode);
    $responseStub->shouldReceive('getBody')->andReturn($streamStub);

    $httpClientStub = Mockery::mock(HttpClientInterface::class);
    $httpClientStub->shouldReceive('withAccessToken')->andReturnSelf();
    $httpClientStub->shouldReceive('withEncryptedKey')->andReturnSelf();

    /** @var ResponseInterface $responseStub */
    $httpClientStub->shouldReceive('sendRequest')->andReturn(new Response($responseStub, new ExceptionHandler()));

    /** @var HttpClientInterface $httpClientStub */
    return new ClientResource($httpClientStub, new Config(
        baseUri: new BaseUri(Mode::Test->getApiUrl()->value),
        encryptionKey: EncryptionKeyFactory::makeRandom()
    ));
}
