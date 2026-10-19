<?php

namespace OpenSoutheners\LaravelDataMapper\Mappers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as DatabaseCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use OpenSoutheners\LaravelDataMapper\Attributes\ResolveModel;
use OpenSoutheners\LaravelDataMapper\MappingValue;
use ReflectionMethod;
use ReflectionNamedType;

use function OpenSoutheners\LaravelDataMapper\map;

class ModelDataMapper extends DataMapper
{
    public function supports(MappingValue $mappingValue): bool
    {
        return is_a($mappingValue->objectClass, Model::class, true)
            && (
                is_array($mappingValue->data)
                || is_string($mappingValue->data)
                || is_int($mappingValue->data)
                || $mappingValue->data instanceof Collection
            );
    }

    /**
     * Resolve mapper that runs once supports returns true.
     */
    public function resolve(MappingValue $mappingValue): mixed
    {
        if (is_array($mappingValue->data) && Arr::isAssoc($mappingValue->data)) {
            /** @var Model $modelInstance */
            $modelInstance = new $mappingValue->objectClass;

            foreach ($mappingValue->data as $key => $value) {
                $relation = $this->relationFor($modelInstance, $key);

                if ($relation instanceof BelongsTo) {
                    $relation->associate($value);

                    continue;
                }

                if ($relation instanceof HasMany) {
                    $modelInstance->setRelation($key, map($value)->to(get_class($relation->getModel())));

                    continue;
                }

                $modelInstance->fill([$key => $value]);
            }

            return $modelInstance;
        }

        $data = $mappingValue->data;

        if ($data instanceof Collection) {
            /** @var class-string<Model> $modelClass */
            $modelClass = $mappingValue->objectClass;

            // A collection made up entirely of already-hydrated instances of the
            // target (e.g. a `Collection<Post>` property fed real `Post` models
            // instead of ids) is mapped item-by-item so each one short-circuits
            // via instance passthrough instead of being queried again. A
            // collection of plain ids/keys instead falls through to the bulk
            // query below, so a scalar-id list still issues a single `whereIn`
            // query rather than one `whereKey` query per item.
            if ($data->isNotEmpty() && $data->every(fn ($item) => $item instanceof $modelClass)) {
                $resolved = $data->map(fn ($item) => map($item)->to($modelClass));

                return $mappingValue->collectClass === 'array' ? $resolved->all() : $resolved;
            }

            $data = $data->all();
        }

        if (is_string($data) && str_contains($data, ',')) {
            $data = array_filter(explode(',', $data));
        }

        $data = $this->resolveIntoModelInstance($data, $mappingValue->objectClass);

        // A single key (e.g. `'2'`) still resolves into one model (or `null`),
        // but a caller asking for a list gets a list of it rather than the
        // model itself, whose own `all()`/`toArray()` mean something else.
        if ($mappingValue->collectClass !== null && ! $data instanceof DatabaseCollection) {
            $data = (new $mappingValue->objectClass)->newCollection($data ? [$data] : []);
        }

        if ($mappingValue->collectClass === Collection::class) {
            $data = $data->toBase();
        }

        if ($mappingValue->collectClass === 'array') {
            $data = $data->all();
        }

        return $data;

        // TODO: Move to ObjectDataMapper
        // if (count($mappingValue->types) <= 1) {
        //     $mappingValue->data = $this->resolveIntoModelInstance($mappingValue->data, $mappingValue->objectClass);
        // }

        // $resolveModelAttributeReflector = $mappingValue->property->getAttributes(ResolveModel::class);

        // /** @var \ReflectionAttribute<\OpenSoutheners\LaravelDataMapper\Attributes\ResolveModel>|null $resolveModelAttributeReflector */
        // $resolveModelAttributeReflector = reset($resolveModelAttributeReflector);

        // /** @var \OpenSoutheners\LaravelDataMapper\Attributes\ResolveModel|null $resolveModelAttribute */
        // $resolveModelAttribute = $resolveModelAttributeReflector
        //     ? $resolveModelAttributeReflector->newInstance()
        //     : new ResolveModel(morphTypeFrom: ResolveModel::getDefaultMorphKeyFrom($mappingValue->property->getName()));

        // $modelClass = Collection::make($mappingValue->types ?? [$mappingValue->preferredTypeClass])
        //     ->map(fn (Type $type): string => $type->getClassName())
        //     ->filter(fn (string $typeClass): bool => is_a($typeClass, Model::class, true))
        //     ->unique()
        //     ->values()
        //     ->toArray();

        // $modelType = count($modelClass) === 1 ? reset($modelClass) : $modelClass;
        // $valueClass = null;

        // /** @var array<string, string[]>|null $modelWithAttributes */
        // $modelWithAttributes = $mappingValue->attributes
        //     ->filter(fn (ReflectionAttribute $reflection) => $reflection->getName() === ModelWith::class)
        //     ->mapWithKeys(fn (ReflectionAttribute $reflection) => [$reflection->newInstance()->type ?? $modelType => $reflection->newInstance()->relations])
        //     ->toArray();

        // if (is_array($modelType) && $mappingValue->objectClass === Collection::class) {
        //     $valueClass = get_class($data);

        //     $modelType = $modelClass[array_search($valueClass, $modelClass)];
        // }

        // if (
        //     (! is_array($modelType) && $modelType === Model::class)
        //     || ($resolveModelAttribute && is_array($modelType))
        // ) {
        //     $modelType = $resolveModelAttribute->getMorphModel(
        //         $mappingValue->property->getName(),
        //         $mappingValue->allMappingData,
        //         $mappingValue->types === Model::class ? [] : (array) $mappingValue->types
        //     );
        // }

        // if (! is_countable($modelType) || count($modelType) === 1) {
        //     return $this->resolveIntoModelInstance(
        //         $data,
        //         ! is_countable($modelType) ? $modelType : $modelType[0],
        //         $mappingValue->property->getName(),
        //         $modelWithAttributes,
        //         $resolveModelAttribute
        //     );
        // }

        // return Collection::make(
        //     array_map(
        //         function (mixed $valueA, mixed $valueB) use (&$lastNonValue): array {
        //             if (! is_null($valueB)) {
        //                 $lastNonValue = $valueB;
        //             }

        //             return [$valueA, $valueB ?? $lastNonValue];
        //         },
        //         $data,
        //         (array) $modelType
        //     )
        // )
        // ->mapToGroups(fn (array $value) => [$value[1] => $value[0]])
        // ->flatMap(fn (Collection $keys, string $model) => $this->resolveIntoModelInstance($keys, $model, $mappingValue->property->getName(), $modelWithAttributes, $resolveModelAttribute));
    }

    /**
     * Get the relation an input key names on the model, if any.
     *
     * Input keys are untrusted, so a key only counts as a relation when it's a
     * registered relation resolver or a public, argument-less method whose
     * declared return type is a `Relation`. Eloquent's own `isRelation()` is
     * just `method_exists()`, which would let a key like `save` or `delete`
     * invoke that method.
     *
     * @return Relation<Model, Model, mixed>|null
     */
    protected function relationFor(Model $model, string $key): ?Relation
    {
        if ($model->relationResolver($model::class, $key)) {
            $relation = $model->$key();

            return $relation instanceof Relation ? $relation : null;
        }

        if (! method_exists($model, $key)) {
            return null;
        }

        $method = new ReflectionMethod($model, $key);
        $returnType = $method->getReturnType();

        if (
            ! $method->isPublic()
            || $method->isStatic()
            || $method->getNumberOfRequiredParameters() > 0
            || ! $returnType instanceof ReflectionNamedType
            || ! is_a($returnType->getName(), Relation::class, true)
        ) {
            return null;
        }

        $relation = $model->$key();

        return $relation instanceof Relation ? $relation : null;
    }

    /**
     * Get model instance(s) for model class and given IDs.
     *
     * @param  class-string<Model>  $model
     * @param  string|int|array|Model  $id
     * @param  string|Model  $usingAttribute
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
     * @param  array<string, string[]>  $withAttributes
     */
    protected function resolveIntoModelInstance(mixed $keys, string $modelClass, ?string $propertyKey = null, array $withAttributes = [], ?ResolveModel $bindingAttribute = null): mixed
    {
        $usingAttribute = null;
        $with = [];

        if ($bindingAttribute && $propertyKey) {
            $with = $withAttributes[$modelClass] ?? [];
            $usingAttribute = $bindingAttribute->getBindingAttribute($propertyKey, $modelClass, $with);
        }

        return $this->getModelInstance($modelClass, $keys, $usingAttribute, $with);
    }
}
