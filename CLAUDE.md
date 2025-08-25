# Laravel 專案架構模板

## 架構概述

這是一個基於 Laravel 的企業級應用程式架構模板，採用 **Contract 驅動開發 (Contract-Driven Development)** 模式，提供完整的代碼生成工具和標準化的開發流程。

## 核心架構層級

專案採用以下5層架構：

1. **核心層 (Core Layer)**: 定義契約、DTOs 和業務邏輯介面
2. **Repository 層**: 資料存取層，使用 DTOs 進行資料傳輸
3. **Service 層**: 業務邏輯實作
4. **Controller 層**: HTTP 請求處理，實作核心契約
5. **Model 層**: Eloquent 模型，包含 schema 驗證和關聯

## 必要目錄結構

### ✅ **必須存在的資料夾**

```
app/
├── Core/
│   ├── Contracts/          # 基礎序列化契約 (必要)
│   ├── Controllers/        # Controller 契約定義
│   │   └── {Entity}/
│   │       └── {Entity}Contract.php
│   ├── Enums/             # 枚舉定義 (必要存在)
│   ├── Repositories/       # DTOs 定義
│   │   └── {Entity}/
│   │       └── Dtos/
│   │           ├── Create{Entity}Dto.php
│   │           └── Update{Entity}Dto.php  
│   └── Services/          # Service 契約 (必要)
├── Exceptions/            # Exception 定義 (必要)
│   ├── BaseException.php
│   └── Handler.php
├── Http/
│   └── Controllers/       # Controller 實作
│       └── {Entity}Controller.php
├── Models/               # Eloquent 模型
│   └── {Entity}.php
├── Repositories/         # Repository 實作
│   └── {Entity}Repository.php
└── Services/            # Service 實作 (必要)
    ├── Service.php       # 基底 Service 類別
    ├── Config/
    │   └── ConfigService.php
    ├── FileHandle/
    │   ├── FileHandleService.php
    │   └── ImageProcessService.php
    ├── FileColumnProcess/
    │   └── FileColumnProcessService.php
    └── Internal/
        └── InternalService.php
```

## 標準化模式

### 1. Model 模式
每個 Model 必須包含：
- **SoftDeletes** trait
- **_schema()** 方法 (基於migration自動生成)
- **關聯方法** (HasMany必須使用`_list`後綴)

### 2. Contract 模式
所有 Controller Contract 包含標準 CRUD 方法：
```php
public function create(ServerRequest $request): Model;
public function read_list(ReadListRequest $request): Collection|LengthAwarePaginator;
public function update(ServerRequest $request): Model;
```

### 3. DTO 模式
- **CreateDto**: 包含創建所需欄位 + 關聯的 `create_*_list` 陣列
- **UpdateDto**: 包含更新所需欄位 + 關聯的 `create_*_list`, `update_*_list`, `delete_*_list` 陣列

### 4. Repository 模式
所有 Repository 繼承基礎 `App\Repositories\Repository`

### 5. API 路由模式
```php
Route::prefix('{entities}')->controller({Entity}Controller::class)->group(function() {
    Route::get('/', 'read_list');
    Route::post('/', 'create');
    Route::put('/', 'update');
});
```

## 自動化生成工具

### 主要指令：`php artisan generate:architecture`

基於現有的 migration 檔案，自動生成完整的架構檔案：

#### 生成的檔案包含：
1. **Models** - 含 `_schema()` 和關聯
2. **Controller Contracts** - CRUD 介面定義
3. **DTOs** - Create/Update 資料傳輸物件
4. **Repositories** - 資料存取層
5. **Controllers** - HTTP 請求處理
6. **API Routes** - RESTful 路由定義
7. **Exceptions** - 異常處理基礎檔案
8. **Services** - 業務邏輯服務檔案

#### 使用方法：
```bash
# 生成所有架構檔案
php artisan generate:architecture

# 生成特定實體的架構檔案
php artisan generate:architecture --model=User

# 重新生成（覆蓋現有檔案）
php artisan generate:architecture --force

# 僅生成特定類型檔案
php artisan generate:architecture --only=models,contracts
php artisan generate:architecture --only=dtos,repositories
php artisan generate:architecture --only=exceptions,services
```

## 開發工作流程

### 1. 新專案設置
1. 複製這個 CLAUDE.md 檔案到新專案
2. 設定基礎目錄結構
3. 建立 migration 檔案
4. 執行 `php artisan generate:architecture`
5. 完成！所有架構檔案自動生成

### 2. 新增實體流程
1. 建立 migration 檔案
2. 執行 `php artisan generate:architecture --model=NewEntity`
3. 檢查並調整生成的檔案
4. 實作業務邏輯

### 3. Migration 要求
Migration 必須遵循以下規範：
- 使用標準 Laravel migration 語法
- 外鍵使用 `foreignId()` 或標準命名 `{table}_id`
- 必要欄位使用適當約束 (nullable, default 等)

## 命名慣例

### 1. 檔案命名
- **Models**: `{Entity}.php` (單數，PascalCase)
- **Controllers**: `{Entity}Controller.php`
- **Contracts**: `{Entity}Contract.php`
- **DTOs**: `Create{Entity}Dto.php`, `Update{Entity}Dto.php`
- **Repositories**: `{Entity}Repository.php`

### 2. 方法命名
- **HasMany 關聯**: 必須使用 `_list` 後綴 (例: `photo_list()`, `comment_list()`)
- **BelongsTo 關聯**: 使用關聯實體名稱 (例: `user()`, `creator()`)
- **Model Schema**: 必須使用 `_schema()` 方法名稱
- **非public方法**: 必須使用 `_` 前綴 (例: `_generateModel()`, `_parseData()`)

### 3. DTO 陣列命名
- **Create arrays**: `create_{relation_name} = []`
- **Update arrays**: `update_{relation_name} = []`, `delete_{relation_name} = []`

### 4. 程式碼規範
- **Public 方法**: 直接使用方法名稱，不加前綴
- **Protected/Private 方法**: 必須使用 `_` 前綴
- **常數**: 使用 `UPPER_SNAKE_CASE`
- **屬性**: 使用 `camelCase` 或 `snake_case`

## 架構優勢

### 1. 自動化開發
- 一鍵生成完整架構
- 標準化代碼結構
- 減少重複性工作

### 2. 型別安全
- 強型別 DTOs
- Contract 介面約束
- IDE 自動完成支援

### 3. 可維護性
- 清晰的層級分離
- 一致的命名慣例
- 標準化的檔案結構

### 4. 可擴展性
- 模組化設計
- 鬆耦合架構
- 便於單元測試

## 生成工具技術細節

### GenerateArchitectureCommand 結構
主要 Command 類別採用標準化命名慣例：

**Public 方法:**
- `handle()` - 主要執行方法

**Protected 方法 (使用 _ 前綴):**
- `_parseMigrations()` - 解析 migration 檔案
- `_extractTableName()` - 提取表格名稱
- `_parseTableStructure()` - 解析表格結構
- `_extractDefault()` - 提取預設值
- `_guessForeignTable()` - 推測外鍵表格
- `_analyzeRelationships()` - 分析關聯關係
- `_generateModel()` - 生成 Model 檔案
- `_generateContract()` - 生成 Contract 檔案
- `_generateDtos()` - 生成 DTO 檔案
- `_generateRepository()` - 生成 Repository 檔案
- `_generateController()` - 生成 Controller 檔案
- `_generateRoutes()` - 生成路由檔案
- `_generateExceptions()` - 生成 Exception 檔案
- `_generateServices()` - 生成 Service 檔案

### Migration 解析
工具會自動分析：
- 表格名稱和欄位定義
- 外鍵關聯和約束
- 資料類型和驗證規則
- 索引和唯一約束

### 關聯檢測
自動建立：
- **HasMany** 關聯 (父表 → 子表)
- **BelongsTo** 關聯 (子表 → 父表)
- **HasOne** 關聯 (1對1關係)

### 驗證規則生成
基於 migration 自動產生：
- `required` vs `nullable`
- 字串長度限制 `max:`
- 數值類型 `integer`, `numeric`
- 日期類型 `date`, `datetime`
- JSON 類型 `array`

## 安全和最佳實踐

### 1. 輸入驗證
- DTO 層型別安全驗證
- Model `_schema()` 規則驗證
- Migration 資料庫約束

### 2. 關聯完整性
- 外鍵約束確保資料一致性
- Soft Delete 確保資料完整性
- 關聯載入最佳化

### 3. API 安全
- 中介軟體認證保護
- 輸入資料清理和驗證
- SQL 注入防護 (Eloquent ORM)

## 擴展建議

### 1. 枚舉整合
在 `app/Core/Enums/` 中定義業務枚舉：
```php
enum Gender: string {
    case MALE = 'male';
    case FEMALE = 'female';
}
```

### 2. 服務層擴展
在 `app/Core/Services/` 中定義業務邏輯契約：
```php
interface UserServiceContract {
    public function createWithValidation(CreateUserDto $dto): User;
}
```

### 3. 快取策略
實作資料快取機制：
- Repository 層查詢快取
- API 回應快取
- 模型關聯快取

## 故障排除

### 常見問題：
1. **Migration 解析失敗** - 檢查 migration 語法是否標準
2. **關聯生成錯誤** - 確認外鍵命名符合慣例
3. **DTO 欄位遺失** - 檢查 migration 欄位定義

### 偵錯選項：
```bash
# 顯示詳細生成過程
php artisan generate:architecture --verbose

# 僅分析不生成檔案  
php artisan generate:architecture --dry-run

# 生成特定檔案用於測試
php artisan generate:architecture --only=models --force
```

## 版本控制建議

### Git 忽略檔案
生成的檔案建議加入版本控制，但可考慮忽略：
```gitignore
# 如果希望每次重新生成
# /app/Http/Controllers/*Controller.php
# /app/Repositories/*Repository.php
```

### 分支策略
- `main` - 穩定版架構模板
- `feature/entity-*` - 新實體開發分支
- `arch/updates` - 架構更新分支

## 結論

這個架構模板提供：
- ✅ **完整自動化工具鏈**
- ✅ **標準化開發流程**
- ✅ **企業級架構模式**
- ✅ **型別安全保證**
- ✅ **高度可維護性**
- ✅ **快速專案啟動**

適用於任何需要快速建立標準化 Laravel 應用程式的場景，特別是企業級專案和多實體的複雜應用程式。
