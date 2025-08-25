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
                           {--only= : 僅生成特定類型檔案 (models,contracts,dtos,repositories,controllers,routes,exceptions,services)}
                           {--dry-run : 僅分析不生成檔案}';

    protected $description = '基於 migration 檔案自動生成完整的架構檔案 (Models, Contracts, DTOs, Repositories, Controllers, Routes, Exceptions, Services)';

    protected array $migrationData = [];
    protected array $relationships = [];

    public function handle()
    {
        $this->call('vendor:publish',[
            '--provider' => 'Js\Authenticator\Providers\AuthServiceProvider'
        ]);

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

        $this->info('✨ 架構生成完成！');

        return Command::SUCCESS;
    }

    protected function _parseMigrations(): void
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

    protected function _parseTableStructure($content): array
    {
        $fields = [];
        $foreignKeys = [];

        // 解析欄位定義
        $lines = explode("\n", $content);
        foreach ($lines as $line) {
            $line = trim($line);

            // 調試輸出
            if ($this->getOutput()->isVerbose() && strpos($line, '$table->') !== false) {
                $this->line("    🔍 處理行: $line");
            }

            // 跳過 id, timestamps, softDeletes, foreign keys, 純註解行, index 等
            if (strpos($line, '->id()') !== false ||
                strpos($line, '->timestamps()') !== false ||
                strpos($line, '->softDeletes()') !== false ||
                strpos($line, '->foreign(') !== false ||
                strpos($line, '->index(') !== false ||
                strpos($line, '->unique(') !== false ||
                preg_match('/\$table->comment\([\'"]/', $line)) { // 跳過表格註解
                continue;
            }

            // 解析一般欄位 - 包含參數的欄位類型
            if (preg_match('/\$table->(\w+)\([\'"]([^\'\"]+)[\'"]/', $line, $matches)) {
                $type = $matches[1];
                $fieldName = $matches[2];

                // 映射特殊類型
                $typeMapping = [
                    'unsignedInteger' => 'integer',
                    'unsignedBigInteger' => 'integer',
                ];

                if (isset($typeMapping[$type])) {
                    $type = $typeMapping[$type];
                }

                $field = [
                    'name' => $fieldName,
                    'type' => $type,
                    'nullable' => strpos($line, '->nullable()') !== false,
                    'default' => $this->_extractDefault($line),
                ];

                // 解析長度限制 - 改進的正則表達式
                if (preg_match('/\([\'"][^\'\"]+[\'"],\s*(\d+)\)/', $line, $lengthMatches)) {
                    $field['length'] = (int)$lengthMatches[1];
                } elseif (preg_match('/\([\'"][^\'\"]+[\'"],(\d+)\)/', $line, $lengthMatches)) {
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
                continue; // 避免重複處理
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
                continue; // 避免重複處理
            }
        }

        return [
            'fields' => $fields,
            'foreign_keys' => $foreignKeys
        ];
    }

    protected function _extractDefault($line): ?string
    {
        if (preg_match('/->default\(([^)]+)\)/', $line, $matches)) {
            return trim($matches[1], "'\"");
        }
        return null;
    }

    protected function _guessForeignTable($fieldName): string
    {
        // 移除 _id 後綴
        $baseName = Str::beforeLast($fieldName, '_id');

        // 特殊映射表格名稱
        $tableMapping = [
            'creator' => 'member',
            'front_photo' => 'album_photo',
            'album' => 'album', // 保持單數
            'member' => 'member', // 保持單數
            'baby' => 'baby', // 保持單數
        ];

        if (isset($tableMapping[$baseName])) {
            return $tableMapping[$baseName];
        }

        // 檢查是否存在單數形式的表格
        if (isset($this->migrationData[$baseName])) {
            return $baseName;
        }

        // 否則嘗試複數形式
        $pluralName = Str::plural($baseName);
        if (isset($this->migrationData[$pluralName])) {
            return $pluralName;
        }

        // 默認返回單數形式
        return $baseName;
    }

    protected function _getBelongsToMethodName($foreignKey, $parentTable): string
    {
        // 移除 _id 後綴作為方法名稱
        $baseName = Str::beforeLast($foreignKey, '_id');

        // 特殊方法名稱映射
        $methodMapping = [
            'creator_id' => 'member',
            'front_photo_id' => 'front_photo', // 這個可能需要特別處理
        ];

        if (isset($methodMapping[$foreignKey])) {
            return $methodMapping[$foreignKey];
        }

        return $baseName;
    }

    protected function _getModelNameFromTable($tableName): string
    {
        // 特殊表格到模型的映射
        $modelMapping = [
            'member' => 'Member',
            'album_photo' => 'AlbumPhoto',
            'album' => 'Album',
        ];

        if (isset($modelMapping[$tableName])) {
            return $modelMapping[$tableName];
        }

        return Str::studly(Str::singular($tableName));
    }

    protected function _getTableNameFromModel($modelName): string
    {
        // 特殊模型到表格的映射（與 _getModelNameFromTable 相反）
        $tableMapping = [
            'Album' => 'album',
            'AlbumPhoto' => 'album_photo',
            'Member' => 'member',
            'Baby' => 'baby',
            'BabyPage' => 'baby_page',
        ];

        if (isset($tableMapping[$modelName])) {
            return $tableMapping[$modelName];
        }

        // 默認規則：將模型名稱轉為 snake_case 並保持單數
        return Str::snake($modelName);
    }

    protected function _getHasManyMethodName($childTable): string
    {
        // 特殊 HasMany 方法名稱映射
        $methodMapping = [
            'album_photo' => 'album_photo_list',
            'album_photo_attr' => 'album_photo_attr_list',
            'baby_page' => 'baby_page_list',
            'member_evaluate' => 'member_evaluate_list',
            'forum_reply' => 'forum_reply_list',
        ];

        if (isset($methodMapping[$childTable])) {
            return $methodMapping[$childTable];
        }

        // 默認規則：singular(table_name) + '_list'
        return Str::singular($childTable) . '_list';
    }

    protected function _analyzeRelationships(): void
    {
        foreach ($this->migrationData as $tableName => $tableData) {
            if ($this->getOutput()->isVerbose()) {
                $this->line("  🔗 分析 {$tableName} 的外鍵: " . json_encode($tableData['foreign_keys']));
            }

            foreach ($tableData['foreign_keys'] as $fk) {
                $childTable = $tableName;
                $parentTable = $fk['references'];

                if ($this->getOutput()->isVerbose()) {
                    $this->line("    🔗 外鍵: {$childTable}.{$fk['column']} -> {$parentTable}.id");
                }

                // HasMany 關聯 (父表 -> 子表)
                if (isset($this->migrationData[$parentTable])) {
                    $methodName = $this->_getHasManyMethodName($childTable);
                    $this->relationships[$parentTable]['hasMany'][] = [
                        'related' => $childTable,
                        'foreign_key' => $fk['column'],
                        'method_name' => $methodName
                    ];

                    if ($this->getOutput()->isVerbose()) {
                        $this->line("    ✅ HasMany: {$parentTable} -> {$methodName}()");
                    }
                }

                // BelongsTo 關聯 (子表 -> 父表)
                $methodName = $this->_getBelongsToMethodName($fk['column'], $parentTable);
                $this->relationships[$childTable]['belongsTo'][] = [
                    'related' => $parentTable,
                    'foreign_key' => $fk['column'],
                    'method_name' => $methodName
                ];

                if ($this->getOutput()->isVerbose()) {
                    $this->line("    ✅ BelongsTo: {$childTable} -> {$methodName}()");
                }
            }
        }
    }

    protected function _generateModel($modelName, $tableName, $tableData, $isDryRun = false): void
    {
        $template = File::get($this->_getStubPath('stubs/model.stub'));

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

    protected function _generateSchemaRules($fields): string
    {
        $rules = [];
        $processedFields = []; // 避免重複欄位

        foreach ($fields as $field) {
            // 跳過重複欄位
            if (in_array($field['name'], $processedFields)) {
                continue;
            }
            $processedFields[] = $field['name'];

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

    protected function _generateModelRelations($tableName): string
    {
        $relations = [];
        $processedMethods = []; // 避免重複方法
        $tableRelations = $this->relationships[$tableName] ?? [];

        // HasMany 關聯
        if (isset($tableRelations['hasMany'])) {
            foreach ($tableRelations['hasMany'] as $relation) {
                $relatedModel = $this->_getModelNameFromTable($relation['related']);
                $methodName = $relation['method_name'];
                $foreignKey = $relation['foreign_key'];

                // 避免重複方法
                if (in_array($methodName, $processedMethods)) {
                    continue;
                }
                $processedMethods[] = $methodName;

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
                $relatedModel = $this->_getModelNameFromTable($relation['related']);
                $methodName = $relation['method_name'];
                $foreignKey = $relation['foreign_key'];

                // 避免重複方法
                if (in_array($methodName, $processedMethods)) {
                    continue;
                }
                $processedMethods[] = $methodName;

                $relations[] = "
    public function {$methodName}(): BelongsTo
    {
        return \$this->belongsTo({$relatedModel}::class, '{$foreignKey}', 'id');
    }";
            }
        }

        return implode("\n", $relations);
    }

    protected function _generateContract($modelName, $isDryRun = false): void
    {
        $template = File::get($this->_getStubPath('stubs/contract.stub'));

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

    protected function _generateDtos($modelName, $tableData, $isDryRun = false): void
    {
        // Generate Create DTO
        $this->_generateCreateDto($modelName, $tableData, $isDryRun);

        // Generate Update DTO
        $this->_generateUpdateDto($modelName, $tableData, $isDryRun);
    }

    protected function _generateCreateDto($modelName, $tableData, $isDryRun = false): void
    {
        $template = File::get($this->_getStubPath('stubs/create-dto.stub'));

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

    protected function _generateUpdateDto($modelName, $tableData, $isDryRun = false): void
    {
        $template = File::get($this->_getStubPath('stubs/update-dto.stub'));

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

    protected function _generateDtoProperties($fields, $includeId = false): string
    {
        $properties = [];
        $processedFields = []; // 避免重複欄位

        if ($includeId) {
            $properties[] = "        public readonly int \$id";
            $processedFields[] = 'id';
        }

        foreach ($fields as $field) {
            // 跳過重複欄位
            if (in_array($field['name'], $processedFields)) {
                continue;
            }
            $processedFields[] = $field['name'];

            $type = $this->_mapFieldTypeToPhp($field);
            $nullable = $field['nullable'] ? '?' : '';

            $properties[] = "        public readonly {$nullable}{$type} \${$field['name']}";
        }

        return implode(",\n", $properties);
    }

    protected function _mapFieldTypeToPhp($field): string
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

    protected function _generateCreateDtoRelationArrays($modelName): string
    {
        // 根據模型名稱找到對應的實際表格名稱
        $tableName = $this->_getTableNameFromModel($modelName);
        $tableRelations = $this->relationships[$tableName] ?? [];
        $arrays = [];

        // 調試輸出
        if ($this->getOutput()->isVerbose()) {
            $this->line("    🔍 檢查 {$modelName} ({$tableName}) 的關聯...");
            $this->line("    🔍 關聯資料: " . json_encode($tableRelations));
        }

        if (isset($tableRelations['hasMany'])) {
            foreach ($tableRelations['hasMany'] as $relation) {
                $methodName = $relation['method_name'];
                $arrays[] = "        public readonly array \$create_{$methodName} = []";

                if ($this->getOutput()->isVerbose()) {
                    $this->line("    ✅ 新增關聯陣列: create_{$methodName}");
                }
            }
        }

        return implode(",\n", $arrays);
    }

    protected function _generateUpdateDtoRelationArrays($modelName): string
    {
        // 根據模型名稱找到對應的實際表格名稱
        $tableName = $this->_getTableNameFromModel($modelName);
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

    protected function _generateRepository($modelName, $isDryRun = false): void
    {
        $template = File::get($this->_getStubPath('stubs/repository.stub'));

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

    protected function _generateController($modelName, $isDryRun = false): void
    {
        $template = File::get($this->_getStubPath('stubs/controller.stub'));

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

    protected function _generateRoutes($isDryRun = false): void
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

        $template = File::get($this->_getStubPath('stubs/routes.stub'));
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

    protected function _generateControllerUseStatements(): string
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

    protected function _generateBaseException($isDryRun = false): void
    {
        $template = File::get($this->_getStubPath('stubs/base-exception.stub'));
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

    protected function _generateExceptionHandler($isDryRun = false): void
    {
        $template = File::get($this->_getStubPath('stubs/exception-handler.stub'));
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

    protected function _generateServices($isDryRun = false): void
    {
        $this->info("⚙️  生成必要的 Service 檔案...");

        // 生成基底 Service 檔案
        $this->_generateBaseService($isDryRun);

        // 生成 ConfigService
        $this->_generateConfigService($isDryRun);

        // 生成其他 Service 檔案
        $this->_generateFileHandleServices($isDryRun);
        $this->_generateInternalService($isDryRun);
    }

    protected function _generateBaseService($isDryRun = false): void
    {
        $template = File::get($this->_getStubPath('stubs/base-service.stub'));
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

    protected function _generateConfigService($isDryRun = false): void
    {
        $template = File::get($this->_getStubPath('stubs/config-service.stub'));
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

    protected function _generateFileHandleServices($isDryRun = false): void
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
                $template = File::get($this->_getStubPath("stubs/{$serviceFile}.stub"));
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

    protected function _generateInternalService($isDryRun = false): void
    {
        $template = File::get($this->_getStubPath('stubs/internal-service.stub'));
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

    protected function _getStubPath($stubName): string
    {
        // 從 Command 檔案位置向上兩層到套件根目錄，然後進入 resources/stubs
        return __DIR__ . '/../../resources/stubs/' . $stubName;
    }
}
