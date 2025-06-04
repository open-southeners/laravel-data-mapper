<?php

namespace OpenSoutheners\LaravelDto\Mappers;

use OpenSoutheners\LaravelDto\Contracts\MapeableObject;
use OpenSoutheners\LaravelDto\DataTransferObjects\MappingValue;

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
