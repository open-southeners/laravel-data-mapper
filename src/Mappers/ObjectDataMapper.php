<?php

namespace OpenSoutheners\LaravelDataMapper\Mappers;

use Illuminate\Contracts\Container\ContextualAttribute;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use OpenSoutheners\LaravelDataMapper\Attributes\NormaliseProperties;
use OpenSoutheners\LaravelDataMapper\Mapper;
use OpenSoutheners\LaravelDataMapper\MappingValue;
use OpenSoutheners\LaravelDataMapper\PropertyInfoExtractor;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionProperty;
use stdClass;
use Symfony\Component\TypeInfo\Type;

use function OpenSoutheners\ExtendedPhp\Strings\is_json_structure;
use function OpenSoutheners\LaravelDataMapper\map;

final class ObjectDataMapper extends DataMapper
{
    /**
     * Assert that this mapper resolves property with types given.
     */
    public function assert(MappingValue $mappingValue): bool
    {
        if (
            ! $mappingValue->preferredTypeClass
                || $mappingValue->preferredTypeClass === stdClass::class
                || ! class_exists($mappingValue->preferredTypeClass)
                || ! (new ReflectionClass($mappingValue->preferredTypeClass))->isInstantiable()
        ) {
            return false;
        }

        return is_array($mappingValue->data)
            && (is_string(array_key_first($mappingValue->data)) || is_json_structure($mappingValue->data));
    }

    /**
     * Resolve mapper that runs once assert returns true.
     */
    public function resolve(MappingValue $mappingValue): void
    {
        $class = new ReflectionClass($mappingValue->preferredTypeClass);

        $data = [];

        $mappingData = is_string($mappingValue->data) ? json_decode($mappingValue->data, true) : $mappingValue->data;

        $propertiesData = array_combine(
            array_map(fn ($key) => $this->normalisePropertyKey($mappingValue, $key), array_keys($mappingData)),
            array_values($mappingData)
        );

        foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $key = $property->getName();
            $value = $propertiesData[$key] ?? null;

            $type = app(PropertyInfoExtractor::class)->typeInfo($class->getName(), $key);

            /** @var \Illuminate\Support\Collection<\ReflectionAttribute> $propertyAttributes */
            $propertyAttributes = Collection::make($property->getAttributes());

            $containerAttribute = $propertyAttributes->filter(
                fn (ReflectionAttribute $attribute) => is_subclass_of($attribute->getName(), ContextualAttribute::class)
            )->first();

            if ($containerAttribute) {
                $data[$key] = app()->resolveFromAttribute($containerAttribute);

                continue;
            }

            if (is_null($value)) {
                continue;
            }

            $unwrappedType = app(PropertyInfoExtractor::class)->unwrapType($type);

            if ($type instanceof Type\NullableType) {
                $type = $type->getWrappedType();
            }

            if ($type instanceof Type\CollectionType) {
                $collectionValueType = $type->getCollectionValueType();
                
                $data[$key] = map($value)
                    ->through((string) $unwrappedType)
                    ->to((string) $collectionValueType);

                continue;
            }

            $data[$key] = match (true) {
                $type instanceof Type\ObjectType => map($value)->to((string) $type),
                default => $value,
            };
        }

        $mappingValue->data = new $mappingValue->preferredTypeClass(...$data);
    }

    /**
     * Normalise property key using camel case or original.
     */
    protected function normalisePropertyKey(MappingValue $mappingValue, string $key): ?string
    {
        $class = new ReflectionClass($mappingValue->objectClass);

        $normaliseProperty = count($class->getAttributes(NormaliseProperties::class)) > 0
            ?: (app('config')->get('data-mapper.normalise_properties') ?? true);

        if (! $normaliseProperty) {
            return $key;
        }

        if (Str::endsWith($key, '_id')) {
            $key = Str::replaceLast('_id', '', $key);
        }

        $camelKey = Str::camel($key);

        return match (true) {
            property_exists($mappingValue->preferredTypeClass, $key) => $key,
            property_exists($mappingValue->preferredTypeClass, $camelKey) => $camelKey,
            default => null
        };
    }
}
