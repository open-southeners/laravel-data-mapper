<?php

namespace OpenSoutheners\LaravelDataMapper\Mappers;

use BackedEnum;
use Illuminate\Support\Collection;
use OpenSoutheners\LaravelDataMapper\MappingValue;

final class BackedEnumDataMapper extends DataMapper
{
    public function assert(MappingValue $mappingValue): array
    {
        return [
            is_string($mappingValue->data) || is_int($mappingValue->data),
            is_subclass_of($mappingValue->objectClass, BackedEnum::class),
        ];
    }

    public function resolve(MappingValue $mappingValue): void
    {
        $mappingValue->data = $mappingValue->data instanceof Collection
            ? $mappingValue->data->mapInto($mappingValue->objectClass)
            : $mappingValue->objectClass::tryFrom($mappingValue->data);
    }
}
