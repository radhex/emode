<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Resources\Testdata;

use N1ebieski\KSEFClient\Contracts\HttpClient\HttpClientInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Testdata\Person\PersonResourceInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Testdata\TestdataResourceInterface;
use N1ebieski\KSEFClient\Resources\AbstractResource;
use N1ebieski\KSEFClient\Resources\Testdata\Person\PersonResource;

final class TestdataResource extends AbstractResource implements TestdataResourceInterface
{
    public function __construct(
        private readonly HttpClientInterface $client
    ) {
    }

    public function person(): PersonResourceInterface
    {
        return new PersonResource($this->client);
    }
}
