<?php
return [
    'image_path'          => env('APP_ENVIRONMENT') === 'production'
        ? env('IMAGE_PATH_PRODUCTION')
        : env('IMAGE_PATH_DEVELOPMENT'),

    'shipping_fee'        => 50,
    'tax'                 => 0,
    'unsplash_access_key' => env('UNSPLASH_ACCESS_KEY'),
];
