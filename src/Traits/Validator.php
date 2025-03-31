<?php

namespace Jsadways\LaravelSDK\Traits;

use Jsadways\LaravelSDK\TagProcessor\Tags\PreCallAction;
use Jsadways\LaravelSDK\Http\RequestCtx;
use Jsadways\LaravelSDK\Http\Requests\Server\ServerRequest;

trait Validator
{
    /**
     * 初始化 validation schemas
     *
     * @param RequestCtx $ctx
     * @return void
     */
    #[PreCallAction]
    protected function init_validation_schemas(RequestCtx $ctx): void
    {
        $parameters = $ctx->get_parameters();
        if (array_is_list($parameters) and !empty($parameters) and (($request = $parameters[0]) instanceof ServerRequest))
        {
            $name = str_replace('Controller', '', class_basename(static::class));
            $request->create_validation(model_name:$name,method_name:$ctx->get_method());
            $ctx->set_parameters($request);
        }
    }

    /**
     * 初始化驗證資料
     *
     * @param RequestCtx $ctx
     * @return void
     */
    #[PreCallAction]
    protected function init_valid_payload(RequestCtx $ctx): void
    {
        $parameters = $ctx->get_parameters();
        # for sure array = [$obj] not ['key' => 'value']
        if (array_is_list($parameters) and !empty($parameters) and (($request = $parameters[0]) instanceof ServerRequest))
        {
            if(method_exists($request,'rules')&& $request->rules() !== [])
            {
                $payload = $request->validate($request->rules());
                $request->replace($payload);
                $ctx->set_parameters($request);
            }
        }
    }
}
