<?php

namespace Jsadways\LaravelSDK\Managers;

use Jsadways\LaravelSDK\Core\Manager\ManagerContract;
use Jsadways\LaravelSDK\Core\Manager\GetObjectDto;
use Jsadways\LaravelSDK\Exceptions\BaseException;
use ReflectionClass;
use ReflectionException;
use Throwable;

class Manager implements ManagerContract
{

    protected string $__root__ = '';

    public function __construct($root=Null)
    {
        $this->__root__ = $root ?? $this->__root__;
    }

    public function get_root(): string
    {
        return $this->__root__;
    }

    /**
     * @param GetObjectDto $data: 參數
     * @return mixed
     * @throws BaseException|ReflectionException
     */
    public function get(GetObjectDto $data): mixed
    {
        return $this->_build(
            $this->_prepare_class($data),
            $this->_prepare_params($data)
        );
    }

    /**
     * @throws ReflectionException
     * @throws BaseException
     */
    protected function _build(string $class, array $params): mixed
    {
        $ref_class = new ReflectionClass($class);
        $constructor = $ref_class->getConstructor();
        try{
            if (!$ref_class->isInstantiable())
            {
                $concrete = $class;
            }
            elseif ($constructor && $constructor->getParameters())
            {
                $concrete = new $class(...$params);
            }
            else
            {
                $concrete = new $class;
            }
            return $concrete;
        }
        catch (Throwable $throwable)
        {
            throw new BaseException('Manager build fail.', error: $throwable->getMessage(), payload: ['class' => $class, 'params' => $params]);
        }
    }

    /**
     * @throws BaseException
     */
    protected function _prepare_class(GetObjectDto $data): string
    {
        $class = "{$this->__root__}{$data->name}";
        if (class_exists($class))
        {
            return $class;
        }
        throw new BaseException('Class not found.', payload: ['class' => $data->name]);
    }

    protected function _prepare_params(GetObjectDto $data): array
    {
        return $data->params;
    }
}
