<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Resources\Certificates;

use N1ebieski\KSEFClient\Contracts\HttpClient\HttpClientInterface;
use N1ebieski\KSEFClient\Contracts\HttpClient\ResponseInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Certificates\CertificatesResourceInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Certificates\Enrollments\EnrollmentsResourceInterface;
use N1ebieski\KSEFClient\Requests\Certificates\Limits\LimitsHandler;
use N1ebieski\KSEFClient\Requests\Certificates\Query\QueryHandler;
use N1ebieski\KSEFClient\Requests\Certificates\Query\QueryRequest;
use N1ebieski\KSEFClient\Requests\Certificates\Retrieve\RetrieveHandler;
use N1ebieski\KSEFClient\Requests\Certificates\Retrieve\RetrieveRequest;
use N1ebieski\KSEFClient\Requests\Certificates\Revoke\RevokeHandler;
use N1ebieski\KSEFClient\Requests\Certificates\Revoke\RevokeRequest;
use N1ebieski\KSEFClient\Resources\AbstractResource;
use N1ebieski\KSEFClient\Resources\Certificates\Enrollments\EnrollmentsResource;

final class CertificatesResource extends AbstractResource implements CertificatesResourceInterface
{
    public function __construct(
        private readonly HttpClientInterface $client
    ) {
    }

    public function limits(): ResponseInterface
    {
        return (new LimitsHandler($this->client))->handle();
    }

    public function enrollments(): EnrollmentsResourceInterface
    {
        return new EnrollmentsResource($this->client);
    }

    public function query(QueryRequest | array $request): ResponseInterface
    {
        if ($request instanceof QueryRequest === false) {
            $request = QueryRequest::from($request);
        }

        return (new QueryHandler($this->client))->handle($request);
    }

    public function revoke(RevokeRequest | array $request): ResponseInterface
    {
        if ($request instanceof RevokeRequest === false) {
            $request = RevokeRequest::from($request);
        }

        return (new RevokeHandler($this->client))->handle($request);
    }

    public function retrieve(RetrieveRequest | array $request): ResponseInterface
    {
        if ($request instanceof RetrieveRequest === false) {
            $request = RetrieveRequest::from($request);
        }

        return (new RetrieveHandler($this->client))->handle($request);
    }
}
