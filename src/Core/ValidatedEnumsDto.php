<?php

namespace Jsadways\LaravelSDK\Core;

class ValidatedEnumsDto extends Dto
{
    public function __construct(
        public string $enum_path,
        public readonly string|int $value
    )
    {
    }
}
