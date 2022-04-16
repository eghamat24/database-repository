<?php

return [

    'php_version' => env('REPOSITORY_PHP_VERSION', '8.0'),

    'path' => [
        'namespace' => [
            'entities' => 'App\Models\Entities',
            'factories' => 'App\Models\Factories',
            'resource' => 'App\Http\Resources\Admin',
            'repository' => 'App\Models\Repositories',
        ],

        'stubs' => [
            'entity' => 'stubs/PHP'.config('repository.php_version').'/repository.entity.',
            'factory' => 'stubs/PHP'.config('repository.php_version').'/repository.factory.',
            'resource' => 'stubs/PHP'.config('repository.php_version').'/repository.resource.',
            'repository' => 'stubs/PHP'.config('repository.php_version').'/repository.repository.',
        ],

        'relative' => [
            'entities' => 'app/Models/Entities/',
            'factories' => 'app/Models/Factories/',
            'resource' => 'app/Http/Resources/Admin/',
            'repository' => 'app/Models/Repositories/',
        ],

    ]

];