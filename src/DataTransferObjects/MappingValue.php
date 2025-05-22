<?php

namespace OpenSoutheners\LaravelDto\DataTransferObjects;

use Illuminate\Support\Collection;
use OpenSoutheners\LaravelDto\Enums\BuiltInType;
use ReflectionClass;
use ReflectionProperty;
use Symfony\Component\PropertyInfo\Type;

final class MappingValue
{
    public readonly ?Type $preferredType;

    /**
     * @var class-string|null
     */
    public readonly ?string $preferredTypeClass;

    /**
     * @var Collection<\ReflectionAttribute>
     */
    public readonly Collection $attributes;

    /**
     * @param  class-string|null  $objectClass
     * @param  array<Type>|null  $types
     */
    public function __construct(
        public readonly mixed $data,
        public readonly BuiltInType $typeFromData,
        public readonly ?string $objectClass = null,
        public readonly ?array $types = null,
        public readonly ?ReflectionClass $class = null,
        public readonly ?ReflectionProperty $property = null,
    ) {
        $this->preferredType = $types ? (reset($types) ?? null) : null;

        $this->preferredTypeClass = $this->preferredType ? ($this->preferredType->getClassName() ?: $objectClass) : $objectClass;

        $this->attributes = Collection::make($class ? $class->getAttributes() : []);
    }
}
