<?php

namespace Jsadways\LaravelSDK\Core;

abstract class Dto
{
    public function get(): array
    {
        $properties = get_object_vars($this);
        return array_filter($properties, fn ($value) => !is_null($value));
    }

    public function to_array(): array
    {
        return get_object_vars($this);
    }
}
