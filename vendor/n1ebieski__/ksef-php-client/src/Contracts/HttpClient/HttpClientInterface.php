<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Contracts\HttpClient;

use N1ebieski\KSEFClient\Contracts\HttpClient\ResponseInterface;
use N1ebieski\KSEFClient\DTOs\HttpClient\Request;
use N1ebieski\KSEFClient\ValueObjects\AccessToken;
use N1ebieski\KSEFClient\ValueObjects\Requests\Sessions\EncryptedKey;

interface HttpClientInterface
{
    public function sendRequest(Request $request): ResponseInterface;

    public function withAccessToken(AccessToken $accessToken): self;

    public function withEncryptedKey(EncryptedKey $encryptedKey): self;
}
