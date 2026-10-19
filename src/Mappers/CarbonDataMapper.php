<?php

namespace OpenSoutheners\LaravelDataMapper\Mappers;

use Carbon\CarbonInterface;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use OpenSoutheners\LaravelDataMapper\MappingValue;

class CarbonDataMapper extends DataMapper
{
    public function supports(MappingValue $mappingValue): bool
    {
        $isDateTarget = is_a($mappingValue->objectClass, CarbonInterface::class, true)
            || in_array($mappingValue->objectClass, [DateTimeInterface::class, DateTime::class, DateTimeImmutable::class], true);

        return $isDateTarget
            && (is_string($mappingValue->data) || is_int($mappingValue->data) || is_iterable($mappingValue->data));
    }

    public function resolve(MappingValue $mappingValue): mixed
    {
        return is_array($mappingValue->data) || $mappingValue->data instanceof Collection
            ? Collection::make($mappingValue->data)->map(fn ($item) => $this->resolveCarbon($item, $mappingValue->objectClass))
            : $this->resolveCarbon($mappingValue->data, $mappingValue->objectClass);
    }

    protected function resolveCarbon($value, string $objectClass): CarbonInterface
    {
        $carbonObject = match (true) {
            gettype($value) === 'integer' || is_numeric($value) => Carbon::createFromTimestamp($value),
            default => Carbon::make($value),
        };

        // Carbon extends the native classes, so `DateTime`/`DateTimeInterface`
        // targets get a `Carbon` and `DateTimeImmutable` ones a `CarbonImmutable`.
        if (is_a($objectClass, DateTimeImmutable::class, true)) {
            return $carbonObject->toImmutable();
        }

        return $carbonObject;
    }
}
