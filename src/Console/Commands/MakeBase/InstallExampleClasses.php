<?php

namespace Jsadways\LaravelSDK\Console\Commands\MakeBase;

class InstallExampleClasses extends BaseMakeCommand
{
    protected $name = 'install:example-classes';
    protected $description = 'install all example classes';
    protected array $files_to_create = [
        'app_path' => [
            # Manager
            'managers/ExampleManager' =>[
                'path_method' => 'ucfirst',
                'path' => 'Managers/ExampleManager'
            ],
            # Repository
            'repositories/ExampleRepository' =>[
                'path_method' => 'ucfirst',
                'path' => 'Repositories/ExampleRepository'
            ],
            # Service
            'services/Example/ExampleService' =>[
                'path_method' => 'ucfirst',
                'path' => 'Services/Example/ExampleService'
            ]
        ]
    ];
}
