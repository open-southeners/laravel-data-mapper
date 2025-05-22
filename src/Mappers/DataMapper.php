<?php

namespace OpenSoutheners\LaravelDto\Mappers;

use OpenSoutheners\LaravelDto\DataTransferObjects\MappingValue;

interface DataMapper
{
    /**
     * Assert that this mapper resolves property with types given.
     */
    public function assert(MappingValue $mappingValue): bool;

    /**
     * Resolve mapper that runs once assert returns true.
     */
    public function resolve(MappingValue $mappingValue): mixed;
}
