<?php

namespace Jsadways\LaravelSDK\Core\Manager;

use Jsadways\LaravelSDK\Core\Dto;

final class GetObjectDto extends Dto
{
    public function __construct(
        public string $name,
        public array $params=[]
    )
    {
    }
}
