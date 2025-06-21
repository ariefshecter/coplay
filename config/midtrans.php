<?php

return [
    'isProduction' => env('MIDTRANS_IS_PRODUCTION', false),
    'serverKey' => env('MIDTRANS_SERVER_KEY'), // Seharusnya nama variabel, bukan nilainya
    'clientKey' => env('MIDTRANS_CLIENT_KEY'), // Seharusnya nama variabel, bukan nilainya
    'snapUrl' => env('MIDTRANS_SNAP_URL', 'https://app.sandbox.midtrans.com/snap/snap.js'),
];