<?php

namespace OpenSoutheners\LaravelDto\Mappers;

use Illuminate\Contracts\Container\ContextualAttribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use OpenSoutheners\LaravelDto\Attributes\NormaliseProperties;
use OpenSoutheners\LaravelDto\DataTransferObjects\MappingValue;
use ReflectionAttribute;
use ReflectionClass;
use stdClass;
use Symfony\Component\PropertyInfo\Extractor\PhpStanExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\Type;

use function OpenSoutheners\ExtendedPhp\Strings\is_json_structure;
use function OpenSoutheners\LaravelDto\map;

final class ObjectDataMapper implements DataMapper
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
    public function resolve(MappingValue $mappingValue): mixed
    {
        $data = [];
        $propertiesInfo = self::getPropertiesInfoFrom($mappingValue->preferredTypeClass);

        $mappingData = is_string($mappingValue->data) ? json_decode($mappingValue->data, true) : $mappingValue->data;

        $propertiesData = array_combine(
            array_map(fn ($key) => $this->normalisePropertyKey($mappingValue, $key), array_keys($mappingData)),
            array_values($mappingData)
        );

        foreach ($propertiesInfo as $key => $propertyTypes) {
            $value = $propertiesData[$key] ?? null;

            if (count($propertyTypes) === 0) {
                $data[$key] = $value;

                continue;
            }

            /** @var \Illuminate\Support\Collection<\ReflectionAttribute> $propertyAttributes */
            $propertyAttributes = Collection::make(
                $mappingValue->class->getProperty($key)->getAttributes()
            );

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

            $preferredType = $propertyTypes[0] ?? null;
            $propertyTypesClasses = array_filter(array_map(fn (Type $type) => $type->getClassName(), $propertyTypes));
            $preferredTypeClass = $preferredType->getClassName();

            if (
                $preferredTypeClass
                    && ! is_array($value)
                    && ! $preferredType->isCollection()
                    && $preferredTypeClass !== Collection::class
                    && ! is_a($preferredTypeClass, Model::class, true)
                    && (is_a($value, $preferredTypeClass, true)
                        || (is_object($value) && in_array(get_class($value), $propertyTypesClasses)))
            ) {
                $data[$key] = $value;

                continue;
            }

            $data[$key] = map($value)
                ->through($mappingValue, $key, $propertyTypes)
                ->to($preferredTypeClass);
        }

        return new $mappingValue->preferredTypeClass(...$data);
    }

    /**
     * Normalise property key using camel case or original.
     */
    protected function normalisePropertyKey(MappingValue $mappingValue, string $key): ?string
    {
        $normaliseProperty = count($mappingValue->class->getAttributes(NormaliseProperties::class)) > 0
            ?: (app('config')->get('data-transfer-objects.normalise_properties') ?? true);

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

    /**
     * Get instance of property info extractor.
     *
     * @return array<string, array<Type>>
     */
    public static function getPropertiesInfoFrom(string $class, ?string $property = null): array
    {
        $phpStanExtractor = new PhpStanExtractor;
        $reflectionExtractor = new ReflectionExtractor;

        $extractor = new PropertyInfoExtractor(
            [$reflectionExtractor],
            [$phpStanExtractor, $reflectionExtractor],
        );

        if ($property) {
            return [$property => $extractor->getTypes($class, $property) ?? []];
        }

        $propertiesInfo = [];

        foreach ($extractor->getProperties($class) as $key) {
            $propertiesInfo[$key] = $extractor->getTypes($class, $key) ?? [];
        }

        return $propertiesInfo;
    }
}
