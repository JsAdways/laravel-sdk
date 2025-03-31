<?php
namespace Jsadways\LaravelSDK\Traits;

use Throwable;
use Exception;

/*
|--------------------------------------------------------------------------
| Column Transformer
|--------------------------------------------------------------------------
|
| 陣列 key 轉換
|
*/

trait ColumnTransformer
{
    /**
     * 轉換 array key
     *
     * @param array $data
     * @param array $columns : [{key(需要被修改的key) => {to key(需要轉換的 值)}},...]
     */
    public function transform(array $data, array $columns):array
    {
        foreach ($columns as $key => $value) {
            if (!array_key_exists($key, $data)) {
                throw new Exception("key {$key} is not exists.");
            }
            $data[$value] = $data[$key];
            unset($data[$key]);
        }

        return $data;
    }
}
