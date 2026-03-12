<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Resources\Testdata\Person;

use N1ebieski\KSEFClient\Contracts\HttpClient\HttpClientInterface;
use N1ebieski\KSEFClient\Contracts\HttpClient\ResponseInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Testdata\Person\PersonResourceInterface;
use N1ebieski\KSEFClient\Requests\Testdata\Person\Create\CreateHandler;
use N1ebieski\KSEFClient\Requests\Testdata\Person\Create\CreateRequest;
use N1ebieski\KSEFClient\Requests\Testdata\Person\Remove\RemoveHandler;
use N1ebieski\KSEFClient\Requests\Testdata\Person\Remove\RemoveRequest;
use N1ebieski\KSEFClient\Resources\AbstractResource;

final class PersonResource extends AbstractResource implements PersonResourceInterface
{
    public function __construct(
        private readonly HttpClientInterface $client
    ) {
    }

    public function create(CreateRequest | array $request): ResponseInterface
    {
        if ($request instanceof CreateRequest === false) {
            $request = CreateRequest::from($request);
        }

        return (new CreateHandler($this->client))->handle($request);
    }

    public function remove(RemoveRequest | array $request): ResponseInterface
    {
        if ($request instanceof RemoveRequest === false) {
            $request = RemoveRequest::from($request);
        }

        return (new RemoveHandler($this->client))->handle($request);
    }
}
