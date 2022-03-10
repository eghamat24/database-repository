<?php

return [

    'path' => [

        'relative' => [
            'entities' => 'App\Models\Entities',
            'factories' => 'App\Models\Factories',
            'resource' => 'App\Http\Resources\Admin',
            'repository' => 'App\Models\Repositories'
        ],

        'absolute' => [
            'entities' => app_path('Models/Entities'),
            'factories' => app_path('Models/Factories'),
            'resource' => app_path('Http/Resources/Admin'),
            'repository' => app_path("Models/Repositories")
        ]

    ]

];