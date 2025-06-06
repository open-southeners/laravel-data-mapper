<?php

namespace OpenSoutheners\LaravelDataMapper\Mappers;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use OpenSoutheners\LaravelDataMapper\MappingValue;

use function OpenSoutheners\ExtendedPhp\Strings\is_json_structure;
use function OpenSoutheners\LaravelDataMapper\map;

final class CollectionDataMapper extends DataMapper
{
    public function assert(MappingValue $mappingValue): array
    {
        if (is_a($mappingValue->objectClass, Collection::class, true)) {
            return [true];
        }

        return [
            !is_a($mappingValue->data, Collection::class),
            $mappingValue->collectClass === 'array' || $mappingValue->collectClass === Collection::class,
            $mappingValue->collectClass === Collection::class && is_array($mappingValue->data) || $mappingValue->collectClass === Collection::class && is_string($mappingValue->data) && str_contains($mappingValue->data, ','),
        ];
    }

    /**
     * Resolve mapper that runs once assert returns true.
     */
    public function resolve(MappingValue $mappingValue): void
    {
        if ($mappingValue->objectClass === EloquentCollection::class) {
            $mappingValue->data = $mappingValue->data->toBase();

            return;
        }

        $collection = match (true) {
            is_json_structure($mappingValue->data) => Collection::make(json_decode($mappingValue->data, true)),
            is_string($mappingValue->data) => Collection::make(explode(',', $mappingValue->data)),
            default => Collection::make($mappingValue->data),
        };
        
        $collection = $collection->filter();
        
        if ($mappingValue->objectClass && $mappingValue->objectClass !== Collection::class) {
            $collection = map($collection)->to($mappingValue->objectClass);
        }

        $mappingValue->data = $mappingValue->collectClass === 'array'
            ? $collection->all()
            : $collection;
    }
}
