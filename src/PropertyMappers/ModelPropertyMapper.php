<?php

namespace OpenSoutheners\LaravelDto\PropertyMappers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use OpenSoutheners\LaravelDto\Attributes\ModelWith;
use OpenSoutheners\LaravelDto\Attributes\ResolveModel;
use ReflectionAttribute;
use Symfony\Component\PropertyInfo\Type;

final class ModelPropertyMapper implements PropertyMapper
{
    /**
     * Assert that this mapper resolves property with types given.
     */
    public function assert(Type $preferredType, mixed $value): bool
    {
        return $preferredType->getClassName() === Model::class
            || is_subclass_of($preferredType->getClassName(), Model::class);
    }
    
    /**
     * Resolve mapper that runs once assert returns true.
     *
     * @param array<Type> $types
     * @param Collection<\ReflectionAttribute> $attributes
     */
    public function resolve(array $types, string $key, mixed $value, Collection $attributes, array $properties): mixed
    {
        /** @var \ReflectionAttribute<\OpenSoutheners\LaravelDto\Attributes\BindModel>|null $resolveModelAttribute */
        $resolveModelAttribute = $attributes
            ->filter(fn (ReflectionAttribute $reflection) => $reflection->getName() === ResolveModel::class)
            ->first();

        /** @var \OpenSoutheners\LaravelDto\Attributes\BindModel|null $resolveModelAttribute */
        $resolveModelAttribute = $resolveModelAttribute
            ? $resolveModelAttribute->newInstance()
            : new ResolveModel(morphTypeFrom: ResolveModel::getDefaultMorphKeyFrom($key));

        /** @var array<string, string[]>|null $modelWithAttributes */
        $modelWithAttributes = $attributes
            ->filter(fn (ReflectionAttribute $reflection) => $reflection->getName() === ModelWith::class)
            ->mapWithKeys(fn (ReflectionAttribute $reflection) => [$reflection->newInstance()->type => $reflection->newInstance()->relations])
            ->toArray();
            
        $modelClass = Collection::make($types)
            ->map(fn (Type $type): string => $type->getClassName())
            ->filter(fn (string $typeClass): bool => is_a($typeClass, Model::class, true))
            ->unique()
            ->values()
            ->toArray();
        
        $modelType = count($modelClass) === 1 ? reset($modelClass) : $modelClass;
        $valueClass = null;

        if (is_object($value) && ! $value instanceof Collection) {
            $valueClass = get_class($value);
            $modelType = is_array($types) ? ($modelClass[$valueClass] ?? null) : $valueClass;
        }

        if (
            (! is_array($modelType) && $modelType === Model::class)
            || ($resolveModelAttribute && is_array($modelType))
        ) {
            $modelType = $resolveModelAttribute->getMorphModel(
                $key,
                $properties,
                $types === Model::class ? [] : (array) $types
            );
        }

        if (! is_countable($modelType) || count($modelType) === 1) {
            return $this->resolveIntoModelInstance(
                $value,
                ! is_countable($modelType) ? $modelType : $modelType[0],
                $key,
                $modelWithAttributes,
                $resolveModelAttribute
            );
        }

        return Collection::make(array_map(
            function (mixed $valueA, mixed $valueB) use (&$lastNonValue): array {
                if (!is_null($valueB)) {
                    $lastNonValue = $valueB;
                }

                return [$valueA, $valueB ?? $lastNonValue];
            },
            $value instanceof Collection ? $value->all() : (array) $value,
            (array) $modelType
        ))->mapToGroups(fn (array $value) => [$value[1] => $value[0]])->flatMap(fn (Collection $keys, string $model) =>
            $this->resolveIntoModelInstance($keys, $model, $key, $modelWithAttributes, $resolveModelAttribute)
        );
    }
    
    /**
     * Get model instance(s) for model class and given IDs.
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $model
     * @param  string|int|array|\Illuminate\Database\Eloquent\Model  $id
     * @param  string|\Illuminate\Database\Eloquent\Model  $usingAttribute
     */
    protected function getModelInstance(string $model, mixed $id, mixed $usingAttribute, array $with)
    {
        if (is_a($usingAttribute, $model)) {
            return $usingAttribute;
        }

        if (is_a($id, $model)) {
            return empty($with) ? $id : $id->loadMissing($with);
        }

        $baseQuery = $model::query()->when(
            $usingAttribute,
            fn (Builder $query) => is_iterable($id) ? $query->whereIn($usingAttribute, $id) : $query->where($usingAttribute, $id),
            fn (Builder $query) => $query->whereKey($id)
        );

        if (count($with) > 0) {
            $baseQuery->with($with);
        }

        if (is_iterable($id)) {
            return $baseQuery->get();
        }

        return $baseQuery->first();
    }
    
    /**
     * Resolve model class strings and keys into instances.
     *
     * @param array<string, string[]> $withAttributes
     */
    protected function resolveIntoModelInstance(mixed $keys, string $modelClass, string $propertyKey, array $withAttributes = [], ?ResolveModel $bindingAttribute = null): mixed
    {
        $usingAttribute = null;
        $with = [];

        if ($bindingAttribute) {
            $with = $withAttributes[$modelClass] ?? [];
            $usingAttribute = $bindingAttribute->getBindingAttribute($propertyKey, $modelClass, $with);
        }

        return $this->getModelInstance($modelClass, $keys, $usingAttribute, $with);
    }
}
