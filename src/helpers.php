<?php
function make_reflection(string $class): ReflectionClass|ReflectionEnum
{
    $type = enum_exists($class) ? 'Enum' : 'Class';
    return new ("Reflection{$type}")($class);
}
