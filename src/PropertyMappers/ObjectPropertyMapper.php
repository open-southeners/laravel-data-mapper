<?php

namespace OpenSoutheners\LaravelDto\PropertyMappers;

use Illuminate\Support\Collection;
use ReflectionClass;
use Symfony\Component\PropertyInfo\Type;

use function OpenSoutheners\ExtendedPhp\Strings\is_json_structure;

final class ObjectPropertyMapper implements PropertyMapper
{
    /**
     * Assert that this mapper resolves property with types given.
     */
    public function assert(Type $preferredType, mixed $value): bool
    {
        $preferredTypeClass = $preferredType->getClassName();
        
        if (!$preferredType->getClassName()) {
            return false;
        }
        
        if (!$preferredTypeClass || !class_exists($preferredTypeClass) || !(new ReflectionClass($preferredTypeClass))->isInstantiable()) {
            return false;
        }
        
        return (is_array($value) && is_string(array_key_first($value))) || is_json_structure($value);
    }

    /**
     * Resolve mapper that runs once assert returns true.
     *
     * @param array<Type> $types
     * @param Collection<\ReflectionAttribute> $attributes
     */
    public function resolve(array $types, string $key, mixed $value, Collection $attributes, array $properties): mixed
    {
        $preferredType = reset($types);
        $preferredTypeClass = $preferredType->getClassName();
        
        if (is_array($value) && is_string(array_key_first($value))) {
            return new $preferredTypeClass(...$value);
        }
        
        return new $preferredTypeClass(...json_decode($value, true));
    }
}
