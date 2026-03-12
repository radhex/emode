<?php

declare(strict_types=1);

use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Error\ErrorResponseFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Permissions\Attachments\Status\StatusResponseFixture;
use N1ebieski\KSEFClient\Tests\Unit\AbstractTestCase;

/** @var AbstractTestCase $this */

/**
 * @return array<string, array{StatusResponseFixture}>
 */
dataset('validResponseProvider', function (): array {
    $responses = [
        new StatusResponseFixture(),
    ];

    $combinations = [];

    foreach ($responses as $response) {
        $combinations[$response->name] = [$response];
    }

    /** @var array<string, array{StatusResponseFixture}> */
    return $combinations;
});

test('valid response', function (StatusResponseFixture $responseFixture): void {
    /** @var AbstractTestCase $this */
    $clientStub = $this->createClientStub($responseFixture);

    $response = $clientStub->permissions()->attachments()->status()->object();

    expect($response)->toBeFixture($responseFixture->data);
})->with('validResponseProvider');

test('invalid response', function (): void {
    $responseFixture = new ErrorResponseFixture();

    expect(function () use ($responseFixture): void {
        $clientStub = $this->createClientStub($responseFixture);

        $clientStub->permissions()->attachments()->status();
    })->toBeExceptionFixture($responseFixture->data);
});
