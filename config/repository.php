<?php

$source = realpath(base_path('vendor/nanvaie/database-repository/src'));

return [

    'path' => [
        'namespace' => [
            'entities' => 'App\Models\Entities',
            'factories' => 'App\Models\Factories',
            'resource' => 'App\Http\Resources\Admin',
            'repository' => 'App\Models\Repositories',
        ],

        'stubs' => [
            'entity' => $source.'Stubs/Entity/',
            'factory' => $source.'Stubs/Factory/',
            'resource' => $source.'Stubs/Resource/',
            'repository' => $source.'Stubs/Repository/',
        ],

        'relative' => [
            'entities' => 'app/Models/Entities/',
            'factories' => 'app/Models/Factories/',
            'resource' => 'app/Http/Resources/Admin/',
            'repository' => 'app/Models/Repositories/',
        ],

    ]

];