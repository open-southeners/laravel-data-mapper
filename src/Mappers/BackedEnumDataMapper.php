<?php

namespace OpenSoutheners\LaravelDto\Mappers;

use BackedEnum;
use OpenSoutheners\LaravelDto\DataTransferObjects\MappingValue;

final class BackedEnumDataMapper implements DataMapper
{
    /**
     * Assert that this mapper resolves property with types given.
     */
    public function assert(MappingValue $mappingValue): bool
    {
        return is_subclass_of($mappingValue->preferredTypeClass, BackedEnum::class);
    }

    /**
     * Resolve mapper that runs once assert returns true.
     */
    public function resolve(MappingValue $mappingValue): mixed
    {
        return $mappingValue->preferredTypeClass::tryFrom($mappingValue->data) ?? (
            count($mappingValue->types) > 1
                ? $mappingValue->data
                : null
        );
    }
}
