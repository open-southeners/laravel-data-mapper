<?php

namespace OpenSoutheners\LaravelDto;

use Illuminate\Contracts\Container\ContextualAttribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use OpenSoutheners\LaravelDto\Attributes\Inject;
use OpenSoutheners\LaravelDto\Attributes\NormaliseProperties;
use OpenSoutheners\LaravelDto\Attributes\WithDefaultValue;
use OpenSoutheners\LaravelDto\PropertyMappers\PropertyMapper;
use ReflectionAttribute;
use ReflectionClass;
use Symfony\Component\PropertyInfo\Extractor\PhpStanExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\Type;

class ObjectMapper
{
    protected ReflectionClass $reflector;
    
    /**
     * @var array<PropertyMapper>
     */
    protected static array $mappers = [];
    
    /**
     * Get instance of property info extractor.
     */
    public static function registerMapper(array|PropertyMapper $mappers)
    {
        static::$mappers = array_merge(static::$mappers, $mappers);
    }
    
    /**
     * Get instance of property info extractor.
     *
     * @return array<string, array<Type>>
     */
    public static function getPropertiesInfoFrom(string $class, ?string $property = null): array
    {
        $phpStanExtractor = new PhpStanExtractor();
        $reflectionExtractor = new ReflectionExtractor();

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

    public function __construct(
        protected array $properties,
        protected string $dataClass,
        protected array $data = []
    ) {
        $this->reflector = new ReflectionClass($this->dataClass);
    }
    
    /**
     * Run properties mapper through all sent class properties.
     */
    public function run(): array
    {
        $propertiesInfo = static::getPropertiesInfoFrom($this->dataClass);

        $propertiesData = array_combine(
            array_map(fn ($key) => $this->normalisePropertyKey($key), array_keys($this->properties)),
            array_values($this->properties)
        );

        foreach ($propertiesInfo as $key => $propertyTypes) {
            $value = $propertiesData[$key] ?? null;

            if (count($propertyTypes) === 0) {
                $this->data[$key] = $value;

                continue;
            }

            /** @var \Illuminate\Support\Collection<\ReflectionAttribute> $propertyAttributes */
            $propertyAttributes = Collection::make(
                $this->reflector->getProperty($key)->getAttributes()
            );

            $containerAttribute = $propertyAttributes->filter(
                fn (ReflectionAttribute $attribute) => is_subclass_of($attribute->getName(), ContextualAttribute::class)
            )->first();
            
            if ($containerAttribute) {
                $this->data[$key] = app()->resolveFromAttribute($containerAttribute);
                
                continue;
            }

            if (is_null($value)) {
                continue;
            }

            $preferredType = reset($propertyTypes);
            $propertyTypesClasses = array_filter(array_map(fn (Type $type) => $type->getClassName(), $propertyTypes));
            $preferredTypeClass = $preferredType->getClassName();
            
            if (
                count(static::$mappers) === 0
                    || ($preferredTypeClass
                        && ! is_array($value)
                        && ! $preferredType->isCollection()
                        && $preferredTypeClass !== Collection::class
                        && ! is_a($preferredTypeClass, Model::class, true)
                        && (is_a($value, $preferredTypeClass, true)
                            || (is_object($value) && in_array(get_class($value), $propertyTypesClasses))))
            ) {
                $this->data[$key] = $value;

                continue;
            }
            
            $this->data[$key] = map($value)->to($preferredTypeClass);
        }

        return $this->data;
    }
    
    public function mapFromType(string $key, Type $preferredType, mixed $value, array $types, Collection $attributes): mixed
    {
        $mappedValue = $value;
        
        foreach (static::$mappers as $mapper) {
            if ($mapper->assert($preferredType, $value)) {
                $mappedValue = $mapper->resolve($types, $key, $value, $attributes, $this->properties);
                
                break;
            }
        }
        
        return $mappedValue;
    }

    /**
     * Normalise property key using camel case or original.
     */
    protected function normalisePropertyKey(string $key): ?string
    {
        $normaliseProperty = count($this->reflector->getAttributes(NormaliseProperties::class)) > 0
            ?: (app('config')->get('data-transfer-objects.normalise_properties') ?? true);

        if (! $normaliseProperty) {
            return $key;
        }

        if (Str::endsWith($key, '_id')) {
            $key = Str::replaceLast('_id', '', $key);
        }

        $camelKey = Str::camel($key);

        return match (true) {
            property_exists($this->dataClass, $key) => $key,
            property_exists($this->dataClass, $camelKey) => $camelKey,
            default => null
        };
    }
}
