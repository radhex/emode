<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Requests\Certificates\Retrieve;

use N1ebieski\KSEFClient\Contracts\BodyInterface;
use N1ebieski\KSEFClient\Requests\AbstractRequest;
use N1ebieski\KSEFClient\ValueObjects\CertificateSerialNumber;
use N1ebieski\KSEFClient\Support\Concerns\HasToBody;

final class RetrieveRequest extends AbstractRequest implements BodyInterface
{
    use HasToBody;

    /**
     * @param array<int, CertificateSerialNumber> $certificateSerialNumbers
     */
    public function __construct(
        public readonly array $certificateSerialNumbers,
    ) {
    }
}
