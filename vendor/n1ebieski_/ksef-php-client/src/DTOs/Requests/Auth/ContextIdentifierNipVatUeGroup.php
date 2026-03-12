<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\DTOs\Requests\Auth;

use DOMDocument;
use N1ebieski\KSEFClient\Contracts\DomSerializableInterface;
use N1ebieski\KSEFClient\Contracts\Requests\Auth\IdentifierInterface;
use N1ebieski\KSEFClient\Support\AbstractDTO;
use N1ebieski\KSEFClient\ValueObjects\NipVatUe;

final class ContextIdentifierNipVatUeGroup extends AbstractDTO implements DomSerializableInterface, IdentifierInterface
{
    public function __construct(
        public readonly NipVatUe $nipVatUe,
    ) {
    }

    public function getIdentifier(): NipVatUe
    {
        return $this->nipVatUe;
    }

    public function toDom(): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $contextIdentifierNipVatUeGroup = $dom->createElement('ContextIdentifierNipVatUeGroup');
        $dom->appendChild($contextIdentifierNipVatUeGroup);

        $nipVatUe = $dom->createElement('NipVatUe');
        $nipVatUe->appendChild($dom->createTextNode((string) $this->nipVatUe));

        $contextIdentifierNipVatUeGroup->appendChild($nipVatUe);

        return $dom;
    }
}
