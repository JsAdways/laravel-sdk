<?php
namespace Jsadways\LaravelSDK\Http\Validation\Unit;

class Picker
{

    # select schema
    protected readonly array $select;


    # ignore schema
    protected readonly array $ignore;


    public function __construct(array $select=[], array $ignore=[])
    {
        $this->select = $select;
        $this->ignore = $ignore;
    }

    public function get_picks(): array
    {
        return ['select' => $this->select, 'ignore' => $this->ignore];
    }
}
