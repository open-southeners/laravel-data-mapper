<?php

namespace OpenSoutheners\LaravelDto\Mappers;

use OpenSoutheners\LaravelDto\DataTransferObjects\MappingValue;
use stdClass;

use function OpenSoutheners\ExtendedPhp\Strings\is_json_structure;

final class GenericObjectDataMapper implements DataMapper
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
    public function resolve(MappingValue $mappingValue): mixed
    {
        if (is_array($mappingValue->data)) {
            return (object) $mappingValue->data;
        }

        return json_decode($mappingValue->data);
    }
}
