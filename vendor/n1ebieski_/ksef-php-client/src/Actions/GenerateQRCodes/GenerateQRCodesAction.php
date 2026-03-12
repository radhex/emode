<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Actions\GenerateQRCodes;

use DateTimeInterface;
use N1ebieski\KSEFClient\Actions\AbstractAction;
use N1ebieski\KSEFClient\DTOs\Requests\Auth\ContextIdentifierGroup;
use N1ebieski\KSEFClient\ValueObjects\Certificate;
use N1ebieski\KSEFClient\ValueObjects\CertificateSerialNumber;
use N1ebieski\KSEFClient\ValueObjects\Mode;
use N1ebieski\KSEFClient\ValueObjects\NIP;
use N1ebieski\KSEFClient\ValueObjects\Requests\KsefNumber;

final class GenerateQRCodesAction extends AbstractAction
{
    public function __construct(
        public readonly NIP $nip,
        public readonly DateTimeInterface $invoiceCreatedAt,
        public readonly string $document,
        public readonly Mode $mode = Mode::Production,
        public readonly ?KsefNumber $ksefNumber = null,
        public readonly ?Certificate $certificate = null,
        public readonly ?CertificateSerialNumber $certificateSerialNumber = null,
        public readonly ?ContextIdentifierGroup $contextIdentifierGroup = null
    ) {
    }
}
