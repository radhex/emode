<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Resources\Auth;

use N1ebieski\KSEFClient\Actions\ConvertEcdsaDerToRaw\ConvertEcdsaDerToRawHandler;
use N1ebieski\KSEFClient\Actions\SignDocument\SignDocumentHandler;
use N1ebieski\KSEFClient\Contracts\HttpClient\HttpClientInterface;
use N1ebieski\KSEFClient\Contracts\HttpClient\ResponseInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Auth\AuthResourceInterface;
use N1ebieski\KSEFClient\Contracts\Resources\Auth\Token\TokenResourceInterface;
use N1ebieski\KSEFClient\Requests\Auth\Challenge\ChallengeHandler;
use N1ebieski\KSEFClient\Requests\Auth\KsefToken\KsefTokenHandler;
use N1ebieski\KSEFClient\Requests\Auth\KsefToken\KsefTokenRequest;
use N1ebieski\KSEFClient\Requests\Auth\Status\StatusHandler;
use N1ebieski\KSEFClient\Requests\Auth\Status\StatusRequest;
use N1ebieski\KSEFClient\Requests\Auth\XadesSignature\XadesSignatureHandler;
use N1ebieski\KSEFClient\Requests\Auth\XadesSignature\XadesSignatureRequest;
use N1ebieski\KSEFClient\Requests\Auth\XadesSignature\XadesSignatureXmlRequest;
use N1ebieski\KSEFClient\Resources\AbstractResource;
use N1ebieski\KSEFClient\Resources\Auth\Token\TokenResource;

final class AuthResource extends AbstractResource implements AuthResourceInterface
{
    public function __construct(
        private readonly HttpClientInterface $client
    ) {
    }

    public function challenge(): ResponseInterface
    {
        return (new ChallengeHandler($this->client))->handle();
    }

    public function xadesSignature(XadesSignatureRequest | XadesSignatureXmlRequest | array $request): ResponseInterface
    {
        if (is_array($request)) {
            $request = XadesSignatureRequest::from($request);
        }

        return (new XadesSignatureHandler(
            client: $this->client,
            signDocument: new SignDocumentHandler(new ConvertEcdsaDerToRawHandler())
        ))->handle($request);
    }

    public function ksefToken(KsefTokenRequest | array $request): ResponseInterface
    {
        if ($request instanceof KsefTokenRequest === false) {
            $request = KsefTokenRequest::from($request);
        }

        return (new KsefTokenHandler($this->client))->handle($request);
    }

    public function status(StatusRequest | array $request): ResponseInterface
    {
        if ($request instanceof StatusRequest === false) {
            $request = StatusRequest::from($request);
        }

        return (new StatusHandler($this->client))->handle($request);
    }

    public function token(): TokenResourceInterface
    {
        return new TokenResource($this->client);
    }
}
