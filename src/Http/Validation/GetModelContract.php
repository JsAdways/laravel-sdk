<?php

namespace Jsadways\LaravelSDK\Http\Validation;

use Jsadways\LaravelSDK\Models\BaseModel;

interface GetModelContract
{
    public function get_model(): BaseModel;
}
