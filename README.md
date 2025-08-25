## Laravel 快速開發套件
laravel-sdk是基於laravel 10的快速開發套件  
以資料表結構為基礎快速產生相對應controller、Model、Repository  
並在Model檔案中提供_schema結構用於資料傳入快速驗證  
安裝完成後基礎Create Update Read 已經完備  

## 設計你的資料表
在安裝laravel-sdk快速開發套件前，先確認所有需要的資料表都已經建立  
透過原生指令建立你所需要的所有資料表  
```
php artisan make:migrate create_xxx_table
```
產生所有資料表  
```
php artisan migrate
```


## 安裝
1. 下載套件  
```
composer require jsadways/laravel-sdk
```
2. 安裝套件內容
```
php artisan setup:architecture
```
3. 產生快速開發原始碼  
```
php artisan generate:architecture
```
```
generate:architecture
{--model= : 生成特定模型的架構檔案}
{--force : 覆蓋現有檔案}
{--only= : 僅生成特定類型檔案 (models,contracts,dtos,repositories,controllers,routes,exceptions,services)}
{--dry-run : 僅分析不生成檔案}
```


4. 在專案 .env 添加帳號驗證網址與前端網址  
```
JS_AUTH_HOST='http://authenticate.tw'
FORESTAGE_URL='http://172.16.1.156:3100/struct'
```
如使用自訂義登入驗證系統則不需要``FORESTAGE_URL``
5. 套件有提供 Middleware 驗證功能，名稱為 js-authenticate-middleware-alias，可依需求加入route中
```
// 在需驗證位置加入 js-authenticate-middleware-alias 中間件
Route::middleware(['js-authenticate-middleware-alias'])->group(function () {
    // 路徑
});
```
6. 套件支援檔案上傳至GCS，可修改``config/filesystem.php``，增加以下資訊
```
'disks' => [
...
    'gcs' => [
            'driver' => 'gcs',
            'key_file_path' => env('GOOGLE_CLOUD_KEY_FILE', null), // optional: /path/to/service-account.json
            'key_file' => [], // optional: Array of data that substitutes the .json file (see below)
            'project_id' => env('GOOGLE_CLOUD_PROJECT_ID', 'your-project-id'), // optional: is included in key file
            'bucket' => env('GOOGLE_CLOUD_STORAGE_BUCKET', 'your-bucket'),
            'path_prefix' => env('GOOGLE_CLOUD_STORAGE_PATH_PREFIX', ''), // optional: /default/path/to/apply/in/bucket
            'storage_api_uri' => env('GOOGLE_CLOUD_STORAGE_API_URI', null), // see: Public URLs below
            'api_endpoint' => env('GOOGLE_CLOUD_STORAGE_API_ENDPOINT', null), // set storageClient apiEndpoint
            'visibility' => 'public', // optional: public|private
            'visibility_handler' => null, // optional: set to \League\Flysystem\GoogleCloudStorage\UniformBucketLevelAccessVisibility::class to enable uniform bucket level access
            'metadata' => ['cacheControl' => 'public,max-age=86400'], // optional: default metadata
        ],
...
]
```
以及修改專案中.env檔案
```
FILESYSTEM_DISK=gcs

GOOGLE_CLOUD_PROJECT_ID=GCP_PROJECT_ID
GOOGLE_CLOUD_KEY_FILE=LOCATION_OF_KEY_FILE
GOOGLE_CLOUD_STORAGE_BUCKET=BUCKET_NAME
GOOGLE_CLOUD_STORAGE_PATH_PREFIX=
GOOGLE_CLOUD_STORAGE_API_URI=
```

## 預設安裝以下服務
1. ConfigService #取得config設定key value使用
2. InternalService #取得Enums資料
3. FileColumnProcessService #針對Create Update Delete處理相對應的檔案

## 資料生成內容
1. app/Core/Controllers/TABLE_NAME/TABLE_NAMEContract.php  
   Interface : 規範controller該有的method，預設生成create、read_list、Update  
2. app/Core/Repositories/TABLE_NAME/Dtos/{Create/Update}TABLE_NAMEDto.php
   建立/更新資料所需傳入的Dto物件，內容為資料表的所有欄位
3. app/Exceptions/BaseException.php  
   基礎錯誤，可繼承BaseException後自行定義其他錯誤內容  
4. app/Http/Controllers/{API}/TABLE_NAMEController.php  
   處理業務邏輯的controller，預設繼承app/Http/Controllers/Controller.php，擁有Create、Update、Read_list、Delete 等method  
5. app/Models/TABLE_NAME.php  
   資料表的model檔案，包含_schema() method，用於定義每個欄位驗證格式，可以照需求調整  
6. app/Repositories/TABLE_NAMERepository.php  
   預設擁有Create、Update、Read_list、Delete 等method，可自行定義read、optional、delete，三個relation name，讓repository執行對應method時可以將relation資料也一起處理  
7. tests/Feature/TABLE_NAME/{Create/Delete/Read/Update}TABEL_NAMETest.php  
   產稱CRUD基礎測試內容，並放入假資料，請再依照實際內容修改

## 將錯誤訊息同步至Slack
config/logging.php
增加一個channel
```
'channels' => [
  --- other channels ---
  'http_error_sender' => [
      'driver' => 'monolog',
      'handler' => Jsadways\LaravelSDK\Logging\HttpSlackLogHandler::class
  ],
]
```
將新的channel加入stack
```
'channels' => [
  'stack' => [
      'driver' => 'stack',
      'channels' => ['single','http_error_sender'],
      'ignore_exceptions' => false,
  ],
  --- other channels ---
 ]
```

```
Create API Documents
```
php artisan app:docs

#依照route/api.php中定義的每條路徑以及每個model中定義好的_schema()產生api文件
 在<http://PROJECT_URL/docs-api>此路徑中可查閱文件內容
```

## 移除SDK套件內容
```
php artisan laravel-sdk:remove

#檔案資料恢復到原生的Laravel資料夾結構
```
