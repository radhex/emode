<?php

declare(strict_types=1);

use N1ebieski\KSEFClient\Requests\Sessions\Invoices\Status\StatusRequest;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Error\ErrorResponseFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Sessions\Invoices\Status\StatusRequestFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Sessions\Invoices\Status\StatusResponseFixture;

use function N1ebieski\KSEFClient\Tests\getClientStub;

/**
 * @return array<string, array{StatusRequestFixture, StatusResponseFixture}>
 */
dataset('validResponseProvider', function (): array {
    $requests = [
        new StatusRequestFixture(),
    ];

    $responses = [
        new StatusResponseFixture(),
    ];

    $combinations = [];

    foreach ($requests as $request) {
        foreach ($responses as $response) {
            $combinations["{$request->name}, {$response->name}"] = [$request, $response];
        }
    }

    /** @var array<string, array{StatusRequestFixture, StatusResponseFixture}> */
    return $combinations;
});

test('valid response', function (StatusRequestFixture $requestFixture, StatusResponseFixture $responseFixture): void {
    $clientStub = getClientStub($responseFixture);

    $request = StatusRequest::from($requestFixture->data);

    expect($request)->toBeFixture($requestFixture->data);

    $response = $clientStub->sessions()->invoices()->status($requestFixture->data)->object();

    expect($response)->toBeFixture($responseFixture->data);
})->with('validResponseProvider');

test('invalid response', function (): void {
    $responseFixture = new ErrorResponseFixture();

    expect(function () use ($responseFixture): void {
        $requestFixture = new StatusRequestFixture();

        $clientStub = getClientStub($responseFixture);

        $clientStub->sessions()->invoices()->status($requestFixture->data);
    })->toBeExceptionFixture($responseFixture->data);
});
