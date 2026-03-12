<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\ValueObjects;

use N1ebieski\KSEFClient\Contracts\EnumInterface;
use N1ebieski\KSEFClient\Support\Concerns\HasEquals;

enum Mode: string implements EnumInterface
{
    use HasEquals;

    case Test = 'test';

    case Demo = 'demo';

    case Production = 'production';

    public function getClientAppInvoiceUrl(): Url
    {
        return match ($this) {
            self::Test => Url::from('https://ksef-test.mf.gov.pl/client-app/invoice'),
            self::Demo => Url::from('https://ksef-demo.mf.gov.pl/client-app/invoice'),
            self::Production => Url::from('https://ksef.mf.gov.pl/client-app/invoice'),
        };
    }

    public function getClientAppCertificateUrl(): Url
    {
        return match ($this) {
            self::Test => Url::from('https://ksef-test.mf.gov.pl/client-app/certificate'),
            self::Demo => Url::from('https://ksef-demo.mf.gov.pl/client-app/certificate'),
            self::Production => Url::from('https://ksef.mf.gov.pl/client-app/certificate'),
        };
    }

    public function getApiUrl(): ApiUrl
    {
        return match ($this) {
            self::Test => ApiUrl::from('https://ksef-test.mf.gov.pl/api/v2'),
            self::Demo => ApiUrl::from('https://ksef-demo.mf.gov.pl/api/v2'),
            self::Production => ApiUrl::from('https://ksef.mf.gov.pl/api/v2'),
        };
    }
}
