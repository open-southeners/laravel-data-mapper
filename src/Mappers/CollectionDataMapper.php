<?php

namespace OpenSoutheners\LaravelDataMapper\Mappers;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use OpenSoutheners\LaravelDataMapper\MappingValue;

use function OpenSoutheners\ExtendedPhp\Strings\is_json_structure;
use function OpenSoutheners\LaravelDataMapper\map;

final class CollectionDataMapper extends DataMapper
{
    /**
     * Assert that this mapper resolves property with types given.
     */
    public function assert(MappingValue $mappingValue): bool
    {
        return $mappingValue->collectClass === 'array'
            || ($mappingValue->collectClass === Collection::class && is_array($mappingValue->data))
            || ($mappingValue->collectClass === Collection::class && is_string($mappingValue->data) && str_contains($mappingValue->data, ','))
            || $mappingValue->objectClass === Collection::class
            || $mappingValue->objectClass === EloquentCollection::class;
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
        
        if ($mappingValue->collectClass === 'array') {
            $mappingValue->data = $collection->all();
            
            return;
        }

        if ($mappingValue->objectClass && $mappingValue->objectClass !== Collection::class) {
            $collection = $collection->map(fn ($value) => map($value)->to($mappingValue->objectClass));
        }

        $mappingValue->data = $collection;
    }
}
