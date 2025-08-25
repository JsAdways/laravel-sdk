<?php

namespace Jsadways\LaravelSDK\Console\Commands\OLD\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

trait table_method
{
    protected function _get_table_columns(string $table_name): bool|array
    {
        $table_name = Str::snake($table_name);
        if(Schema::hasTable($table_name)){
            return json_decode(json_encode(DB::select("SHOW COLUMNS FROM {$table_name}")),true);
        }

        return false;
    }
}
