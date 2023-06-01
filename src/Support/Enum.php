<?php

namespace KhanhArtisan\LaravelBackbone\Support;

class Enum
{
    /**
     * From enum cases, return an array of its values
     *
     * @param string $enumClass
     * @return array
     */
    public static function getValues(string $enumClass): array
    {
        static::validateEnum($enumClass);

        return array_map(function ($enum) {
            return $enum->value;
        }, $enumClass::cases());
    }

    /**
     * Get enum by value
     *
     * @param string $enumClass
     * @param mixed $value
     * @param mixed $default
     * @return mixed
     */
    public static function fromValue(string $enumClass, mixed $value = null, mixed $default = null): mixed
    {
        static::validateEnum($enumClass);

        foreach ($enumClass::cases() as $enum) {
            if (($enum->value ?? null) and $enum->value === $value) {
                return $enum;
            }
        }

        return $default;
    }

    /**
     * Validate if the enum exists
     *
     * @param string $enumClass
     * @return void
     */
    protected static function validateEnum(string $enumClass): void
    {
        if (!enum_exists($enumClass)) {
            throw new \InvalidArgumentException($enumClass.' is not a valid enum.');
        }
    }
}