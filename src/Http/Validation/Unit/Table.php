<?php
namespace Jsadways\LaravelSDK\Http\Validation\Unit;

use Jsadways\LaravelSDK\Models\BaseModel;
use Generator;

class Table extends Picker
{
    # table relations -> [new Relation('A'), new Relation('B'), ...]
    protected readonly array $relations;


    # Main Model
    protected readonly ?BaseModel $model;


    public function __construct(array $select=[], array $ignore=[], $relations=[], string $model=Null)
    {
        parent::__construct(select: $select, ignore: $ignore);
        $this->relations = $relations;
        $this->model = $model ? (new $model) : $model;
    }

    public function get_relations(): Generator
    {
        foreach ($this->relations as $relation)
        {
            yield $relation;
        }
    }

    public function has_relations(): bool
    {
        return !empty($this->relations);
    }

    public function get_model(): ?BaseModel
    {
        return $this->model;
    }
}
