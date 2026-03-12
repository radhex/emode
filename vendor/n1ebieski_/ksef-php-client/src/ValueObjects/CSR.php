<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\ValueObjects;

use N1ebieski\KSEFClient\Support\AbstractValueObject;
use OpenSSLAsymmetricKey;

final class CSR extends AbstractValueObject
{
    public function __construct(
        public readonly string $raw,
        public readonly OpenSSLAsymmetricKey $privateKey
    ) {
    }
}
