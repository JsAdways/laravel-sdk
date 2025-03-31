<?php

namespace Jsadways\LaravelSDK\Console\Commands\Traits;

trait route_method
{
    protected function _check_insert_position(string $file_content):false|int
    {
        return strpos($file_content, $this->use_string_insert_target);
    }

    protected function _insert_position(int $position):int
    {
        return $position + strlen($this->use_string_insert_target);
    }
}
