<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Resources\Invoices;

use N1ebieski\KSEFClient\Contracts\Exception\ExceptionHandlerInterface;
use N1ebieski\KSEFClient\Contracts\HttpClient\HttpClientInterface;
use N1ebieski\KSEFClient\Contracts\HttpClient\ResponseInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Invoices\InvoicesResourceInterface;
use N1ebieski\KSEFClient\DTOs\Config;
use N1ebieski\KSEFClient\Requests\Invoices\Download\DownloadHandler;
use N1ebieski\KSEFClient\Requests\Invoices\Download\DownloadRequest;
use N1ebieski\KSEFClient\Resources\AbstractResource;
use N1ebieski\KSEFClient\Resources\Invoices\Exports\ExportsResource;
use N1ebieski\KSEFClient\Resources\Invoices\Query\QueryResource;
use Throwable;

final class InvoicesResource extends AbstractResource implements InvoicesResourceInterface
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly Config $config,
        private readonly ExceptionHandlerInterface $exceptionHandler
    ) {
    }

    public function download(DownloadRequest | array $request): ResponseInterface
    {
        try {
            if ($request instanceof DownloadRequest === false) {
                $request = DownloadRequest::from($request);
            }

            return (new DownloadHandler($this->client))->handle($request);
        } catch (Throwable $throwable) {
            throw $this->exceptionHandler->handle($throwable);
        }
    }

    public function query(): QueryResource
    {
        try {
            return new QueryResource($this->client, $this->exceptionHandler);
        } catch (Throwable $throwable) {
            throw $this->exceptionHandler->handle($throwable);
        }
    }

    public function exports(): ExportsResource
    {
        try {
            return new ExportsResource($this->client, $this->config, $this->exceptionHandler);
        } catch (Throwable $throwable) {
            throw $this->exceptionHandler->handle($throwable);
        }
    }
}
