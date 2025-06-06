<?php

namespace OpenSoutheners\LaravelDataMapper\Mappers;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use OpenSoutheners\LaravelDataMapper\MappingValue;

final class CarbonDataMapper extends DataMapper
{
    public function assert(MappingValue $mappingValue): array
    {
        return [
            is_a($mappingValue->objectClass, CarbonInterface::class, true),
            in_array(gettype($mappingValue->data), ['string', 'integer'], true),
            is_iterable($mappingValue->data),
        ];
    }

    public function resolve(MappingValue $mappingValue): void
    {
        $mappingValue->data = is_array($mappingValue->data) || $mappingValue->data instanceof Collection
            ? Collection::make($mappingValue->data)->map(fn ($item) => $this->resolveCarbon($item, $mappingValue->objectClass))
            : $this->resolveCarbon($mappingValue->data, $mappingValue->objectClass);
    }
    
    private function resolveCarbon($value, string $objectClass): CarbonInterface
    {
        $carbonObject = match (true) {
            gettype($value) === 'integer' || is_numeric($value) => Carbon::createFromTimestamp($value),
            default => Carbon::make($value),
        };

        if ($objectClass === CarbonImmutable::class) {
            return $carbonObject->toImmutable();
        }
        
        return $carbonObject;
    }
}
