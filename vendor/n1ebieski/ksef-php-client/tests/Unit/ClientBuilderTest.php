<?php

declare(strict_types=1);

use Mockery\MockInterface;
use N1ebieski\KSEFClient\ClientBuilder;
use N1ebieski\KSEFClient\Contracts\Exception\ExceptionHandlerInterface;
use N1ebieski\KSEFClient\Tests\Unit\AbstractTestCase;

/** @var AbstractTestCase $this */

test('If custom exception handler is passed to the client resource', function (): void {
    /** @var AbstractTestCase $this */

    /** @var MockInterface&ExceptionHandlerInterface $exceptionHandler */
    $exceptionHandler = Mockery::mock(ExceptionHandlerInterface::class);

    $client = new ClientBuilder();
    $client->withExceptionHandler($exceptionHandler);

    $clientResource = $client->build();

    $reflection = new ReflectionClass($clientResource);
    $property = $reflection->getProperty('exceptionHandler');

    expect($property->getValue($clientResource))->toBe($exceptionHandler);
});
