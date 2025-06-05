<?php

namespace OpenSoutheners\LaravelDataMapper\Mappers;

use Closure;
use OpenSoutheners\LaravelDataMapper\MappingValue;

abstract class DataMapper
{
    /**
     * Assert that this mapper resolves property with types given.
     */
    abstract public function assert(MappingValue $mappingValue): bool;

    /**
     * Resolve mapper that runs once assert returns true.
     */
    abstract public function resolve(MappingValue $mappingValue): void;

    public function __invoke(MappingValue $mappingValue, Closure $next)
    {
        if (! $this->assert($mappingValue)) {
            return $next($mappingValue);
        }

        $this->resolve($mappingValue);

        return $next($mappingValue);
    }
}
