<?php

namespace Jsadways\LaravelSDK\Core;

class ReadListParamsDto extends Dto
{
    public function __construct(
        public array|string $filter,
        public readonly array $select=['*'],
        public readonly string $sort_by='id',
        public readonly string $sort_order='asc',
        public readonly int $per_page=30,
        protected array|string $extra = [],
    )
    {
        $this->filter = is_string($filter) ? json_decode($filter, true) : $filter;
        $this->extra = is_string($extra) ? json_decode($extra, true) : $extra;
    }

    public function from_extra_get(string $key, $default=Null)
    {
        if (array_key_exists($key, $this->extra))
        {
            return $this->extra[$key];
        }
        return $default;
    }

    public function extra(): array
    {
        return $this->extra;
    }
}
