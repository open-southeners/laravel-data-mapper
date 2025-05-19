<?php

namespace OpenSoutheners\LaravelDto\PropertyMappers;

use Illuminate\Support\Collection;
use stdClass;
use Symfony\Component\PropertyInfo\Type;

use function OpenSoutheners\ExtendedPhp\Strings\is_json_structure;

final class GenericObjectPropertyMapper implements PropertyMapper
{
    /**
     * Assert that this mapper resolves property with types given.
     */
    public function assert(Type $preferredType, mixed $value): bool
    {
        return $preferredType->getClassName() === stdClass::class
            && (is_array($value) || is_json_structure($value));
    }

    /**
     * Resolve mapper that runs once assert returns true.
     *
     * @param array<Type> $types
     * @param Collection<\ReflectionAttribute> $attributes
     */
    public function resolve(array $types, string $key, mixed $value, Collection $attributes, array $properties): mixed
    {
        if (is_array($value)) {
            return (object) $value;
        }
        
        return json_decode($value);
    }
}
