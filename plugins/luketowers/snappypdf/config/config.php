<?php return [
    'packages' => [
        'barryvdh' => [
            'providers' => [
                '\Barryvdh\Snappy\ServiceProvider',
            ],

            'aliases' => [
                'SnappyPDF'   => '\Barryvdh\Snappy\Facades\SnappyPdf',

            ],

            'config_namespace' => 'snappy',

            'config' => [
                'pdf' => array(
                    'enabled' => true,
                    'binary'  => env('SNAPPY_PDF_BINARY', plugins_path('luketowers/snappypdf/vendor/bin/wkhtmltopdf-amd64')),
                    'timeout' => false,
                    'options' => array(),
                    'env'     => array(),
                )
            ],
        ],
    ],
];