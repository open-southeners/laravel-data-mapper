<?php

namespace OpenSoutheners\LaravelDto\Mappers;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use OpenSoutheners\LaravelDto\DataTransferObjects\MappingValue;
use Symfony\Component\PropertyInfo\Type;

use function OpenSoutheners\ExtendedPhp\Strings\is_json_structure;
use function OpenSoutheners\LaravelDto\map;

final class CollectionDataMapper implements DataMapper
{
    /**
     * Assert that this mapper resolves property with types given.
     */
    public function assert(MappingValue $mappingValue): bool
    {
        return $mappingValue->preferredType?->isCollection()
            || $mappingValue->preferredTypeClass === Collection::class
            || $mappingValue->preferredTypeClass === EloquentCollection::class;
    }

    /**
     * Resolve mapper that runs once assert returns true.
     *
     * @param  string[]|string  $types
     * @param  Collection<\ReflectionAttribute>  $attributes
     * @param  array<string, mixed>  $properties
     */
    public function resolve(MappingValue $mappingValue): mixed
    {
        if ($mappingValue->objectClass === EloquentCollection::class) {
            return $mappingValue->data->toBase();
        }
        
        if (
            count(array_filter($mappingValue->types, fn (Type $type) => $type->getBuiltinType() === Type::BUILTIN_TYPE_STRING)) > 0
            && ! str_contains($mappingValue->types, ',')
        ) {
            return $mappingValue->data;
        }

        $collection = match (true) {
            is_json_structure($mappingValue->data) => Collection::make(json_decode($mappingValue->data, true)),
            is_string($mappingValue->data) => Collection::make(explode(',', $mappingValue->data)),
            default => Collection::make($mappingValue->data),
        };

        $collectionTypes = $mappingValue->preferredType->getCollectionValueTypes();

        $preferredCollectionType = head($collectionTypes);
        $preferredCollectionTypeClass = $preferredCollectionType ? $preferredCollectionType->getClassName() : null;

        $collection = $collection->map(fn ($value) => is_string($value) ? trim($value) : $value)
            ->filter(fn ($item) => ! blank($item));

        if ($preferredCollectionType && $preferredCollectionType->getBuiltinType() === Type::BUILTIN_TYPE_OBJECT) {
            if (is_subclass_of($preferredCollectionTypeClass, Model::class)) {
                $collection = map($mappingValue->data)->to($preferredCollectionTypeClass);
            } else {
                $collection = $collection->map(
                    fn ($item) => map($item)->to($preferredCollectionTypeClass)
                );
            }
        }

        if ($mappingValue->preferredType->getBuiltinType() === Type::BUILTIN_TYPE_ARRAY) {
            $collection = $collection->all();
        }

        return $collection;
    }
}
