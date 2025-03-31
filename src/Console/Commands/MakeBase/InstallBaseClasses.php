<?php

namespace Jsadways\LaravelSDK\Console\Commands\MakeBase;

class InstallBaseClasses extends BaseMakeCommand
{
    protected $name = 'install:base-classes';
    protected $description = 'install all base classes';
    protected array $files_to_create = [
        # Controller
        'http/BaseController' =>[
            'path' => 'Http/Controllers/Controller'
        ],
        'http/ConfigController' =>[
            'path' => 'Http/Controllers/API/ConfigController'
        ],
        'http/FileUploadController' =>[
            'path' => 'Http/Controllers/API/FileUploadController'
        ],
        'http/InternalController' =>[
            'path' => 'Http/Controllers/API/InternalController'
        ],
        # Model
        'models/BaseModel' =>[
            'path' => 'Models/Model'
        ],
        # Exception
        'exceptions/BaseException' =>[
            'path' => 'Exceptions/BaseException'
        ],
        # Repository
        'repositories/BaseRepository' =>[
            'path' => 'Repositories/Repository'
        ],
        # Service
        'services/BaseService' =>[
            'path' => 'Services/Service'
        ],
        'services/Config/ConfigService' =>[
            'path' => 'Services/Config/ConfigService'
        ],
        'services/FileColumnProcess/FileColumnProcessService' =>[
            'path' => 'Services/FileColumnProcess/FileColumnProcessService'
        ],
        'services/FileHandle/FileHandleService' =>[
            'path' => 'Services/FileHandle/FileHandleService'
        ],
        'services/FileHandle/ImageProcessService' =>[
            'path' => 'Services/FileHandle/ImageProcessService'
        ],
        'services/Internal/InternalService' =>[
            'path' => 'Services/Internal/InternalService'
        ],
        # Core
        'core/Consts' =>[
            'path' => 'Core/Consts'
        ],
        'core/Controller/ControllerExampleContract' => [
            'path' => 'Core/Controllers/Example/ExampleContract'
        ],
        'core/Controller/EnumGetterContract' => [
            'path' => 'Core/Controllers/Internal/EnumGetterContract'
        ],
        'core/Enum/EnumExample' => [
            'path' => 'Core/Enums/Example/Example'
        ],
        'core/Service/Config/Contracts/ConfigContract' => [
            'path' => 'Core/Services/Config/Contracts/ConfigContract'
        ],
        'core/Service/Config/Dtos/ConfigDto' => [
            'path' => 'Core/Services/Config/Dtos/ConfigDto'
        ],
        'core/Service/FileColumnProcess/Contracts/FileColumnProcessContract' => [
            'path' => 'Core/Services/FileColumnProcess/Contracts/FileColumnProcessContract'
        ],
        'core/Service/FileHandle/Contracts/FileHandleContract' => [
            'path' => 'Core/Services/FileHandle/Contracts/FileHandleContract'
        ],
        'core/Service/FileHandle/Contracts/ImageProcessContract' => [
            'path' => 'Core/Services/FileHandle/Contracts/ImageProcessContract'
        ],
        'core/Service/FileHandle/Dtos/FileClassifyDto' => [
            'path' => 'Core/Services/FileHandle/Dtos/FileClassifyDto'
        ],
        'core/Service/FileHandle/Dtos/MatchResultDto' => [
            'path' => 'Core/Services/FileHandle/Dtos/MatchResultDto'
        ],
        'core/Service/Internal/Contracts/EnumServiceContract' => [
            'path' => 'Core/Services/Internal/Contracts/EnumServiceContract'
        ],
        'core/Contract/SerializerContract' => [
            'path' => 'Core/Contracts/SerializerContract'
        ],
        'core/Contract/StaticSerializerContract' => [
            'path' => 'Core/Contracts/StaticSerializerContract'
        ]
    ];
}
