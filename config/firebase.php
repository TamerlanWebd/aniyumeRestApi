<?php

return [
    'credentials' => [
        // Делаем из относительного пути абсолютный
        'file' => base_path(
            env('FIREBASE_CREDENTIALS', 'storage/app/firebase/firebase-credentials.json')
        ),
    ],

    'database' => [
        'url' => env('FIREBASE_DATABASE_URL'),
    ],

    'storage' => [
        'bucket' => env('FIREBASE_STORAGE_BUCKET'),
    ],
];
