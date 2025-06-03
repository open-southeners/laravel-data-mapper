<?php

namespace OpenSoutheners\LaravelDto\DataTransferObjects;

use Illuminate\Support\Collection;
use Symfony\Component\TypeInfo\Type;

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
     * @param  class-string|null  $collectClass
     * @param  array<Type>|null  $types
     */
    public function __construct(
        public mixed $data,
        public readonly array $allMappingData,
        
        public readonly ?string $objectClass = null,
        public readonly ?string $collectClass = null,
        
        public readonly ?array $types = null,
    ) {
        $this->preferredType = $types ? (reset($types) ?? null) : null;

        $this->preferredTypeClass = $this->preferredType ? ($this->preferredType->getClassName() ?: $objectClass) : $objectClass;

        // if ($property) {
        //     $this->attributes = Collection::make($property->getAttributes());
        // } else {
        //     $this->attributes = Collection::make($class ? $class->getAttributes() : []);
        // }
    }
}
