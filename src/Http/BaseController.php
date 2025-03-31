<?php

namespace Jsadways\LaravelSDK\Http;

use Exception;
use Jsadways\LaravelSDK\TagProcessor\Tags\PreCallAction;
use Jsadways\LaravelSDK\Traits\Validator;
use Generator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Routing\Controller;
use Jsadways\LaravelSDK\Core\Dto;
use Jsadways\LaravelSDK\TagProcessor\TagExecutor;
use Jsadways\LaravelSDK\Traits\LogMessage;
use Jsadways\LaravelSDK\Traits\GeneralApi;
use Jsadways\LaravelSDK\Traits\UseRepository;
use JetBrains\PhpStorm\Pure;
use ReflectionException;
use Symfony\Component\HttpFoundation\Response as ServerResponse;


class BaseController extends Controller
{
    use AuthorizesRequests, ValidatesRequests, LogMessage, UseRepository, Validator, GeneralApi;

    /**
     * @throws ReflectionException
     */
    public function callAction($method, $parameters): array|ServerResponse
    {
        $ctx = new RequestCtx(method: $method, parameters: $parameters);
        $this->_pre_call($ctx);
        $this->_call($ctx);
        return $this->_post_call($ctx);
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    protected function _pre_call(RequestCtx $ctx): RequestCtx
    {
        (new TagExecutor($this, PreCallAction::class))->execute_tagged($ctx);
        return $ctx;
    }

    protected function _call(RequestCtx $ctx): RequestCtx
    {
        $result = parent::callAction($ctx->get_method(), $ctx->get_parameters());
        $ctx->set_result($result);
        return $ctx;
    }

    protected function _post_call(RequestCtx $ctx): ServerResponse|array
    {
        $result = $ctx->get_result();
        if ($result instanceof ServerResponse)
        {
            return $result;
        }

        $content = $this->_serialize_content(
            result: $result,
            parsers: $this->_get_serializers()
        );
        return ['data' => $content];
    }

    # 序列化Controller返回內容
    private function _serialize_content($result, array $parsers):mixed
    {
        foreach ($parsers as $class => $parser) {
            if ($result instanceof $class) {
                return $parser($result);
            }
        }
        return $result;
    }

    #[Pure]
    private function _get_serializers():array
    {
        $default_serializers = [
            Collection::class => fn($result) => $result->keyBy('id')->toArray(),
            LengthAwarePaginator::class => function ($result) {
                $result = collect($result->toArray());
                return ['list' => $result->pull('data'), 'page' => $result->toArray()];
            },
            Generator::class => fn($result) => iterator_to_array($result),
            Dto::class => fn($result) => $result->to_array()
        ];
        return array_merge($default_serializers, $this->_custom_serializer());
    }

    public function _custom_serializer():array
    {
        return [];
    }
}
