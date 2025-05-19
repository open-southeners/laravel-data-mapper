<?php

namespace OpenSoutheners\LaravelDto\PropertyMappers;

use BackedEnum;
use Illuminate\Support\Collection;
use Symfony\Component\PropertyInfo\Type;

final class BackedEnumPropertyMapper implements PropertyMapper
{
    /**
     * Assert that this mapper resolves property with types given.
     */
    public function assert(Type $preferredType, mixed $value): bool
    {
        return is_subclass_of($preferredType->getClassName(), BackedEnum::class);
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
        
        return $preferredTypeClass::tryFrom($value) ?? (count($types) > 1 ? $value : null);
    }
}
