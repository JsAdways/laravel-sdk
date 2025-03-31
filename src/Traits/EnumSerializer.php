<?php
namespace Jsadways\LaravelSDK\Traits;

use ReflectionEnum;

trait EnumSerializer
{
    public static function to_array(): array
    {
        $cases = [];
        foreach ((new ReflectionEnum(static::class))->getCases() as $case) {
            foreach ($case->getAttributes() ?? [] as $attr)
            {
                $cases[] = [
                    'value' => $case->getValue(),
                    ...$attr->getArguments()
                ];
            }
        }
        return $cases;
    }
}
