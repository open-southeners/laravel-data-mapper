<?php

namespace OpenSoutheners\LaravelDataMapper\Mappers;

use OpenSoutheners\LaravelDataMapper\Contracts\MapeableObject;
use OpenSoutheners\LaravelDataMapper\MappingValue;

class MapeableObjectMapper extends DataMapper
{
    /**
     * Assert that this mapper resolves property with types given.
     */
    public function assert(MappingValue $mappingValue): bool
    {
        return is_a($mappingValue->objectClass, MapeableObject::class, true);
    }

    /**
     * Resolve mapper that runs once assert returns true.
     */
    public function resolve(MappingValue $mappingValue): void
    {
        app($mappingValue->objectClass)->mappingFrom($mappingValue);
    }
}
