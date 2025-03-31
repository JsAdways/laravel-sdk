<?php
namespace Jsadways\LaravelSDK\Traits;

use Throwable;
use Illuminate\Support\Facades\Log;

trait LogMessage
{
    public function get_error(Throwable $e) {
        $file_info = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,0);
        Log::error("msg: {$e->getMessage()} | file_path: {$file_info[0]['file']} | line: {$e->getLine()}");
        return $e->getMessage();
    }
}
