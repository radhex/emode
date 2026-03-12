<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\DTOs\Requests\Sessions\Online;

use DOMDocument;
use N1ebieski\KSEFClient\Contracts\DomSerializableInterface;
use N1ebieski\KSEFClient\ValueObjects\Requests\Sessions\Online\ZKlucz;
use N1ebieski\KSEFClient\ValueObjects\Requests\Sessions\Online\ZWartosc;
use N1ebieski\KSEFClient\Support\AbstractDTO;

final class MetaDane extends AbstractDTO implements DomSerializableInterface
{
    public function __construct(
        public readonly ZKlucz $zKlucz,
        public readonly ZWartosc $zWartosc,
    ) {
    }

    public function toDom(): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $metaDane = $dom->createElement('MetaDane');
        $dom->appendChild($metaDane);

        $zKlucz = $dom->createElement('ZKlucz');
        $zKlucz->appendChild($dom->createTextNode((string) $this->zKlucz));

        $metaDane->appendChild($zKlucz);

        $zWartosc = $dom->createElement('ZWartosc');
        $zWartosc->appendChild($dom->createTextNode((string) $this->zWartosc));

        $metaDane->appendChild($zWartosc);

        return $dom;
    }
}
