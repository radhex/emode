<?php

declare(strict_types=1);

use N1ebieski\KSEFClient\Requests\Sessions\Invoices\KsefUpo\KsefUpoRequest;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Error\ErrorResponseFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Sessions\Invoices\KsefUpo\KsefUpoRequestFixture;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Sessions\Invoices\KsefUpo\KsefUpoResponseFixture;

use function N1ebieski\KSEFClient\Tests\getClientStub;

/**
 * @return array<string, array{KsefUpoRequestFixture, KsefUpoResponseFixture}>
 */
dataset('validResponseProvider', function (): array {
    $requests = [
        new KsefUpoRequestFixture(),
    ];

    $responses = [
        new KsefUpoResponseFixture(),
    ];

    $combinations = [];

    foreach ($requests as $request) {
        foreach ($responses as $response) {
            $combinations["{$request->name}, {$response->name}"] = [$request, $response];
        }
    }

    /** @var array<string, array{KsefUpoRequestFixture, KsefUpoResponseFixture}> */
    return $combinations;
});

test('valid response', function (KsefUpoRequestFixture $requestFixture, KsefUpoResponseFixture $responseFixture): void {
    $clientStub = getClientStub($responseFixture);

    $request = KsefUpoRequest::from($requestFixture->data);

    expect($request)->toBeFixture($requestFixture->data);

    $response = $clientStub->sessions()->invoices()->ksefUpo($requestFixture->data)->body();

    expect($response)->toBe($responseFixture->data);
})->with('validResponseProvider');

test('invalid response', function (): void {
    $responseFixture = new ErrorResponseFixture();

    expect(function () use ($responseFixture): void {
        $requestFixture = new KsefUpoRequestFixture();

        $clientStub = getClientStub($responseFixture);

        $clientStub->sessions()->invoices()->ksefUpo($requestFixture->data);
    })->toBeExceptionFixture($responseFixture->data);
});
