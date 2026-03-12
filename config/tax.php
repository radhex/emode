<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'vat' => [
        '23' => env('TAX_VAT23',23),
        '8' => env('TAX_VAT8',8),
        '5' => env('TAX_VAT5',5),
    ],

    'vat1' => [
        '23' => 22,
        '8' => 7,
        '5' => 5,
    ],

    'date' => [
        'vat1' => ['from' => '1993-05-07','to' => '2010-12-31'],
    ],

    'advance' => [
        'line' => [
            '2022' => ["rate" => 19, "limit" => 8700],   
            '2023' => ["rate" => 19, "limit" => 10200], 
            '2024' => ["rate" => 19, "limit" => 11600], 
            '2025' => ["rate" => 19, "limit" => 12900], 
            '2026' => ["rate" => 19, "limit" => 14100],
          ],
         'scale' => [
            '2021' => ['scale_treshold' => 85528, 'scale_treshold_down' => 17, 'scale_treshold_up' => 32 , 'scale_reduce' => 1360],
            '2022' => ['scale_treshold' => 120000, 'scale_treshold_down' => 12, 'scale_treshold_up' => 32 , 'scale_reduce' => 3600],
            '2023' => ['scale_treshold' => 120000, 'scale_treshold_down' => 12, 'scale_treshold_up' => 32 , 'scale_reduce' => 3600],
            '2024' => ['scale_treshold' => 120000, 'scale_treshold_down' => 12, 'scale_treshold_up' => 32 , 'scale_reduce' => 3600],
            '2025' => ['scale_treshold' => 120000, 'scale_treshold_down' => 12, 'scale_treshold_up' => 32 , 'scale_reduce' => 3600],
            '2026' => ['scale_treshold' => 120000, 'scale_treshold_down' => 12, 'scale_treshold_up' => 32 , 'scale_reduce' => 3600],
          ]          
    ],




];
