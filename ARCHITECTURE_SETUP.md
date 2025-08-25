# Laravel 架構自動生成工具使用說明

## 🚀 快速開始

### 1. 複製到新專案
```bash
# 複製必要檔案到新專案
cp CLAUDE.md /path/to/new-project/
cp ARCHITECTURE_SETUP.md /path/to/new-project/
cp -r app/Console/Commands/GenerateArchitectureCommand.php /path/to/new-project/app/Console/Commands/
cp -r resources/stubs/ /path/to/new-project/resources/
```

### 2. 建立基礎目錄結構
```bash
mkdir -p app/Core/{Contracts,Controllers,Enums,Repositories,Services}
mkdir -p app/{Repositories,Services}
```

### 3. 建立基礎檔案

**app/Core/Contracts/SerializerContract.php:**
```php
<?php
namespace App\Core\Contracts;
interface SerializerContract {
    public function to_array(): array;
}
```

**app/Core/Contracts/StaticSerializerContract.php:**
```php
<?php
namespace App\Core\Contracts;
interface StaticSerializerContract {
    public static function to_array(): array;
}
```

**app/Repositories/Repository.php:**
```php
<?php
namespace App\Repositories;
use Jsadways\LaravelSDK\Repositories\Repository as BaseRepository;
class Repository extends BaseRepository {}
```

**app/Http/Controllers/Controller.php:**
```php
<?php
namespace App\Http\Controllers;
use Jsadways\LaravelSDK\Http\BaseController;
class Controller extends BaseController {}
```

### 4. 執行生成
```bash
# 建立 migration 檔案後
php artisan generate:architecture
```

## 📋 詳細使用方法

### 基本指令
```bash
# 生成所有架構檔案
php artisan generate:architecture

# 生成特定模型
php artisan generate:architecture --model=User

# 覆蓋現有檔案
php artisan generate:architecture --force

# 僅生成特定類型
php artisan generate:architecture --only=models,contracts
```

### 指令選項

| 選項 | 說明 | 範例 |
|------|------|------|
| `--model=` | 生成特定模型 | `--model=User` |
| `--force` | 覆蓋現有檔案 | `--force` |
| `--only=` | 僅生成特定類型 | `--only=models,dtos` |
| `--dry-run` | 僅分析不生成 | `--dry-run` |
| `--verbose` | 顯示詳細過程 | `--verbose` |

### 支援的檔案類型
- `models` - Eloquent 模型
- `contracts` - Controller 契約
- `dtos` - 資料傳輸物件
- `repositories` - Repository 類別
- `controllers` - HTTP 控制器
- `routes` - API 路由

## 🏗️ Migration 要求

### 標準格式
```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name', 64);
    $table->string('email')->nullable();
    $table->timestamp('email_verified_at')->nullable();
    $table->foreignId('role_id')->constrained('roles');
    $table->timestamps();
    $table->softDeletes();
});
```

### 外鍵規範
```php
// 推薦：使用 foreignId
$table->foreignId('user_id')->constrained();

// 或標準命名
$table->unsignedBigInteger('user_id');
$table->foreign('user_id')->references('id')->on('users');
```

## 📁 生成檔案結構

```
app/
├── Models/
│   └── User.php                 # 含 _schema() 和關聯
├── Core/
│   ├── Controllers/
│   │   └── User/
│   │       └── UserContract.php # CRUD 契約介面
│   └── Repositories/
│       └── User/
│           └── Dtos/
│               ├── CreateUserDto.php
│               └── UpdateUserDto.php
├── Http/
│   └── Controllers/
│       └── UserController.php   # 實作契約
└── Repositories/
    └── UserRepository.php       # 繼承基底 Repository
```

## ⚙️ 自動生成內容

### Model 檔案
- ✅ SoftDeletes trait
- ✅ 基於 migration 的 _schema() 方法
- ✅ 自動偵測的關聯 (HasMany 使用 _list 後綴)
- ✅ 正確的欄位驗證規則

### Contract 檔案
- ✅ 標準 CRUD 方法簽名
- ✅ 正確的回傳類型

### DTO 檔案
- ✅ 強型別屬性
- ✅ Readonly 不可變性
- ✅ 關聯操作陣列 (create_*_list, update_*_list, delete_*_list)

### Controller 檔案
- ✅ 實作對應 Contract
- ✅ 繼承基底 Controller

### Repository 檔案
- ✅ 繼承基底 Repository
- ✅ 準備好接受業務邏輯

### Routes 檔案
- ✅ RESTful 路由群組
- ✅ 中介軟體保護
- ✅ 自動 Controller 引入

## 🔧 故障排除

### 常見問題

**1. Migration 解析失敗**
```bash
# 檢查 migration 語法
php artisan generate:architecture --dry-run --verbose
```

**2. 關聯生成錯誤**
- 確保外鍵命名符合 `{table}_id` 格式
- 檢查參考表是否存在

**3. DTO 欄位遺失**
- 驗證 migration 欄位定義正確
- 使用 `--verbose` 查看解析過程

### 偵錯指令
```bash
# 詳細分析過程
php artisan generate:architecture --verbose --dry-run

# 測試特定模型
php artisan generate:architecture --model=User --dry-run

# 僅生成模型測試
php artisan generate:architecture --only=models --verbose
```

## 📋 檢查清單

### 新專案設置檢查
- [ ] 複製 CLAUDE.md 和相關檔案
- [ ] 建立必要目錄結構
- [ ] 建立基礎 Contract 和 Repository 檔案
- [ ] 註冊 GenerateArchitectureCommand
- [ ] 建立 migration 檔案
- [ ] 執行生成指令

### 生成後檢查
- [ ] Model 包含正確的 _schema()
- [ ] HasMany 關聯使用 _list 後綴
- [ ] Contract 定義完整 CRUD 方法
- [ ] DTO 包含所有必要屬性和關聯陣列
- [ ] Controller 正確實作 Contract
- [ ] Routes 檔案包含所有端點

## 🎯 最佳實踐

1. **Migration 優先** - 先完成 migration 設計再生成架構
2. **增量生成** - 使用 `--model=` 生成個別實體
3. **版本控制** - 生成的檔案建議納入版本控制
4. **業務邏輯分離** - 在生成檔案基礎上實作具體業務邏輯
5. **測試覆蓋** - 基於 Contract 編寫單元測試

這套工具讓您在幾分鐘內建立完整的 Laravel 企業級架構，大幅提升開發效率！
