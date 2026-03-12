<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Testing\Fixtures\Requests\Sessions\Invoices\KsefUpo;

use Override;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\AbstractResponseFixture;

final class KsefUpoResponseFixture extends AbstractResponseFixture
{
    public int $statusCode = 200;

    public string $data = 'upo';

    #[Override]
    public function toContents(): string
    {
        return $this->data;
    }
}
