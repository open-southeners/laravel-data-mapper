<?php

namespace OpenSoutheners\LaravelDto\Mappers;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use OpenSoutheners\LaravelDto\DataTransferObjects\MappingValue;
use OpenSoutheners\LaravelDto\Enums\BuiltInType;

final class CarbonDataMapper implements DataMapper
{
    /**
     * Assert that this mapper resolves property with types given.
     */
    public function assert(MappingValue $mappingValue): bool
    {
        return $mappingValue->typeFromData->assert(BuiltInType::String, BuiltInType::Integer)
            && ($mappingValue->preferredTypeClass === CarbonInterface::class
                || is_subclass_of($mappingValue->preferredTypeClass, CarbonInterface::class));
    }

    /**
     * Resolve mapper that runs once assert returns true.
     */
    public function resolve(MappingValue $mappingValue): mixed
    {
        $dateValue = match (true) {
            $mappingValue->typeFromData === BuiltInType::Integer || is_numeric($mappingValue->data) => Carbon::createFromTimestamp($mappingValue->data),
            default => Carbon::make($mappingValue->data),
        };

        if ($mappingValue->preferredTypeClass === CarbonImmutable::class) {
            $dateValue = $dateValue->toImmutable();
        }

        return $dateValue;
    }
}
