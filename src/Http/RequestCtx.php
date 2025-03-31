<?php
namespace Jsadways\LaravelSDK\Http;

use Illuminate\Http\Request;

class RequestCtx
{
    # Controller method name.
    protected string $method;


    # Controller method parameters.
    protected array $parameters;


    # Controller method return.
    protected mixed $result;


    public function __construct(string $method, array $parameters)
    {
        $this->method = $method;
        $this->parameters = $parameters;
    }

    public function set_method(string $value): self
    {
        $this->method = $value;
        return $this;
    }

    public function set_parameters(array|Request $value): self
    {
        $this->parameters = is_array($value) ? $value : [$value];
        return $this;
    }

    public function set_result(mixed $value): self
    {
        $this->result = $value;
        return $this;
    }

    public function get_method(): string
    {
        return $this->method;
    }

    public function get_parameters(): array
    {
        return $this->parameters;
    }

    public function get_result(): mixed
    {
        return $this->result;
    }
}
