<?php

namespace Jsadways\LaravelSDK\Console\Commands;

use Symfony\Component\Filesystem\Filesystem;
use Throwable;

class RemoveSDK extends BaseMakeCommand
{
    protected $name = 'laravel-sdk:remove';
    protected $description = 'remove sdk files';
    protected Filesystem $filesystem;
    protected array $remove_path;
    protected array $files_to_create = [
        'app_path' => [
            'initial/Controllers/Controller' =>[
                'path_method' => 'ucfirst',
                'path' => 'Http/Controllers/Controller'
            ],
            'initial/Exceptions/Handler' =>[
                'path_method' => 'ucfirst',
                'path' => 'Exceptions/Handler'
            ],
            'initial/Models/User' =>[
                'path_method' => 'ucfirst',
                'path' => 'Models/User'
            ],
        ],
        'base_path' => [
            'initial/Routes/api' =>[
                'path_method' => 'strtolower',
                'path' => 'routes/api'
            ],
            'initial/Routes/web' =>[
                'path_method' => 'strtolower',
                'path' => 'routes/web'
            ],
            'initial/Routes/channels' =>[
                'path_method' => 'strtolower',
                'path' => 'routes/channels'
            ],
            'initial/Routes/console' =>[
                'path_method' => 'strtolower',
                'path' => 'routes/console'
            ],
            'initial/tests/Feature/ExampleTest' =>[
                'path_method' => 'ucfirst',
                'path' => 'tests/Feature/ExampleTest'
            ],
        ]
    ];

    public function __construct(Filesystem $filesystem)
    {
        parent::__construct();
        $this->filesystem = $filesystem;
        $this->remove_path = [
            config_path('forestage.php'),
            config_path('js_auth.php'),
            app_path('Core'),
            app_path('Http/Controllers'),
            app_path('Exceptions'),
            app_path('Models'),
            app_path('Repositories'),
            app_path('Services'),
            base_path('routes'),
            base_path('tests/Feature/')
        ];
    }

    public function handle()
    {
        try{
            foreach ($this->remove_path as $path) {
                if($this->filesystem->exists($path)){
                    $this->filesystem->remove($path);
                }
            }
            parent::handle();

        }catch (Throwable $e){
            dump("移除失敗 - {$e->getMessage()}");
        }
    }
}
