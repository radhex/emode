<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Contracts\Resources\Testdata;

use N1ebieski\KSEFClient\Contracts\Resources\Testdata\Person\PersonResourceInterface;

interface TestdataResourceInterface
{
    public function person(): PersonResourceInterface;
}
