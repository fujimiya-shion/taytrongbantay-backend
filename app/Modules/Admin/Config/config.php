<?php

return [
    'name' => 'Admin',
    'path' => env('ADMIN_PATH', 'admin'),
    'account' => [
        'email' => env('SUPER_ADMIN_EMAIL'),
        'password' => env('SUPER_ADMIN_PASSWORD'),
    ]
];
