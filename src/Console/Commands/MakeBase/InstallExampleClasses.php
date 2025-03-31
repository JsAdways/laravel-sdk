<?php

namespace Jsadways\LaravelSDK\Console\Commands\MakeBase;

class InstallExampleClasses extends BaseMakeCommand
{
    protected $name = 'install:example-classes';
    protected $description = 'install all example classes';
    protected array $files_to_create = [
        # Manager
        'managers/ExampleManager' =>[
            'path' => 'Managers/ExampleManager'
        ],
        # Repository
        'repositories/ExampleRepository' =>[
            'path' => 'Repositories/ExampleRepository'
        ],
        # Service
        'services/Example/ExampleService' =>[
            'path' => 'Services/Example/ExampleService'
        ]
    ];
}
