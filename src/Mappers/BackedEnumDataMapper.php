<?php

namespace OpenSoutheners\LaravelDataMapper\Mappers;

use BackedEnum;
use Illuminate\Support\Collection;
use OpenSoutheners\LaravelDataMapper\MappingValue;

class BackedEnumDataMapper extends DataMapper
{
    public function supports(MappingValue $mappingValue): bool
    {
        return is_subclass_of($mappingValue->objectClass, BackedEnum::class)
            && (is_string($mappingValue->data) || is_int($mappingValue->data) || $mappingValue->data instanceof Collection);
    }

    public function resolve(MappingValue $mappingValue): mixed
    {
        // `from()` rather than `tryFrom()`: an unknown value raises a
        // `ValueError` naming the enum and the value, instead of a `null` that
        // only fails later as a confusing constructor `TypeError`.
        return $mappingValue->data instanceof Collection
            ? $mappingValue->data->map(fn ($value) => $mappingValue->objectClass::from($value))
            : $mappingValue->objectClass::from($mappingValue->data);
    }
}
