<?php

const DR = DIRECTORY_SEPARATOR;

return [

    'path' => [
        'namespace' => [
            'entities' => 'App\Models\Entities',
            'factories' => 'App\Models\Factories',
            'resource' => 'App\Http\Resources\Admin',
            'repository' => 'App\Models\Repositories',
        ],

        'stubs' => [
            'entity' => 'app'.'Stubs'.DR.'Entity'.DR,
            'factory' => 'app'.DR.'Stubs'.DR.'Factory'.DR,
            'resource' => 'app'.DR.'Stubs'.DR.'Resource'.DR,
            'repository' => 'app'.DR.'Stubs'.DR.'Repository'.DR,
        ],

        'relative' => [
            'entities' => 'app'.DR.'Models'.DR.'Entities'.DR,
            'factories' => 'app'.DR.'Models'.DR.'Factories'.DR,
            'resource' => 'app'.DR.'Http'.DR.'Resources'.DR.'Admin'.DR,
            'repository' => 'app'.DR.'Models'.DR.'Repositories'.DR,
        ],

    ]

];