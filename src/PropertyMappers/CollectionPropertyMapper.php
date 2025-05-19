<?php

namespace OpenSoutheners\LaravelDto\PropertyMappers;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Symfony\Component\PropertyInfo\Type;

use function OpenSoutheners\ExtendedPhp\Strings\is_json_structure;

final class CollectionPropertyMapper implements PropertyMapper
{
    /**
     * Assert that this mapper resolves property with types given.
     */
    public function assert(Type $preferredType, mixed $value): bool
    {
        return $preferredType->isCollection()
            || $preferredType->getClassName() === Collection::class
            || $preferredType->getClassName() === EloquentCollection::class;
    }
    
    /**
     * Resolve mapper that runs once assert returns true.
     *
     * @param array<Type> $types
     * @param Collection<\ReflectionAttribute> $attributes
     */
    public function resolve(array $types, string $key, mixed $value, Collection $attributes, array $properties): mixed
    {
        if ($value instanceof Collection) {
            return $value instanceof EloquentCollection ? $value->toBase() : $value;
        }

        $propertyType = reset($types);

        if (
            count(array_filter($types, fn (Type $type) => $type->getBuiltinType() === Type::BUILTIN_TYPE_STRING)) > 0
            && ! str_contains($value, ',')
        ) {
            return $value;
        }

        if (is_json_structure($value)) {
            $collection = Collection::make(json_decode($value, true));
        } else {
            $collection = Collection::make(
                is_array($value)
                    ? $value
                    : explode(',', $value)
            );
        }

        $collectionTypes = $propertyType->getCollectionValueTypes();

        $preferredCollectionType = reset($collectionTypes);
        $preferredCollectionTypeClass = $preferredCollectionType ? $preferredCollectionType->getClassName() : null;

        $collection = $collection->map(fn ($value) => is_string($value) ? trim($value) : $value)
            ->filter(fn($item) => !blank($item));

        if ($preferredCollectionType && $preferredCollectionType->getBuiltinType() === Type::BUILTIN_TYPE_OBJECT) {
            if (is_subclass_of($preferredCollectionTypeClass, Model::class)) {
                $collectionTypeModelClasses = array_filter(
                    array_map(fn (Type $type) => $type->getClassName(), $collectionTypes),
                    fn ($typeClass) => is_a($typeClass, Model::class, true)
                );

                $collection = (new ModelPropertyMapper())->resolve(
                    $collectionTypeModelClasses,
                    $key,
                    $collection,
                    $attributes,
                    $properties
                );
            } else {
                $collection = $collection->map(
                    fn ($item) => is_array($item)
                        ? new $preferredCollectionTypeClass(...$item)
                        : new $preferredCollectionTypeClass($item)
                );
            }
        }

        if ($propertyType->getBuiltinType() === Type::BUILTIN_TYPE_ARRAY) {
            $collection = $collection->all();
        }

        return $collection;
    }
}
