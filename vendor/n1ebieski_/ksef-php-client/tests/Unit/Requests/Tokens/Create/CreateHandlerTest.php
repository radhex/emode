<?php

declare(strict_types=1);

use N1ebieski\KSEFClient\Requests\Tokens\Create\CreateRequest;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Error\ErrorResponseFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Tokens\Create\CreateRequestFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Tokens\Create\CreateResponseFixture;

use function N1ebieski\KSEFClient\Tests\getClientStub;

/**
 * @return array<string, array{CreateRequestFixture, CreateResponseFixture}>
 */
dataset('validResponseProvider', function (): array {
    $requests = [
        new CreateRequestFixture(),
    ];

    $responses = [
        new CreateResponseFixture(),
    ];

    $combinations = [];

    foreach ($requests as $request) {
        foreach ($responses as $response) {
            $combinations["{$request->name}, {$response->name}"] = [$request, $response];
        }
    }

    /** @var array<string, array{CreateRequestFixture, CreateResponseFixture}> */
    return $combinations;
});

test('valid response', function (CreateRequestFixture $requestFixture, CreateResponseFixture $responseFixture): void {
    $clientStub = getClientStub($responseFixture);

    $request = CreateRequest::from($requestFixture->data);

    expect($request)->toBeFixture($requestFixture->data);

    $response = $clientStub->tokens()->create($requestFixture->data)->object();

    expect($response)->toBeFixture($responseFixture->data);
})->with('validResponseProvider');

test('invalid response', function (): void {
    $responseFixture = new ErrorResponseFixture();

    expect(function () use ($responseFixture): void {
        $requestFixture = new CreateRequestFixture();

        $clientStub = getClientStub($responseFixture);

        $clientStub->tokens()->create($requestFixture->data);
    })->toBeExceptionFixture($responseFixture->data);
});
