<?php

namespace OpenSoutheners\LaravelDataMapper\Mappers;

use OpenSoutheners\LaravelDataMapper\MappingValue;
use stdClass;

use function OpenSoutheners\ExtendedPhp\Strings\is_json_structure;

final class GenericObjectDataMapper extends DataMapper
{
    /**
     * Assert that this mapper resolves property with types given.
     */
    public function assert(MappingValue $mappingValue): bool
    {
        return $mappingValue->preferredTypeClass === stdClass::class
            && (is_array($mappingValue->data) || is_json_structure($mappingValue->data));
    }

    /**
     * Resolve mapper that runs once assert returns true.
     */
    public function resolve(MappingValue $mappingValue): void
    {
        $mappingValue->data = is_array($mappingValue->data)
            ? (object) $mappingValue->data
            : json_decode($mappingValue->data);
    }
}
