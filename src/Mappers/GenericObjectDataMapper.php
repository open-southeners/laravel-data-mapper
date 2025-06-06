<?php

namespace OpenSoutheners\LaravelDataMapper\Mappers;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use OpenSoutheners\LaravelDataMapper\MappingValue;
use stdClass;

use function OpenSoutheners\ExtendedPhp\Strings\is_json_structure;

final class GenericObjectDataMapper extends DataMapper
{
    public function assert(MappingValue $mappingValue): array
    {
        return [
            $mappingValue->objectClass === stdClass::class,
            is_json_structure($mappingValue->data) || (is_array($mappingValue->data) && Arr::isAssoc($mappingValue->data)) || (is_array($mappingValue->data[0] ?? null) && Arr::isAssoc($mappingValue->data[0])),
        ];
    }

    public function resolve(MappingValue $mappingValue): void
    {
        $mappingValue->data = $mappingValue->data instanceof Collection
            ? $mappingValue->data->map(fn($item) => $this->newObjectInstance($item))
            : $this->newObjectInstance($mappingValue->data);
    }
    
    protected function newObjectInstance(mixed $data): stdClass
    {
        return is_array($data) ? (object) $data : json_decode($data);
    }
}
