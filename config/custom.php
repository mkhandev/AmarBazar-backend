<?php
return [
    'image_path' => env('APP_ENVIRONMENT') === 'production'
        ? env('IMAGE_PATH_PRODUCTION')
        : env('IMAGE_PATH_DEVELOPMENT'),
];
