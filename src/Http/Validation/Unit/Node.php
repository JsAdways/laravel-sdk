<?php
namespace Jsadways\LaravelSDK\Http\Validation\Unit;

use Jsadways\LaravelSDK\Http\Validation\ModelSchema;
use Jsadways\LaravelSDK\Http\Validation\Unit\Operator;
use Jsadways\LaravelSDK\Http\Validation\Unit\Relation;
use Jsadways\LaravelSDK\Models\BaseModel;
use Exception;
use Generator;

class Node
{
    protected ?ModelSchema $model_schema = Null;


    protected ?Relation $relation = Null;


    protected BaseModel $model;


    protected ?Node $last_node;

    /** @var array<string, Node> */
    protected array $next_nodes;


    /** @var array<Relation> */
    protected array $relations;

    public function __construct(?ModelSchema $model_schema = Null, ?Relation $relation = Null)
    {
        if ($model_schema === Null and $relation === Null)
        {
            throw new Exception('Parameters $model_schema or $relation must have value.');
        }

        # 資料庫訊息
        $this->model_schema = $model_schema;
        $this->relation = $relation;
        $this->model = ($model_schema ?? $relation)->get_model();

        # 節點訊息
        $this->last_node = $model_schema !== Null ? $this : Null;
        $this->next_nodes = [];

    }

    public function get_suffix(): string
    {
        return $this->relation->get_suffix();
    }

    public function get_current(): string
    {
        return $this->relation ? $this->relation->get_current_relation() : $this->model->get_table_name();
    }

    public function get_last(): ?string
    {
        return $this->relation ? $this->relation->get_last_relation() : $this->model->get_table_name();
    }

    public function has_last(): bool
    {
        return $this->get_last() !== Null;
    }

    public function has_last_node(): bool
    {
        return $this->last_node !== Null;
    }

    public function get_model_picks(): array
    {
        return $this->model_schema->get_model_picks()->get_picks();
    }

    /** @return iterable<Operator> */
    public function operators(): Generator
    {
        return $this->relation->operators();
    }

    public function get_option(): string
    {
        return $this->model_schema->get_option();
    }

    public function gen_schema(array $picks, string $option, string $head = ''): array
    {
        $schemas = [...$this->model->get_schema(...$picks), ...$this->_get_child_schema($option)];
        $result = [];
        foreach ($schemas as $field => $schema)
        {
            $result["{$head}{$field}"] = $schema;
        }
        return $result;
    }

    protected function _get_child_schema(string $option): array
    {
        $schemas = [];
        foreach ($this->next_nodes as $child)
        {
            $suffix = $child->get_suffix();
            $syntax = $suffix === 'list' ? '.*.' : '.';
            foreach ($child->operators() as $operator)
            {
                $current_option = $operator->get_option();
                # create 後續只能接續 create
                if ($option === 'create' and in_array($current_option, ['update', 'delete']))
                {
                    continue;
                }
                $stmt = "{$current_option}_{$child->get_current()}";
                $child_schemas = $child->gen_schema($operator->get_picks(), $operator->get_option(), $stmt.$syntax);
                if ($suffix === 'list')
                {
                    $child_schemas[$stmt] = "{$operator->get_required()}|array";
                }
                $schemas = array_merge($schemas, $child_schemas);
            }
        }
        return $schemas;
    }

    public function link_parent_node(Node $parent): static
    {
        $this->last_node = $parent;
        return $this;
    }

    public function link_child_node(Node $child): static
    {
        $this->next_nodes[] = $child;
        return $this;
    }
}
