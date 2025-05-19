<?php

namespace OpenSoutheners\LaravelDto\PropertyMappers;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\PropertyInfo\Type;

final class CarbonPropertyMapper implements PropertyMapper
{
    /**
     * Assert that this mapper resolves property with types given.
     */
    public function assert(Type $preferredType, mixed $value): bool
    {
        return $preferredType->getClassName() === CarbonInterface::class
            || is_subclass_of($preferredType->getClassName(), CarbonInterface::class);
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
        
        if ($preferredType === CarbonImmutable::class) {
            return CarbonImmutable::make($value);
        }

        return Carbon::make($value);
    }
}
