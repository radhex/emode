<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\DTOs;

use N1ebieski\KSEFClient\Support\AbstractDTO;

final class QRCodes extends AbstractDTO
{
    public function __construct(
        public readonly string $code1,
        public readonly ?string $code2 = null,
    ) {
    }
}
