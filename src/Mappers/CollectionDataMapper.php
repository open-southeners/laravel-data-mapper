<?php

namespace OpenSoutheners\LaravelDto\Mappers;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use OpenSoutheners\LaravelDto\DataTransferObjects\MappingValue;
use Symfony\Component\TypeInfo\Type;

use function OpenSoutheners\ExtendedPhp\Strings\is_json_structure;
use function OpenSoutheners\LaravelDto\map;

final class CollectionDataMapper extends DataMapper
{
    /**
     * Assert that this mapper resolves property with types given.
     */
    public function assert(MappingValue $mappingValue): bool
    {
        return ($mappingValue->collectClass === Collection::class && is_array($mappingValue->data))
            || ($mappingValue->collectClass === Collection::class && is_string($mappingValue->data) && str_contains($mappingValue->data, ','))
            || $mappingValue->preferredType instanceof Type\CollectionType
            || $mappingValue->preferredTypeClass === Collection::class
            || $mappingValue->preferredTypeClass === EloquentCollection::class;
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
        
        if ($mappingValue->preferredTypeClass) {
            $collection = $collection->map(fn ($value) => map($value)->to($mappingValue->preferredTypeClass));
        }

        // if ($mappingValue->preferredType->getBuiltinType() === Type::BUILTIN_TYPE_ARRAY) {
        //     $collection = $collection->all();
        // }
        
        $mappingValue->data = $collection;
    }
}
