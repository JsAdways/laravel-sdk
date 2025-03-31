<?php
namespace Jsadways\LaravelSDK\Http\Validation\Unit;

use Jsadways\LaravelSDK\Http\Validation\GetModelContract;
use Jsadways\LaravelSDK\Models\BaseModel;
use Generator;

class Relation implements GetModelContract
{
    // Relation Name.
    protected readonly string $name;


    // Relation Model
    protected readonly BaseModel $model;


    /**
     * @var Operator[]
     */
    protected readonly array $operators;


    public function __construct(string $name, BaseModel $model, ...$operators)
    {
        $this->name = $name;
        $this->model = $model;
        $this->operators = $operators;
    }

    public function get_name(): string
    {
        return $this->name;
    }

    public function get_current_relation(): string
    {
        $content = $this->get_content();
        return end($content);
    }

    public function get_last_relation(): ?string
    {
        $content = $this->get_content();
        if (count($content) > 1)
        {
            array_pop($content);
            return end($content);
        }
        return Null;
    }

    public function get_content(): array
    {
        return explode('.', $this->name);
    }

    public function get_model(): BaseModel
    {
        return $this->model;
    }

    /**
     * @return iterable<Operator>
     */
    public function operators(): Generator
    {
        foreach ($this->operators as $operator)
        {
            yield $operator;
        }
    }

    /**
     * @return Operator[]
     */
    public function get_operators(): array
    {
        return $this->operators;
    }

    public function has_operators(): bool
    {
        return !empty($this->operators);
    }

    public function get_suffix(): string
    {
        $relation_content = $this->get_content();  # ['rA_list', 'rB', 'rC_list']
        $last_content = explode('_', end($relation_content));  # ['rC', 'list']
        return end($last_content);
    }
}
