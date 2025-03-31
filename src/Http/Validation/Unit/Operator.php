<?php
namespace Jsadways\LaravelSDK\Http\Validation\Unit;

class Operator extends Picker
{
    public readonly string $option;
    public readonly string $required;
    public function __construct(string $option, array $select=[], array $ignore=[], bool $is_require=False)
    {
        parent::__construct(select: $select, ignore: $ignore);
        $this->option = $option;
        $this->required = $is_require ? 'required' : 'sometimes';
    }

    public function get_option(): string
    {
        return $this->option;
    }

    public function get_required(): string
    {
        return $this->required;
    }
}
