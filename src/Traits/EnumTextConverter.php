<?php
namespace Jsadways\LaravelSDK\Traits;

use ReflectionEnum;
use Jsadways\LaravelSDK\TagProcessor\Tags\Description;

trait EnumTextConverter
{
    /**
     * 取得 Description
     *
     * @param int $value
     * @return string
     */
    public static function get_description(int $value): string
    {
        $enum = new ReflectionEnum(__CLASS__);
        $value_case = self::from($value);
        $result = '';

        foreach ($enum->getCases() as $case) {
            if ($case->getValue() === $value_case) {
                $result = $case->getAttributes(Description::class)[0]->getArguments()['name'];
                break;
            }
        }

        return $result;
    }
}
