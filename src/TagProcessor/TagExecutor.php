<?php
namespace Jsadways\LaravelSDK\TagProcessor;
use Exception;
use ReflectionClass;
use ReflectionException;

class TagExecutor
{
    protected object $obj;
    protected string $tag;
    protected ReflectionClass $ref;


    /**
     * @throws Exception
     */
    public function __construct(object $obj, string $tag)
    {
        if (!class_exists($obj::class))
        {
            throw new Exception;
        }
        $this->tag = $tag;
        $this->obj = $obj;
        $this->ref = make_reflection($obj::class);
    }

    /**
     * @throws ReflectionException
     */
    public function execute_tagged(...$params)
    {
        foreach ($this->ref->getMethods() as $method)
        {
            if ($method->getAttributes($this->tag))
            {
                $method->invoke($this->obj, ...$params);
            }
        }
    }
}
