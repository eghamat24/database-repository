<?php

return [

    'php_version' => env('REPOSITORY_PHP_VERSION', '8.0'),

    'path' => [
        'namespace' => [
            'entities' => 'App\Models\Entities',
            'enums' => 'App\Models\Enums',
            'factories' => 'App\Models\Factories',
            'resources' => 'App\Models\Resources',
            'repositories' => 'App\Models\Repositories',
        ],

        'stub' => [
            'entities' => 'stubs/PHP' . env('REPOSITORY_PHP_VERSION', '8.0') . '/repository.entity.',
            'factories' => 'stubs/PHP' . env('REPOSITORY_PHP_VERSION', '8.0') . '/repository.factory.',
            'resources' => 'stubs/PHP' . env('REPOSITORY_PHP_VERSION', '8.0') . '/repository.resource.',
            'repositories' => [
                'base' => 'stubs/PHP' . env('REPOSITORY_PHP_VERSION', '8.0') . '/repository.base.',
                'mysql' => 'stubs/PHP' . env('REPOSITORY_PHP_VERSION', '8.0') . '/repository.mysql.',
                'interface' => 'stubs/PHP' . env('REPOSITORY_PHP_VERSION', '8.0') . '/repository.interface.',
            ]
        ],

        'relative' => [
            'entities' => 'app/Models/Entities/',
            'enums' => 'app/Models/Enums/',
            'factories' => 'app/Models/Factories/',
            'resources' => 'app/Models/Resources/',
            'repositories' => 'app/Models/Repositories/',
        ],

    ]

];
