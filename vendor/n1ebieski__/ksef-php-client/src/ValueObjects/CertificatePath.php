<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\ValueObjects;

use N1ebieski\KSEFClient\Support\AbstractValueObject;
use N1ebieski\KSEFClient\Validator\Rules\File\ExistsRule;
use N1ebieski\KSEFClient\Validator\Rules\File\ExtensionsRule;
use N1ebieski\KSEFClient\Validator\Validator;

final class CertificatePath extends AbstractValueObject
{
    public readonly string $path;

    public function __construct(
        string $path,
        public readonly ?string $passphrase = null
    ) {
        Validator::validate($path, [
            new ExistsRule(),
            new ExtensionsRule(['p12']),
        ]);

        $this->path = $path;
    }

    public static function from(string $path, ?string $passphrase = null): self
    {
        return new self($path, $passphrase);
    }
}
