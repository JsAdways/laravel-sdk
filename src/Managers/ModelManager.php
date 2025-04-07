<?php

namespace Jsadways\LaravelSDK\Managers;

use Jsadways\LaravelSDK\Core\_Consts;
use Jsadways\LaravelSDK\Models\BaseModel;

class ModelManager extends Manager
{
    protected string $__root__ = _Consts::MODEL_ROOT;


    /**
     * @return array<string, BaseModel>
     */
    public function get_relation_model(BaseModel $model, string $relation): array
    {
        $result = [];
        $relation_content = explode('.', $relation);  # ['relationA', 'RelationB', 'RelationC']
        foreach ($relation_content as $content)
        {
            $model = $model->{$content}()->getRelated();
            $result[$content] = $model;
        }
        return $result;
    }
}
