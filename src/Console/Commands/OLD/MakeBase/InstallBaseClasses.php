<?php

namespace Jsadways\LaravelSDK\Console\Commands\OLD\MakeBase;

use Jsadways\LaravelSDK\Console\Commands\BaseMakeCommand;

class InstallBaseClasses extends BaseMakeCommand
{
    protected $name = 'install:base-classes';
    protected $description = 'install all base classes';
    protected array $files_to_create = [
        'app_path' => [
            # Controller
            'http/BaseController' =>[
                'path_method' => 'ucfirst',
                'path' => 'Http/Controllers/Controller'
            ],
            'http/ConfigController' =>[
                'path_method' => 'ucfirst',
                'path' => 'Http/Controllers/API/ConfigController'
            ],
            'http/FileUploadController' =>[
                'path_method' => 'ucfirst',
                'path' => 'Http/Controllers/API/FileUploadController'
            ],
            'http/InternalController' =>[
                'path_method' => 'ucfirst',
                'path' => 'Http/Controllers/API/InternalController'
            ],
            # Model
            'models/BaseModel' =>[
                'path_method' => 'ucfirst',
                'path' => 'Models/Model'
            ],
            # Exception
            'exceptions/BaseException' =>[
                'path_method' => 'ucfirst',
                'path' => 'Exceptions/BaseException'
            ],
            'exceptions/Handler' =>[
                'path_method' => 'ucfirst',
                'path' => 'Exceptions/Handler'
            ],
            # Repository
            'repositories/BaseRepository' =>[
                'path_method' => 'ucfirst',
                'path' => 'Repositories/Repository'
            ],
            # Service
            'services/BaseService' =>[
                'path_method' => 'ucfirst',
                'path' => 'Services/Service'
            ],
            'services/Config/ConfigService' =>[
                'path_method' => 'ucfirst',
                'path' => 'Services/Config/ConfigService'
            ],
            'services/FileColumnProcess/FileColumnProcessService' =>[
                'path_method' => 'ucfirst',
                'path' => 'Services/FileColumnProcess/FileColumnProcessService'
            ],
            'services/FileHandle/FileHandleService' =>[
                'path_method' => 'ucfirst',
                'path' => 'Services/FileHandle/FileHandleService'
            ],
            'services/FileHandle/ImageProcessService' =>[
                'path_method' => 'ucfirst',
                'path' => 'Services/FileHandle/ImageProcessService'
            ],
            'services/Internal/InternalService' =>[
                'path_method' => 'ucfirst',
                'path' => 'Services/Internal/InternalService'
            ],
            # Core
            'core/Consts' =>[
                'path_method' => 'ucfirst',
                'path' => 'Core/_Consts'
            ],
            'core/Controller/ControllerExampleContract' => [
                'path_method' => 'ucfirst',
                'path' => 'Core/Controllers/Example/ExampleContract'
            ],
            'core/Controller/EnumGetterContract' => [
                'path_method' => 'ucfirst',
                'path' => 'Core/Controllers/Internal/EnumGetterContract'
            ],
            'core/Enum/EnumExample' => [
                'path_method' => 'ucfirst',
                'path' => 'Core/Enums/Example/Example'
            ],
            'core/Service/Config/Contracts/ConfigContract' => [
                'path_method' => 'ucfirst',
                'path' => 'Core/Services/Config/Contracts/ConfigContract'
            ],
            'core/Service/Config/Dtos/ConfigDto' => [
                'path_method' => 'ucfirst',
                'path' => 'Core/Services/Config/Dtos/ConfigDto'
            ],
            'core/Service/FileColumnProcess/Contracts/FileColumnProcessContract' => [
                'path_method' => 'ucfirst',
                'path' => 'Core/Services/FileColumnProcess/Contracts/FileColumnProcessContract'
            ],
            'core/Service/FileHandle/Contracts/FileHandleContract' => [
                'path_method' => 'ucfirst',
                'path' => 'Core/Services/FileHandle/Contracts/FileHandleContract'
            ],
            'core/Service/FileHandle/Contracts/ImageProcessContract' => [
                'path_method' => 'ucfirst',
                'path' => 'Core/Services/FileHandle/Contracts/ImageProcessContract'
            ],
            'core/Service/FileHandle/Dtos/FileClassifyDto' => [
                'path_method' => 'ucfirst',
                'path' => 'Core/Services/FileHandle/Dtos/FileClassifyDto'
            ],
            'core/Service/FileHandle/Dtos/MatchResultDto' => [
                'path_method' => 'ucfirst',
                'path' => 'Core/Services/FileHandle/Dtos/MatchResultDto'
            ],
            'core/Service/Internal/Contracts/EnumServiceContract' => [
                'path_method' => 'ucfirst',
                'path' => 'Core/Services/Internal/Contracts/EnumServiceContract'
            ],
            'core/Contract/SerializerContract' => [
                'path_method' => 'ucfirst',
                'path' => 'Core/Contracts/SerializerContract'
            ],
            'core/Contract/StaticSerializerContract' => [
                'path_method' => 'ucfirst',
                'path' => 'Core/Contracts/StaticSerializerContract'
            ]
        ]
    ];
}
