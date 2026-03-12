<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Requests\Invoices\Exports\Init;

use N1ebieski\KSEFClient\Contracts\BodyInterface;
use N1ebieski\KSEFClient\DTOs\Requests\Invoices\Exports\Filters;
use N1ebieski\KSEFClient\Requests\AbstractRequest;

final class InitRequest extends AbstractRequest implements BodyInterface
{
    public function __construct(
        public readonly Filters $filters,
    ) {
    }

    public function toBody(): array
    {
        return [
            'filters' => $this->filters->toArray()
        ];
    }
}
