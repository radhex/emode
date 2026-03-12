<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Resources\Certificates\Enrollments;

use N1ebieski\KSEFClient\Contracts\HttpClient\HttpClientInterface;
use N1ebieski\KSEFClient\Contracts\HttpClient\ResponseInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Certificates\Enrollments\EnrollmentsResourceInterface;
use N1ebieski\KSEFClient\Requests\Certificates\Enrollments\Data\DataHandler;
use N1ebieski\KSEFClient\Requests\Certificates\Enrollments\Send\SendHandler;
use N1ebieski\KSEFClient\Requests\Certificates\Enrollments\Send\SendRequest;
use N1ebieski\KSEFClient\Requests\Certificates\Enrollments\Status\StatusHandler;
use N1ebieski\KSEFClient\Requests\Certificates\Enrollments\Status\StatusRequest;
use N1ebieski\KSEFClient\Resources\AbstractResource;

final class EnrollmentsResource extends AbstractResource implements EnrollmentsResourceInterface
{
    public function __construct(
        private readonly HttpClientInterface $client
    ) {
    }

    public function data(): ResponseInterface
    {
        return (new DataHandler($this->client))->handle();
    }

    public function send(SendRequest | array $request): ResponseInterface
    {
        if ($request instanceof SendRequest === false) {
            $request = SendRequest::from($request);
        }

        return (new SendHandler($this->client))->handle($request);
    }

    public function status(StatusRequest | array $request): ResponseInterface
    {
        if ($request instanceof StatusRequest === false) {
            $request = StatusRequest::from($request);
        }

        return (new StatusHandler($this->client))->handle($request);
    }
}
