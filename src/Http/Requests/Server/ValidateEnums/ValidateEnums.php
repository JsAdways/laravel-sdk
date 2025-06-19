<?php
namespace Jsadways\LaravelSDK\Http\Requests\Server\ValidateEnums;

use Illuminate\Support\Arr;
use Illuminate\Validation\Rules\Enum as EnumRule;
use Jsadways\LaravelSDK\Core\ValidatedEnumsDto;
use ReflectionClass;
use ReflectionException;

class ValidateEnums
{
    public function replace(array $payload,array $rules): array
    {
        $enum_rules = $this->_find_enum_rules($rules);

        $result_payload = [];
        foreach($enum_rules as $key => $enum_rule){
            $initial_segment = explode('.', $key)[0];
            if (isset($payload[$initial_segment])) {
                $this->_find_enum_in_payload($payload[$initial_segment], $initial_segment, $enum_rules, $result_payload);
            }
        }

        foreach($result_payload as $path => $enum_data){
            $enum_data = $enum_data->get();
            Arr::set($payload, $path, $enum_data['enum_path']::tryFrom($enum_data['value']));
        }

        return $payload;
    }

    /**
     *
     *
     * @return array<string, ValidatedEnumsDto>
     */
    protected function _find_enum_in_payload(&$current_data,string $current_path, array $rules, array &$results): array
    {
        $matched = null;
        $matched_length = -1;

        foreach($rules as $rule_path => $rule_value){
            $regex_pattern = '^' . str_replace(['.', '*'], ['\.', '(?:[^.]+)'], $rule_path) . '$';
            if (preg_match("/$regex_pattern/", $current_path)) {
                $current_rule_path_length = count(explode('.', $rule_path));
                if ($current_rule_path_length > $matched_length) {
                    $matched_length = $current_rule_path_length;
                    $matched = $rule_value;
                }
            }
        }

        if ($matched !== null) {
            $results[$current_path] = new ValidatedEnumsDto(enum_path:$matched,value:$current_data);
        }

        if (is_array($current_data)) {
            foreach ($current_data as $key => &$value) {
                $new_path = $current_path ? "$current_path.$key" : $key;
                $is_relevant_path = false;
                foreach ($rules as $rule_path => $_) {
                    $rule_prefix = explode('.*', $rule_path)[0];
                    if (str_starts_with($new_path, $rule_prefix) || $new_path === $rule_prefix) {
                        $is_relevant_path = true;
                        break;
                    }
                }

                if (isset($rules[$new_path])) {
                    $is_relevant_path = true;
                }

                if ($is_relevant_path) {
                    $this->_find_enum_in_payload($value, $new_path, $rules, $results);
                }
            }
        }

        return $results;
    }
    protected function _find_enum_rules(array $rules):array
    {
        return collect($rules)->filter(function ($value){
            return is_array($value) && $value[1] instanceof EnumRule;
        })->map(function ($value){
            return $this->_get_enum_class($value[1]);
        })->toArray();
    }

    /**
     * @throws ReflectionException
     */
    protected function _get_enum_class(mixed $enum_rule_obj):string
    {
        $reflection = new ReflectionClass($enum_rule_obj);
        $property = $reflection->getProperty('type');
        return $property->getValue($enum_rule_obj);
    }
}
