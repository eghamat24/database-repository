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
            'entities' => 'stubs/Entities/entity.',
            'enums' => 'stubs/Enums/enum.',
            'factories' => 'stubs/Factories/factory.',
            'resources' => 'stubs/Resources/resource.',
            'repositories' => [
                'base' => 'stubs/Repositories/Base/base.',
                'mysql' => 'stubs/Repositories/Mysql/mysql.',
                'interface' => 'stubs/Repositories/Interface/interface.',
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
