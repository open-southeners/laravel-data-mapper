<?php

namespace OpenSoutheners\LaravelDataMapper\Mappers;

use BackedEnum;
use OpenSoutheners\LaravelDataMapper\MappingValue;
use ReflectionEnum;

final class BackedEnumDataMapper extends DataMapper
{
    /**
     * Assert that this mapper resolves property with types given.
     */
    public function assert(MappingValue $mappingValue): bool
    {
        return is_subclass_of($mappingValue->preferredTypeClass, BackedEnum::class)
            && gettype($mappingValue->data) === (new ReflectionEnum($mappingValue->preferredTypeClass))->getBackingType()->getName();
    }

    /**
     * Resolve mapper that runs once assert returns true.
     */
    public function resolve(MappingValue $mappingValue): void
    {
        $mappingValue->data = $mappingValue->preferredTypeClass::tryFrom($mappingValue->data) ?? (
            count($mappingValue->types) > 1
                ? $mappingValue->data
                : null
        );
    }
}
