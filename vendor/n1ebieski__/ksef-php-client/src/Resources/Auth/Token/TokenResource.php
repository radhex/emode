<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Resources\Auth\Token;

use N1ebieski\KSEFClient\Contracts\HttpClient\HttpClientInterface;
use N1ebieski\KSEFClient\Contracts\HttpClient\ResponseInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Auth\Token\TokenResourceInterface;
use N1ebieski\KSEFClient\Requests\Auth\Token\Redeem\RedeemHandler;
use N1ebieski\KSEFClient\Requests\Auth\Token\Refresh\RefreshHandler;
use N1ebieski\KSEFClient\Resources\AbstractResource;

final class TokenResource extends AbstractResource implements TokenResourceInterface
{
    public function __construct(
        private readonly HttpClientInterface $client
    ) {
    }

    public function redeem(): ResponseInterface
    {
        return (new RedeemHandler($this->client))->handle();
    }

    public function refresh(): ResponseInterface
    {
        return (new RefreshHandler($this->client))->handle();
    }
}
