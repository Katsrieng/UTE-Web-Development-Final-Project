<?php

return [
    'public_driver' => env('PUBLIC_MEDIA_DRIVER', 'local'),
    'payment_slip_driver' => env('PAYMENT_SLIP_DRIVER', 'local'),
    'cloudinary' => [
        'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
        'api_key' => env('CLOUDINARY_API_KEY'),
        'api_secret' => env('CLOUDINARY_API_SECRET'),
    ],
];
