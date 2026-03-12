<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Resources\Invoices\Query;

use N1ebieski\KSEFClient\Contracts\HttpClient\HttpClientInterface;
use N1ebieski\KSEFClient\Contracts\HttpClient\ResponseInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Invoices\Query\QueryResourceInterface;
use N1ebieski\KSEFClient\Requests\Invoices\Query\Metadata\MetadataHandler;
use N1ebieski\KSEFClient\Requests\Invoices\Query\Metadata\MetadataRequest;
use N1ebieski\KSEFClient\Resources\AbstractResource;

final class QueryResource extends AbstractResource implements QueryResourceInterface
{
    public function __construct(
        private readonly HttpClientInterface $client
    ) {
    }

    public function metadata(MetadataRequest | array $request): ResponseInterface
    {
        if ($request instanceof MetadataRequest === false) {
            $request = MetadataRequest::from($request);
        }

        return (new MetadataHandler($this->client))->handle($request);
    }
}
