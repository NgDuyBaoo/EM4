<?php

return [
    // Blade view nằm ở frontend/resources/views (tách phần giao diện khỏi backend).
    'paths' => [
        env('FRONTEND_VIEWS_PATH', realpath(base_path('../frontend/resources/views')) ?: base_path('../frontend/resources/views')),
    ],

    'compiled' => env('VIEW_COMPILED_PATH', realpath(storage_path('framework/views'))),
];
