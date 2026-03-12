<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Contracts\Resources\Security;

use N1ebieski\KSEFClient\Requests\Security\PublicKeyCertificates\PublicKeyCertificatesResponse;

interface SecurityResourceInterface
{
    public function publicKeyCertificates(): PublicKeyCertificatesResponse;
}
