<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\DTOs\Requests\Invoices;

use N1ebieski\KSEFClient\Support\AbstractDTO;
use N1ebieski\KSEFClient\Support\Optional;
use N1ebieski\KSEFClient\ValueObjects\Requests\Invoices\DateRangeFrom;
use N1ebieski\KSEFClient\ValueObjects\Requests\Invoices\DateRangeTo;
use N1ebieski\KSEFClient\ValueObjects\Requests\Invoices\DateType;

final class DateRange extends AbstractDTO
{
    public function __construct(
        public readonly DateType $dateType,
        public readonly DateRangeFrom $from,
        public readonly Optional | DateRangeTo $to = new Optional(),
    ) {
    }
}
