<?php

namespace OpenSoutheners\LaravelDto\PropertyMappers;

use Illuminate\Support\Collection;
use Symfony\Component\PropertyInfo\Type;

interface PropertyMapper
{
    /**
     * Assert that this mapper resolves property with types given.
     */
    public function assert(Type $preferredType, mixed $value): bool;
    
    /**
     * Resolve mapper that runs once assert returns true.
     *
     * @param array<Type> $types
     * @param Collection<\ReflectionAttribute> $attributes
     */
    public function resolve(array $types, string $key, mixed $value, Collection $attributes, array $properties): mixed;
}
