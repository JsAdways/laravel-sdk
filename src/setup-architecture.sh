#!/bin/bash

# Laravel 架構快速設置腳本
echo "🚀 Laravel 企業級架構設置腳本"
echo "================================"

# 檢查是否在 Laravel 專案根目錄
if [ ! -f "artisan" ]; then
    echo "❌ 錯誤：請在 Laravel 專案根目錄執行此腳本"
    exit 1
fi

echo "📁 建立必要目錄結構..."

# 建立核心目錄
mkdir -p app/Core/{Contracts,Controllers,Enums,Repositories,Services}
mkdir -p app/{Exceptions,Repositories,Services}
mkdir -p resources/stubs

echo "📄 建立基礎檔案..."

# 建立 SerializerContract
cat > app/Core/Contracts/SerializerContract.php << 'EOF'
<?php

namespace App\Core\Contracts;

interface SerializerContract
{
    public function to_array(): array;
}
EOF

# 建立 StaticSerializerContract
cat > app/Core/Contracts/StaticSerializerContract.php << 'EOF'
<?php

namespace App\Core\Contracts;

interface StaticSerializerContract
{
    public static function to_array(): array;
}
EOF

# 建立基底 Model（強制覆蓋）
cat > app/Models/Model.php << 'EOF'
<?php
namespace App\Models;
use Jsadways\LaravelSDK\Models\BaseModel;

class Model extends BaseModel
{
    protected function _schema(): array
    {
        // TODO: Implement _schema() method.
        return [];
    }
}
EOF

# 建立基底 Repository（強制覆蓋）
cat > app/Repositories/Repository.php << 'EOF'
<?php

namespace App\Repositories;

use Jsadways\LaravelSDK\Repositories\Repository as BaseRepository;

class Repository extends BaseRepository
{

}
EOF

# 建立基底 Picker（強制覆蓋）
cat > app/Core/Pickers/BasePicker.php << 'EOF'
<?php

namespace App\Core\Pickers;

use Jsadways\LaravelSDK\Http\Requests\Server\Picker\BasePicker as Pickers;

class BasePicker extends Pickers
{

}
EOF

# 更新 Controller（強制覆蓋）
cat > app/Http/Controllers/Controller.php << 'EOF'
<?php

namespace App\Http\Controllers;

use Jsadways\LaravelSDK\Http\BaseController;

class Controller extends BaseController
{

}
EOF

# 建立空的 Enums 目錄標記檔案
touch app/Core/Enums/.gitkeep

# 建立空的 Services 目錄標記檔案
touch app/Core/Services/.gitkeep
touch app/Services/.gitkeep

echo "🔧 註冊 Artisan Command..."

# 檢查 Kernel.php 是否已註冊命令
KERNEL_FILE="app/Console/Kernel.php"
if [ -f "$KERNEL_FILE" ]; then
    if ! grep -q "GenerateArchitectureCommand" "$KERNEL_FILE"; then
        echo "⚠️  請手動在 app/Console/Kernel.php 的 \$commands 陣列中加入："
        echo "   \\App\\Console\\Commands\\GenerateArchitectureCommand::class,"
    fi
fi

echo "✅ 基礎架構設置完成！"
echo ""
echo "📋 下一步："
echo "1. 建立您的 migration 檔案"
echo "2. 執行: php artisan generate:architecture"
echo "3. 檢查生成的檔案並實作業務邏輯"
echo ""
echo "🔍 可用指令："
echo "  php artisan generate:architecture --help"
echo "  php artisan generate:architecture --dry-run"
echo "  php artisan generate:architecture --model=User"
echo ""
echo "📚 詳細說明請查看 CLAUDE.md 和 ARCHITECTURE_SETUP.md"
