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
            'entity' => 'stubs/Entity/',
            'factory' => 'stubs/Factory/',
            'resource' => 'stubs/Resource/',
            'repository' => 'stubs/Repository/',
        ],

        'relative' => [
            'entities' => 'app/Models/Entities/',
            'factories' => 'app/Models/Factories/',
            'resource' => 'app/Http/Resources/Admin/',
            'repository' => 'app/Models/Repositories/',
        ],

    ]

];