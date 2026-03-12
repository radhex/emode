<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Requests\Security\PublicKeyCertificates;

use DateTimeImmutable;
use N1ebieski\KSEFClient\Contracts\HttpClient\ResponseInterface;
use N1ebieski\KSEFClient\ValueObjects\Requests\Security\PublicKeyCertificates\PublicKeyCertificateUsage;
use N1ebieski\KSEFClient\ValueObjects\Support\KeyType;
use Psr\Http\Message\ResponseInterface as BaseResponseInterface;

final class PublicKeyCertificatesResponse implements ResponseInterface
{
    public readonly BaseResponseInterface $baseResponse;

    public function __construct(private readonly ResponseInterface $response)
    {
        $this->baseResponse = $response->baseResponse;
    }

    public function getFirstByPublicKeyCertificateUsage(PublicKeyCertificateUsage $type): ?string
    {
        /** @var array<int, object{certificate: string, validFrom: string, validTo: string, usage: array<int, string>}> $certificates */
        $certificates = $this->object();

        foreach ($certificates as $certificate) {
            if ( ! in_array($type->value, $certificate->usage)) {
                continue;
            }

            if (new DateTimeImmutable($certificate->validTo) < new DateTimeImmutable()) {
                continue;
            }

            if (new DateTimeImmutable($certificate->validFrom) > new DateTimeImmutable()) {
                continue;
            }

            return $certificate->certificate;
        }

        return null;
    }

    public function status(): int
    {
        return $this->response->status();
    }

    public function json(): array
    {
        return $this->response->json();
    }

    public function object(): object | array
    {
        return $this->response->object();
    }

    public function body(): string
    {
        return $this->response->body();
    }

    public function toArray(KeyType $keyType = KeyType::Camel, array $only = []): array
    {
        return $this->response->toArray($keyType, $only);
    }
}
