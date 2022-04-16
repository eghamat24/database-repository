<?php

return [

    'path' => [
        'namespace' => [
            'entities' => 'App\Models\Entities',
            'factories' => 'App\Models\Factories',
            'resource' => 'App\Http\Resources\Admin',
            'repository' => 'App\Models\Repositories',
        ],

        'stubs' => [
            'entity' => 'stubs/repository/entity/',
            'factory' => 'stubs/repository/factory/',
            'resource' => 'stubs/repository/resource/',
            'repository' => 'stubs/repository/repository/',
        ],

        'relative' => [
            'entities' => 'app/Models/Entities/',
            'factories' => 'app/Models/Factories/',
            'resource' => 'app/Http/Resources/Admin/',
            'repository' => 'app/Models/Repositories/',
        ],

    ]

];