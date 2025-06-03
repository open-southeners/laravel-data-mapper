<?php

namespace OpenSoutheners\LaravelDto\Mappers;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use OpenSoutheners\LaravelDto\DataTransferObjects\MappingValue;

final class CarbonDataMapper extends DataMapper
{
    /**
     * Assert that this mapper resolves property with types given.
     */
    public function assert(MappingValue $mappingValue): bool
    {
        return in_array(gettype($mappingValue->data), ['string', 'integer'], true)
            && ($mappingValue->preferredTypeClass === CarbonInterface::class
                || is_subclass_of($mappingValue->preferredTypeClass, CarbonInterface::class));
    }

    /**
     * Resolve mapper that runs once assert returns true.
     */
    public function resolve(MappingValue $mappingValue): void
    {
        $mappingValue->data = match (true) {
            gettype($mappingValue->data) === 'integer' || is_numeric($mappingValue->data) => Carbon::createFromTimestamp($mappingValue->data),
            default => Carbon::make($mappingValue->data),
        };

        if ($mappingValue->preferredTypeClass === CarbonImmutable::class) {
            $mappingValue->data = $mappingValue->data->toImmutable();
        }
    }
}
