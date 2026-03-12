<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Requests\Security\PublicKeyCertificates;

use N1ebieski\KSEFClient\Contracts\HttpClient\HttpClientInterface;
use N1ebieski\KSEFClient\DTOs\HttpClient\Request;
use N1ebieski\KSEFClient\Requests\AbstractHandler;
use N1ebieski\KSEFClient\ValueObjects\HttpClient\Method;
use N1ebieski\KSEFClient\ValueObjects\HttpClient\Uri;

final class PublicKeyCertificatesHandler extends AbstractHandler
{
    public function __construct(
        private readonly HttpClientInterface $client,
    ) {
    }

    public function handle(): PublicKeyCertificatesResponse
    {
        $response = $this->client
            ->withoutAccessToken()
            ->sendRequest(new Request(
                method: Method::Get,
                uri: Uri::from('security/public-key-certificates')
            ));

        return new PublicKeyCertificatesResponse($response);
    }
}
