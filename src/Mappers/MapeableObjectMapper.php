<?php

namespace OpenSoutheners\LaravelDataMapper\Mappers;

use OpenSoutheners\LaravelDataMapper\Contracts\MapeableObject;
use OpenSoutheners\LaravelDataMapper\MappingValue;

class MapeableObjectMapper extends DataMapper
{
    public function assert(MappingValue $mappingValue): array
    {
        return [
            is_a($mappingValue->objectClass, MapeableObject::class, true),
        ];
    }

    public function resolve(MappingValue $mappingValue): void
    {
        app($mappingValue->objectClass)->mappingFrom($mappingValue);
    }
}
