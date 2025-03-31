<?php
namespace Jsadways\LaravelSDK\Http\Validation;

use Jsadways\LaravelSDK\Http\Validation\Unit\Relation;
use Jsadways\LaravelSDK\Http\Validation\Unit\Picker;
use Jsadways\LaravelSDK\Models\BaseModel;
use Exception;
use Generator;

class ModelSchema implements GetModelContract
{
    protected BaseModel $model;
    protected Picker $model_picks;

    /** @var Relation[] */
    protected array $relations;

    protected string $option;

    public function __construct(BaseModel $model, Picker $model_picks, array $relations, string $option)
    {
        $this->model = $model;
        $this->model_picks = $model_picks;
        $this->relations = $relations;
        $this->option = $option;
    }

    public function get_model(): BaseModel
    {
        return $this->model;
    }

    public function get_model_picks(): Picker
    {
        return $this->model_picks;
    }

    public function get_option(): string
    {
        return $this->option;
    }

    /**
     * @return Generator
     * @throws Exception
     */
    public function get_relations(): Generator
    {
        foreach ($this->relations as $relation)
        {
            if (!($relation instanceof Relation))
            {
                throw new Exception();
            }
            yield $relation;
        }
    }
}
