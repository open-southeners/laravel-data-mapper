<?php

namespace OpenSoutheners\LaravelDto;

use Exception;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use OpenSoutheners\LaravelDto\Attributes\BindModel;
use OpenSoutheners\LaravelDto\Attributes\WithDefaultValue;
use Symfony\Component\PropertyInfo\Type;

abstract class SerializableObject implements Arrayable
{
    /**
     * Check if the following property is filled.
     */
    public function filled(string $property): bool
    {
        /** @var \Illuminate\Http\Request $request */
        $request = app(Request::class);
        $camelProperty = Str::camel($property);

        if (app()->get('dto.context.booted') === static::class && $request->route()) {
            $requestHasProperty = $request->has(Str::snake($property))
                ?: $request->has($property)
                ?: $request->has($camelProperty);

            if (! $requestHasProperty && $request->route() instanceof Route) {
                return $request->route()->hasParameter($property)
                    ?: $request->route()->hasParameter($camelProperty);
            }

            return $requestHasProperty;
        }

        $reflection = new \ReflectionClass($this);

        $classProperty = match (true) {
            $reflection->hasProperty($property) => $property,
            $reflection->hasProperty($camelProperty) => $camelProperty,
            default => throw new Exception("Properties '{$property}' or '{$camelProperty}' doesn't exists on class instance."),
        };

        [$classPropertyTypes] = ObjectMapper::getPropertiesInfoFrom(get_class($this), $classProperty);

        $reflectionProperty = $reflection->getProperty($classProperty);
        $propertyValue = $reflectionProperty->getValue($this);

        if ($classPropertyTypes === null) {
            return function_exists('filled') && filled($propertyValue);
        }

        $propertyDefaultValue = $reflectionProperty->getDefaultValue();

        $propertyIsNullable = in_array(true, array_map(fn (Type $type) => $type->isNullable(), $classPropertyTypes), true);

        /**
         * Not filled when DTO property's default value is set to null while none is passed through
         */
        if (! $propertyValue && $propertyIsNullable && $propertyDefaultValue === null) {
            return false;
        }

        /**
         * Not filled when property isn't promoted and does have a default value matching value sent
         *
         * @see problem with promoted properties and hasDefaultValue/getDefaultValue https://bugs.php.net/bug.php?id=81386
         */
        if (! $reflectionProperty->isPromoted() && $reflectionProperty->hasDefaultValue() && $propertyValue === $propertyDefaultValue) {
            return false;
        }

        return true;
    }

    /**
     * Get the instance as an array.
     *
     * @return array<string, mixed>
     */
    public function toArray()
    {
        /** @var array<\ReflectionProperty> $properties */
        $properties = (new \ReflectionClass($this))->getProperties(\ReflectionProperty::IS_PUBLIC);
        $newPropertiesArr = [];

        foreach ($properties as $property) {
            if (! $this->filled($property->name) && count($property->getAttributes(WithDefaultValue::class)) === 0) {
                continue;
            }

            $propertyValue = $property->getValue($this) ?? $property->getDefaultValue();

            if ($propertyValue instanceof Arrayable) {
                $propertyValue = $propertyValue->toArray();
            }

            if ($propertyValue instanceof \stdClass) {
                $propertyValue = (array) $propertyValue;
            }

            $newPropertiesArr[Str::snake($property->name)] = $propertyValue;
        }

        return $newPropertiesArr;
    }

    public function __serialize(): array
    {
        $reflection = new \ReflectionClass($this);

        /** @var array<\ReflectionProperty> $properties */
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

        $serialisableArr = [];

        foreach ($properties as $property) {
            $key = $property->getName();
            $value = $property->getValue($this);

            /** @var array<\ReflectionAttribute<\OpenSoutheners\LaravelDto\Attributes\BindModel>> $propertyModelBindingAttribute */
            $propertyModelBindingAttribute = $property->getAttributes(BindModel::class);
            $propertyModelBindingAttribute = reset($propertyModelBindingAttribute);

            $propertyModelBindingAttributeName = null;

            if ($propertyModelBindingAttribute) {
                $propertyModelBindingAttributeName = $propertyModelBindingAttribute->newInstance()->using;
            }

            $serialisableArr[$key] = match (true) {
                $value instanceof Model => $value->getAttribute($propertyModelBindingAttributeName ?? $value->getRouteKeyName()),
                $value instanceof Collection => $value->first() instanceof Model ? $value->map(fn (Model $model) => $model->getAttribute($propertyModelBindingAttributeName ?? $model->getRouteKeyName()))->join(',') : $value->join(','),
                $value instanceof Arrayable => $value->toArray(),
                $value instanceof \Stringable => (string) $value,
                is_array($value) => head($value) instanceof Model ? implode(',', array_map(fn (Model $model) => $model->getAttribute($propertyModelBindingAttributeName ?? $model->getRouteKeyName()), $value)) : implode(',', $value),
                default => $value,
            };
        }

        return $serialisableArr;
    }

    /**
     * Called during unserialization of the object.
     */
    public function __unserialize(array $data): void
    {
        $properties = (new \ReflectionClass($this))->getProperties(\ReflectionProperty::IS_PUBLIC);

        $propertiesMapper = new ObjectMapper(array_merge($data), static::class);

        $data = $propertiesMapper->run();

        foreach ($properties as $property) {
            $key = $property->getName();

            $this->{$key} = $data[$key] ?? $property->getDefaultValue();
        }
    }
}
