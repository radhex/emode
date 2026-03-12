<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Testing\Fixtures\Requests\Testdata\RateLimits\Limits;

use N1ebieski\KSEFClient\Testing\Fixtures\Requests\AbstractRequestFixture;

final class LimitsRequestFixture extends AbstractRequestFixture
{
    /**
     * @var array<string, mixed>
     */
    public array $data = [
        'rateLimits' => [
            'onlineSession' => [
                'perSecond' => 0,
                'perMinute' => 0,
                'perHour' => 0,
            ],
            'batchSession' => [
                'perSecond' => 0,
                'perMinute' => 0,
                'perHour' => 0,
            ],
            'invoiceSend' => [
                'perSecond' => 0,
                'perMinute' => 0,
                'perHour' => 0,
            ],
            'invoiceStatus' => [
                'perSecond' => 0,
                'perMinute' => 0,
                'perHour' => 0,
            ],
            'sessionList' => [
                'perSecond' => 0,
                'perMinute' => 0,
                'perHour' => 0,
            ],
            'sessionInvoiceList' => [
                'perSecond' => 0,
                'perMinute' => 0,
                'perHour' => 0,
            ],
            'sessionMisc' => [
                'perSecond' => 0,
                'perMinute' => 0,
                'perHour' => 0,
            ],
            'invoiceMetadata' => [
                'perSecond' => 0,
                'perMinute' => 0,
                'perHour' => 0,
            ],
            'invoiceExport' => [
                'perSecond' => 0,
                'perMinute' => 0,
                'perHour' => 0,
            ],
            'invoiceDownload' => [
                'perSecond' => 0,
                'perMinute' => 0,
                'perHour' => 0,
            ],
            'other' => [
                'perSecond' => 0,
                'perMinute' => 0,
                'perHour' => 0,
            ],
        ],
    ];
}
