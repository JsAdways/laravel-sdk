<?php

namespace Jsadways\LaravelSDK\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class GenerateArchitectureCommand extends Command
{
    protected $signature = 'generate:architecture
                           {--model= : 生成特定模型的架構檔案}
                           {--force : 覆蓋現有檔案}
                           {--only= : 僅生成特定類型檔案 (models,contracts,dtos,repositories,controllers,routes,exceptions,services,api-controllers)}
                           {--dry-run : 僅分析不生成檔案}';

    protected $description = '基於 migration 檔案自動生成完整的架構檔案 (Models, Contracts, DTOs, Repositories, Controllers, Routes, Exceptions, Services)';

    protected $migrationData = [];
    protected $relationships = [];

    public function handle()
    {
        $this->info('🚀 Laravel 架構生成工具啟動...');

        // 解析 migration 檔案
        $this->info('📖 分析 migration 檔案...');
        $this->_parseMigrations();

        if (empty($this->migrationData)) {
            $this->error('❌ 未找到 migration 檔案');
            return Command::FAILURE;
        }

        $this->info('✅ 找到 ' . count($this->migrationData) . ' 個表格定義');

        // 分析關聯
        $this->info('🔗 分析表格關聯...');
        $this->_analyzeRelationships();

        $modelFilter = $this->option('model');
        $onlyTypes = $this->option('only') ? explode(',', $this->option('only')) : null;
        $isDryRun = $this->option('dry-run');

        foreach ($this->migrationData as $tableName => $tableData) {
            $modelName = Str::studly(Str::singular($tableName));

            if ($modelFilter && $modelName !== $modelFilter) {
                continue;
            }

            $this->info("🏗️  處理 {$modelName} 模型...");

            if (!$onlyTypes || in_array('models', $onlyTypes)) {
                $this->_generateModel($modelName, $tableName, $tableData, $isDryRun);
            }

            if (!$onlyTypes || in_array('contracts', $onlyTypes)) {
                $this->_generateContract($modelName, $isDryRun);
            }

            if (!$onlyTypes || in_array('dtos', $onlyTypes)) {
                $this->_generateDtos($modelName, $tableData, $isDryRun);
            }

            if (!$onlyTypes || in_array('repositories', $onlyTypes)) {
                $this->_generateRepository($modelName, $isDryRun);
            }

            if (!$onlyTypes || in_array('controllers', $onlyTypes)) {
                $this->_generateController($modelName, $isDryRun);
            }
        }

        if (!$onlyTypes || in_array('routes', $onlyTypes)) {
            $this->_generateRoutes($isDryRun);
        }

        // 生成必要的基礎檔案
        if (!$onlyTypes || in_array('exceptions', $onlyTypes)) {
            $this->_generateExceptions($isDryRun);
        }

        if (!$onlyTypes || in_array('services', $onlyTypes)) {
            $this->_generateServices($isDryRun);
        }

        // 生成 API Controllers
        if (!$onlyTypes || in_array('api-controllers', $onlyTypes)) {
            $this->_generateApiControllers($isDryRun);
        }

        $this->info('✨ 架構生成完成！');

        return Command::SUCCESS;
    }

    protected function _parseMigrations()
    {
        $migrationPath = database_path('migrations');
        $files = File::glob($migrationPath . '/*.php');

        foreach ($files as $file) {
            $content = File::get($file);
            $tableName = $this->_extractTableName($content);

            if ($tableName) {
                $this->migrationData[$tableName] = $this->_parseTableStructure($content);
                if ($this->getOutput()->isVerbose()) {
                    $this->line("   📄 解析: {$tableName}");
                }
            }
        }
    }

    protected function _extractTableName($content)
    {
        // 匹配 Schema::create('table_name'
        if (preg_match('/Schema::create\([\'"]([^\'\"]+)[\'"]/', $content, $matches)) {
            return $matches[1];
        }
        return null;
    }

    protected function _parseTableStructure($content)
    {
        $fields = [];
        $foreignKeys = [];

        // 解析欄位定義
        $lines = explode("\n", $content);
        foreach ($lines as $line) {
            $line = trim($line);

            // 跳過 id, timestamps, softDeletes
            if (strpos($line, '->id()') !== false ||
                strpos($line, '->timestamps()') !== false ||
                strpos($line, '->softDeletes()') !== false) {
                continue;
            }

            // 解析一般欄位
            if (preg_match('/\$table->(\w+)\([\'"]([^\'\"]+)[\'"]/', $line, $matches)) {
                $type = $matches[1];
                $fieldName = $matches[2];

                $field = [
                    'name' => $fieldName,
                    'type' => $type,
                    'nullable' => strpos($line, '->nullable()') !== false,
                    'default' => $this->_extractDefault($line),
                ];

                // 解析長度限制
                if (preg_match('/\([\'"][^\'\"]+[\'"],\s*(\d+)\)/', $line, $lengthMatches)) {
                    $field['length'] = (int)$lengthMatches[1];
                }

                $fields[] = $field;

                // 檢查是否為外鍵
                if (Str::endsWith($fieldName, '_id')) {
                    $foreignKeys[] = [
                        'column' => $fieldName,
                        'references' => $this->_guessForeignTable($fieldName)
                    ];
                }
            }

            // 解析 foreignId
            if (preg_match('/\$table->foreignId\([\'"]([^\'\"]+)[\'"]/', $line, $matches)) {
                $fieldName = $matches[1];
                $fields[] = [
                    'name' => $fieldName,
                    'type' => 'foreignId',
                    'nullable' => strpos($line, '->nullable()') !== false,
                ];

                $foreignKeys[] = [
                    'column' => $fieldName,
                    'references' => $this->_guessForeignTable($fieldName)
                ];
            }
        }

        return [
            'fields' => $fields,
            'foreign_keys' => $foreignKeys
        ];
    }

    protected function _extractDefault($line)
    {
        if (preg_match('/->default\(([^)]+)\)/', $line, $matches)) {
            return trim($matches[1], "'\"");
        }
        return null;
    }

    protected function _guessForeignTable($fieldName)
    {
        // 移除 _id 後綴，轉為複數表名
        $baseName = Str::beforeLast($fieldName, '_id');
        return Str::plural($baseName);
    }

    protected function _analyzeRelationships()
    {
        foreach ($this->migrationData as $tableName => $tableData) {
            foreach ($tableData['foreign_keys'] as $fk) {
                $childTable = $tableName;
                $parentTable = $fk['references'];

                // HasMany 關聯 (父表 -> 子表)
                if (isset($this->migrationData[$parentTable])) {
                    $this->relationships[$parentTable]['hasMany'][] = [
                        'related' => $childTable,
                        'foreign_key' => $fk['column'],
                        'method_name' => Str::singular($childTable) . '_list'
                    ];
                }

                // BelongsTo 關聯 (子表 -> 父表)
                $this->relationships[$childTable]['belongsTo'][] = [
                    'related' => $parentTable,
                    'foreign_key' => $fk['column'],
                    'method_name' => Str::singular($parentTable)
                ];
            }
        }
    }

    protected function _generateModel($modelName, $tableName, $tableData, $isDryRun = false)
    {
        $template = File::get($this->_getStubPath('model.stub'));

        // 生成 _schema() 內容
        $schemaRules = $this->_generateSchemaRules($tableData['fields']);

        // 生成關聯方法
        $relations = $this->_generateModelRelations($tableName);

        $content = str_replace([
            '{{ModelName}}',
            '{{tableName}}',
            '{{schemaRules}}',
            '{{relations}}'
        ], [
            $modelName,
            $tableName,
            $schemaRules,
            $relations
        ], $template);

        $filePath = app_path("Models/{$modelName}.php");

        if ($isDryRun) {
            $this->line("   📝 [DRY-RUN] Model: {$filePath}");
            return;
        }

        if (!$this->option('force') && File::exists($filePath)) {
            $this->warn("   ⚠️  Model 檔案已存在: {$modelName}");
            return;
        }

        File::put($filePath, $content);
        $this->info("   ✅ Model: {$modelName}");
    }

    protected function _generateSchemaRules($fields)
    {
        $rules = [];

        foreach ($fields as $field) {
            $rule = [];

            if (!$field['nullable']) {
                $rule[] = 'required';
            } else {
                $rule[] = 'nullable';
            }

            switch ($field['type']) {
                case 'string':
                case 'text':
                    $rule[] = 'string';
                    if (isset($field['length'])) {
                        $rule[] = "max:{$field['length']}";
                    }
                    break;
                case 'integer':
                case 'bigInteger':
                case 'foreignId':
                case 'unsignedBigInteger':
                    $rule[] = 'integer';
                    break;
                case 'boolean':
                    $rule[] = 'bool';
                    break;
                case 'date':
                    $rule[] = 'date';
                    break;
                case 'datetime':
                case 'timestamp':
                    $rule[] = 'datetime';
                    break;
                case 'json':
                    $rule[] = 'array';
                    break;
                case 'decimal':
                case 'float':
                    $rule[] = 'numeric';
                    break;
            }

            $rules[] = "            '{$field['name']}' => '" . implode('|', $rule) . "'";
        }

        return implode(",\n", $rules);
    }

    protected function _generateModelRelations($tableName)
    {
        $relations = [];
        $tableRelations = $this->relationships[$tableName] ?? [];

        // HasMany 關聯
        if (isset($tableRelations['hasMany'])) {
            foreach ($tableRelations['hasMany'] as $relation) {
                $relatedModel = Str::studly(Str::singular($relation['related']));
                $methodName = $relation['method_name'];
                $foreignKey = $relation['foreign_key'];

                $relations[] = "
    public function {$methodName}(): HasMany
    {
        return \$this->hasMany({$relatedModel}::class, '{$foreignKey}', 'id');
    }";
            }
        }

        // BelongsTo 關聯
        if (isset($tableRelations['belongsTo'])) {
            foreach ($tableRelations['belongsTo'] as $relation) {
                $relatedModel = Str::studly(Str::singular($relation['related']));
                $methodName = $relation['method_name'];
                $foreignKey = $relation['foreign_key'];

                $relations[] = "
    public function {$methodName}(): BelongsTo
    {
        return \$this->belongsTo({$relatedModel}::class, '{$foreignKey}', 'id');
    }";
            }
        }

        return implode("\n", $relations);
    }

    protected function _generateContract($modelName, $isDryRun = false)
    {
        $template = File::get($this->_getStubPath('contract.stub'));

        $content = str_replace('{{ModelName}}', $modelName, $template);

        $dirPath = app_path("Core/Controllers/{$modelName}");
        $filePath = "{$dirPath}/{$modelName}Contract.php";

        if ($isDryRun) {
            $this->line("   📝 [DRY-RUN] Contract: {$filePath}");
            return;
        }

        if (!File::isDirectory($dirPath)) {
            File::makeDirectory($dirPath, 0755, true);
        }

        if (!$this->option('force') && File::exists($filePath)) {
            $this->warn("   ⚠️  Contract 檔案已存在: {$modelName}");
            return;
        }

        File::put($filePath, $content);
        $this->info("   ✅ Contract: {$modelName}Contract");
    }

    protected function _generateDtos($modelName, $tableData, $isDryRun = false)
    {
        // Generate Create DTO
        $this->_generateCreateDto($modelName, $tableData, $isDryRun);

        // Generate Update DTO
        $this->_generateUpdateDto($modelName, $tableData, $isDryRun);
    }

    protected function _generateCreateDto($modelName, $tableData, $isDryRun = false)
    {
        $template = File::get($this->_getStubPath('create-dto.stub'));

        $properties = $this->_generateDtoProperties($tableData['fields'], false);
        $relationArrays = $this->_generateCreateDtoRelationArrays($modelName);

        $allProperties = $properties;
        if ($relationArrays) {
            $allProperties .= ",\n" . $relationArrays;
        }

        $content = str_replace([
            '{{ModelName}}',
            '{{properties}}'
        ], [
            $modelName,
            $allProperties
        ], $template);

        $dirPath = app_path("Core/Repositories/{$modelName}/Dtos");
        $filePath = "{$dirPath}/Create{$modelName}Dto.php";

        if ($isDryRun) {
            $this->line("   📝 [DRY-RUN] Create DTO: {$filePath}");
            return;
        }

        if (!File::isDirectory($dirPath)) {
            File::makeDirectory($dirPath, 0755, true);
        }

        if (!$this->option('force') && File::exists($filePath)) {
            $this->warn("   ⚠️  Create DTO 檔案已存在: {$modelName}");
            return;
        }

        File::put($filePath, $content);
        $this->info("   ✅ Create DTO: Create{$modelName}Dto");
    }

    protected function _generateUpdateDto($modelName, $tableData, $isDryRun = false)
    {
        $template = File::get($this->_getStubPath('update-dto.stub'));

        $properties = $this->_generateDtoProperties($tableData['fields'], true);
        $relationArrays = $this->_generateUpdateDtoRelationArrays($modelName);

        $allProperties = $properties;
        if ($relationArrays) {
            $allProperties .= ",\n" . $relationArrays;
        }

        $content = str_replace([
            '{{ModelName}}',
            '{{properties}}'
        ], [
            $modelName,
            $allProperties
        ], $template);

        $dirPath = app_path("Core/Repositories/{$modelName}/Dtos");
        $filePath = "{$dirPath}/Update{$modelName}Dto.php";

        if ($isDryRun) {
            $this->line("   📝 [DRY-RUN] Update DTO: {$filePath}");
            return;
        }

        if (!File::isDirectory($dirPath)) {
            File::makeDirectory($dirPath, 0755, true);
        }

        if (!$this->option('force') && File::exists($filePath)) {
            $this->warn("   ⚠️  Update DTO 檔案已存在: {$modelName}");
            return;
        }

        File::put($filePath, $content);
        $this->info("   ✅ Update DTO: Update{$modelName}Dto");
    }

    protected function _generateDtoProperties($fields, $includeId = false)
    {
        $properties = [];

        if ($includeId) {
            $properties[] = "        public readonly int \$id";
        }

        foreach ($fields as $field) {
            $type = $this->_mapFieldTypeToPhp($field);
            $nullable = $field['nullable'] ? '?' : '';

            $properties[] = "        public readonly {$nullable}{$type} \${$field['name']}";
        }

        return implode(",\n", $properties);
    }

    protected function _mapFieldTypeToPhp($field)
    {
        switch ($field['type']) {
            case 'string':
            case 'text':
            case 'date':
            case 'datetime':
            case 'timestamp':
                return 'string';
            case 'integer':
            case 'bigInteger':
            case 'foreignId':
            case 'unsignedBigInteger':
                return 'int';
            case 'boolean':
                return 'bool';
            case 'json':
                return 'array';
            case 'decimal':
            case 'float':
                return 'float';
            default:
                return 'string';
        }
    }

    protected function _generateCreateDtoRelationArrays($modelName)
    {
        $tableName = Str::plural(Str::snake($modelName));
        $tableRelations = $this->relationships[$tableName] ?? [];
        $arrays = [];

        if (isset($tableRelations['hasMany'])) {
            foreach ($tableRelations['hasMany'] as $relation) {
                $methodName = $relation['method_name'];
                $arrays[] = "        public readonly array \$create_{$methodName} = []";
            }
        }

        return implode(",\n", $arrays);
    }

    protected function _generateUpdateDtoRelationArrays($modelName)
    {
        $tableName = Str::plural(Str::snake($modelName));
        $tableRelations = $this->relationships[$tableName] ?? [];
        $arrays = [];

        if (isset($tableRelations['hasMany'])) {
            foreach ($tableRelations['hasMany'] as $relation) {
                $methodName = $relation['method_name'];
                $arrays[] = "        public readonly array \$create_{$methodName} = []";
                $arrays[] = "        public readonly array \$update_{$methodName} = []";
                $arrays[] = "        public readonly array \$delete_{$methodName} = []";
            }
        }

        return implode(",\n", $arrays);
    }

    protected function _generateRepository($modelName, $isDryRun = false)
    {
        $template = File::get($this->_getStubPath('repository.stub'));

        $content = str_replace('{{ModelName}}', $modelName, $template);

        $filePath = app_path("Repositories/{$modelName}Repository.php");

        if ($isDryRun) {
            $this->line("   📝 [DRY-RUN] Repository: {$filePath}");
            return;
        }

        if (!$this->option('force') && File::exists($filePath)) {
            $this->warn("   ⚠️  Repository 檔案已存在: {$modelName}");
            return;
        }

        File::put($filePath, $content);
        $this->info("   ✅ Repository: {$modelName}Repository");
    }

    protected function _generateController($modelName, $isDryRun = false)
    {
        $template = File::get($this->_getStubPath('controller.stub'));

        $content = str_replace('{{ModelName}}', $modelName, $template);

        $filePath = app_path("Http/Controllers/{$modelName}Controller.php");

        if ($isDryRun) {
            $this->line("   📝 [DRY-RUN] Controller: {$filePath}");
            return;
        }

        if (!$this->option('force') && File::exists($filePath)) {
            $this->warn("   ⚠️  Controller 檔案已存在: {$modelName}");
            return;
        }

        File::put($filePath, $content);
        $this->info("   ✅ Controller: {$modelName}Controller");
    }

    protected function _generateRoutes($isDryRun = false)
    {
        $routes = [];

        foreach ($this->migrationData as $tableName => $tableData) {
            $modelName = Str::studly(Str::singular($tableName));
            $routePrefix = Str::kebab(Str::plural($modelName));

            $routes[] = "// {$modelName} routes";
            $routes[] = "Route::prefix('{$routePrefix}')->controller({$modelName}Controller::class)->group(function() {";
            $routes[] = "    Route::get('/', 'read_list');";
            $routes[] = "    Route::post('/', 'create');";
            $routes[] = "    Route::put('/', 'update');";
            $routes[] = "});";
            $routes[] = "";
        }

        $template = File::get($this->_getStubPath('routes.stub'));
        $useStatements = $this->_generateControllerUseStatements();

        $content = str_replace([
            '{{useStatements}}',
            '{{routes}}'
        ], [
            $useStatements,
            implode("\n", $routes)
        ], $template);

        $filePath = base_path('routes/api.php');

        if ($isDryRun) {
            $this->line("   📝 [DRY-RUN] Routes: {$filePath}");
            return;
        }

        File::put($filePath, $content);
        $this->info("   ✅ Routes: api.php");
    }

    protected function _generateControllerUseStatements()
    {
        $uses = [];

        foreach ($this->migrationData as $tableName => $tableData) {
            $modelName = Str::studly(Str::singular($tableName));
            $uses[] = "use App\\Http\\Controllers\\{$modelName}Controller;";
        }

        return implode("\n", $uses);
    }

    protected function _generateExceptions($isDryRun = false)
    {
        $this->info("🚨 生成必要的 Exception 檔案...");

        // 生成 BaseException
        $this->_generateBaseException($isDryRun);

        // 生成 Handler (如果需要的話)
        $this->_generateExceptionHandler($isDryRun);
    }

    protected function _generateBaseException($isDryRun = false)
    {
        $template = File::get($this->_getStubPath('base-exception.stub'));
        $filePath = app_path('Exceptions/BaseException.php');

        if ($isDryRun) {
            $this->line("   📝 [DRY-RUN] BaseException: {$filePath}");
            return;
        }

        if (!$this->option('force') && File::exists($filePath)) {
            $this->warn("   ⚠️  BaseException 檔案已存在");
            return;
        }

        if (!File::isDirectory(app_path('Exceptions'))) {
            File::makeDirectory(app_path('Exceptions'), 0755, true);
        }

        File::put($filePath, $template);
        $this->info("   ✅ BaseException");
    }

    protected function _generateExceptionHandler($isDryRun = false)
    {
        $template = File::get($this->_getStubPath('exception-handler.stub'));
        $filePath = app_path('Exceptions/Handler.php');

        if ($isDryRun) {
            $this->line("   📝 [DRY-RUN] Exception Handler: {$filePath}");
            return;
        }

        if (!$this->option('force') && File::exists($filePath)) {
            $this->warn("   ⚠️  Exception Handler 檔案已存在");
            return;
        }

        File::put($filePath, $template);
        $this->info("   ✅ Exception Handler");
    }

    protected function _generateServices($isDryRun = false)
    {
        $this->info("⚙️  生成必要的 Service 檔案...");

        // 生成 Core 檔案（Contracts 和 DTOs）
        $this->_generateServiceCoreFiles($isDryRun);

        // 生成基底 Service 檔案
        $this->_generateBaseService($isDryRun);

        // 生成 ConfigService
        $this->_generateConfigService($isDryRun);

        // 生成其他 Service 檔案
        $this->_generateFileHandleServices($isDryRun);
        $this->_generateInternalService($isDryRun);
    }

    protected function _generateBaseService($isDryRun = false)
    {
        $template = File::get($this->_getStubPath('base-service.stub'));
        $filePath = app_path('Services/Service.php');

        if ($isDryRun) {
            $this->line("   📝 [DRY-RUN] Base Service: {$filePath}");
            return;
        }

        if (!$this->option('force') && File::exists($filePath)) {
            $this->warn("   ⚠️  Base Service 檔案已存在");
            return;
        }

        if (!File::isDirectory(app_path('Services'))) {
            File::makeDirectory(app_path('Services'), 0755, true);
        }

        File::put($filePath, $template);
        $this->info("   ✅ Base Service");
    }

    protected function _generateConfigService($isDryRun = false)
    {
        $template = File::get($this->_getStubPath('config-service.stub'));
        $dirPath = app_path('Services/Config');
        $filePath = "{$dirPath}/ConfigService.php";

        if ($isDryRun) {
            $this->line("   📝 [DRY-RUN] Config Service: {$filePath}");
            return;
        }

        if (!$this->option('force') && File::exists($filePath)) {
            $this->warn("   ⚠️  Config Service 檔案已存在");
            return;
        }

        if (!File::isDirectory($dirPath)) {
            File::makeDirectory($dirPath, 0755, true);
        }

        File::put($filePath, $template);
        $this->info("   ✅ Config Service");
    }

    protected function _generateFileHandleServices($isDryRun = false)
    {
        $services = [
            'FileHandle' => ['FileHandleService', 'ImageProcessService'],
            'FileColumnProcess' => ['FileColumnProcessService']
        ];

        foreach ($services as $dirName => $serviceFiles) {
            $dirPath = app_path("Services/{$dirName}");

            if (!File::isDirectory($dirPath)) {
                File::makeDirectory($dirPath, 0755, true);
            }

            foreach ($serviceFiles as $serviceFile) {
                $template = File::get($this->_getStubPath("{$serviceFile}.stub"));
                $filePath = "{$dirPath}/{$serviceFile}.php";

                if ($isDryRun) {
                    $this->line("   📝 [DRY-RUN] {$serviceFile}: {$filePath}");
                    continue;
                }

                if (!$this->option('force') && File::exists($filePath)) {
                    $this->warn("   ⚠️  {$serviceFile} 檔案已存在");
                    continue;
                }

                File::put($filePath, $template);
                $this->info("   ✅ {$serviceFile}");
            }
        }
    }

    protected function _generateInternalService($isDryRun = false)
    {
        $template = File::get($this->_getStubPath('internal-service.stub'));
        $dirPath = app_path('Services/Internal');
        $filePath = "{$dirPath}/InternalService.php";

        if ($isDryRun) {
            $this->line("   📝 [DRY-RUN] Internal Service: {$filePath}");
            return;
        }

        if (!$this->option('force') && File::exists($filePath)) {
            $this->warn("   ⚠️  Internal Service 檔案已存在");
            return;
        }

        if (!File::isDirectory($dirPath)) {
            File::makeDirectory($dirPath, 0755, true);
        }

        File::put($filePath, $template);
        $this->info("   ✅ Internal Service");
    }

    protected function _generateServiceCoreFiles($isDryRun = false)
    {
        $this->info("🏗️  生成 Service Core 檔案...");

        // Core 檔案定義
        $coreFiles = [
            // Config Service 相關
            'Config/Contracts' => [
                'ConfigContract' => 'config-contract.stub'
            ],
            'Config/Dtos' => [
                'ConfigDto' => 'config-dto.stub'
            ],

            // FileColumnProcess Service 相關
            'FileColumnProcess/Contracts' => [
                'FileColumnProcessContract' => 'file-column-process-contract.stub'
            ],

            // FileHandle Service 相關
            'FileHandle/Contracts' => [
                'FileHandleContract' => 'file-handle-contract.stub',
                'ImageProcessContract' => 'image-process-contract.stub'
            ],
            'FileHandle/Dtos' => [
                'FileClassifyDto' => 'file-classify-dto.stub',
                'MatchResultDto' => 'match-result-dto.stub'
            ],

            // Internal Service 相關
            'Internal/Contracts' => [
                'EnumServiceContract' => 'enum-service-contract.stub'
            ],

            // Controller Contracts
            'Controllers/Internal' => [
                'EnumGetterContract' => 'enum-getter-contract.stub'
            ]
        ];

        foreach ($coreFiles as $subDir => $files) {
            // 判斷是否為 Controllers 相關路徑
            if (strpos($subDir, 'Controllers/') === 0) {
                $dirPath = app_path("Core/{$subDir}");
            } else {
                $dirPath = app_path("Core/Services/{$subDir}");
            }

            if (!File::isDirectory($dirPath)) {
                if (!$isDryRun) {
                    File::makeDirectory($dirPath, 0755, true);
                }
            }

            foreach ($files as $fileName => $stubFile) {
                $template = File::get($this->_getStubPath("{$stubFile}"));
                $filePath = "{$dirPath}/{$fileName}.php";

                if ($isDryRun) {
                    $this->line("   📝 [DRY-RUN] {$fileName}: {$filePath}");
                    continue;
                }

                if (!$this->option('force') && File::exists($filePath)) {
                    $this->warn("   ⚠️  {$fileName} 檔案已存在");
                    continue;
                }

                File::put($filePath, $template);
                $this->info("   ✅ {$fileName}");
            }
        }
    }

    protected function _generateApiControllers($isDryRun = false)
    {
        $this->info('🌐 生成 API Controllers...');

        // 生成 ConfigController
        $this->_generateConfigController($isDryRun);

        // 生成 FileUploadController
        $this->_generateFileUploadController($isDryRun);

        // 生成 InternalController
        $this->_generateInternalController($isDryRun);
    }

    protected function _generateConfigController($isDryRun = false)
    {
        $template = File::get($this->_getStubPath('config-controller.stub'));
        $dirPath = app_path('Http/Controllers/API');
        $filePath = "{$dirPath}/ConfigController.php";

        if ($isDryRun) {
            $this->line("   📝 [DRY-RUN] Config Controller: {$filePath}");
            return;
        }

        if (!File::isDirectory($dirPath)) {
            File::makeDirectory($dirPath, 0755, true);
        }

        if (!$this->option('force') && File::exists($filePath)) {
            $this->warn("   ⚠️  Config Controller 檔案已存在");
            return;
        }

        File::put($filePath, $template);
        $this->info("   ✅ Config Controller");
    }

    protected function _generateFileUploadController($isDryRun = false)
    {
        $template = File::get($this->_getStubPath('file-upload-controller.stub'));
        $dirPath = app_path('Http/Controllers/API');
        $filePath = "{$dirPath}/FileUploadController.php";

        if ($isDryRun) {
            $this->line("   📝 [DRY-RUN] FileUpload Controller: {$filePath}");
            return;
        }

        if (!File::isDirectory($dirPath)) {
            File::makeDirectory($dirPath, 0755, true);
        }

        if (!$this->option('force') && File::exists($filePath)) {
            $this->warn("   ⚠️  FileUpload Controller 檔案已存在");
            return;
        }

        File::put($filePath, $template);
        $this->info("   ✅ FileUpload Controller");
    }

    protected function _generateInternalController($isDryRun = false)
    {
        $template = File::get($this->_getStubPath('internal-controller.stub'));
        $dirPath = app_path('Http/Controllers/API');
        $filePath = "{$dirPath}/InternalController.php";

        if ($isDryRun) {
            $this->line("   📝 [DRY-RUN] Internal Controller: {$filePath}");
            return;
        }

        if (!File::isDirectory($dirPath)) {
            File::makeDirectory($dirPath, 0755, true);
        }

        if (!$this->option('force') && File::exists($filePath)) {
            $this->warn("   ⚠️  Internal Controller 檔案已存在");
            return;
        }

        File::put($filePath, $template);
        $this->info("   ✅ Internal Controller");
    }

    protected function _getStubPath($stubName): string
    {
        // 從 Command 檔案位置向上兩層到套件根目錄，然後進入 resources/stubs
        return __DIR__ . '/../../resources/stubs/' . $stubName;
    }
}
