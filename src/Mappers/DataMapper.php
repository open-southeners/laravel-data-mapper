<?php

namespace OpenSoutheners\LaravelDataMapper\Mappers;

use Closure;
use Illuminate\Support\Facades\Log;
use OpenSoutheners\LaravelDataMapper\MappingValue;

abstract class DataMapper
{
    /**
     * Assertions count that this mapper resolves property with types given.
     * 
     * @return array<boolean>
     */
    abstract public function assert(MappingValue $mappingValue): array;

    /**
     * Resolve mapper that runs once assert returns true.
     */
    abstract public function resolve(MappingValue $mappingValue): void;
    
    public function score(MappingValue $mappingValue): float
    {
        $assertions = $this->assert($mappingValue);
        
        $total = count($assertions);
        
        $positive = count(array_filter($assertions));
        
        if ($total === 0) {
            return 0.0;
        }
        
        return $positive / $total;
    }
    
    public function __invoke(MappingValue $mappingValue)
    {
        if (config('app.debug')) {
            Log::withContext([
                'mappingData' => $mappingValue->data,
                'toClass' => $mappingValue->objectClass,
                'throughClass' => $mappingValue->collectClass,
            ])->info('Mapping using class: '.static::class);
        }
        
        $this->resolve($mappingValue);
        
        return $mappingValue->data;
    }
}
