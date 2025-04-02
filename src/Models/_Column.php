<?php

namespace Jsadways\LaravelSDK\Models;

class _Column
{
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly int|null $length,
        public readonly string $comment,
        public readonly bool $required
    )
    {

    }

    public function to_array()
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'length' => $this->length,
            'comment' => $this->comment,
            'required' => $this->required
        ];
    }
}
