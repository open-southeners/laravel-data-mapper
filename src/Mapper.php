<?php

namespace OpenSoutheners\LaravelDto;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use OpenSoutheners\LaravelDto\DataTransferObjects\MappingValue;
use OpenSoutheners\LaravelDto\Enums\BuiltInType;
use ReflectionClass;
use ReflectionProperty;

final class Mapper
{
    protected mixed $data;

    protected ?string $dataClass = null;

    protected ?MappingValue $fromMappingValue = null;

    protected ?string $property = null;

    protected array $propertyTypes = [];
    
    protected bool $runningFromMapper = false;

    public function __construct(mixed $input)
    {
        if (is_object($input)) {
            $this->dataClass = get_class($input);
        }

        $this->data = $this->takeDataFrom($input);
    }

    protected function extractProperties(object $input): array
    {
        $reflector = new ReflectionClass($input);
        $extraction = [];

        foreach ($reflector->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $property->isReadOnly();
            $extraction[$property->getName()] = $property->getValue($input);
        }

        return $extraction;
    }

    protected function takeDataFrom(mixed $input): mixed
    {
        return match (true) {
            $input instanceof Request => array_merge(
                is_object($input->route()) ? $input->route()->parameters() : [],
                $input instanceof FormRequest ? $input->validated() : $input->all()
            ),
            $input instanceof Collection => $input->all(),
            $input instanceof Model => $input,
            is_object($input) => $this->extractProperties($input),
            default => $input,
        };
    }

    /**
     * @param  array<\Symfony\Component\PropertyInfo\Type>  $types
     *
     * @internal
     */
    public function through(MappingValue $mappingValue, string $property, array $types): static
    {
        $this->fromMappingValue = $mappingValue;

        $this->property = $property;

        $this->propertyTypes = $types;
        
        $this->runningFromMapper = true;

        return $this;
    }

    /**
     * @template T of object
     *
     * @param  class-string<T>  $output
     * @return T
     */
    public function to(?string $output = null)
    {
        // TODO: Move to ModelMapper class
        // if ($output && is_a($output, Model::class, true)) {
        //     /** @var Model $model */
        //     $model = new $output;

        //     foreach ($this->data as $key => $value) {
        //         if ($model->isRelation($key) && $model->$key() instanceof BelongsTo) {
        //             $model->$key()->associate($value);
        //         }

        //         $model->fill([$key => $value]);
        //     }

        //     return $model;
        // }

        $output ??= $this->dataClass;

        $mappedValue = $this->data;

        if (is_array($mappedValue)) {
            $reflectionClass = $this->fromMappingValue?->class ?? $output ? new ReflectionClass($output) : null;
        } else {
            $reflectionClass = $output ? new ReflectionClass($output) : null;
        }

        $mappingDataValue = new MappingValue(
            data: $this->data,
            allMappingData: ($this->runningFromMapper ? $this->fromMappingValue?->allMappingData : $this->data) ?? [],
            typeFromData: BuiltInType::guess($this->data),
            types: $this->propertyTypes,
            objectClass: $output,
            class: $reflectionClass,
            property: $this->fromMappingValue?->property ?? ($this->property ? $reflectionClass->getProperty($this->property) : null)
        );

        foreach (ServiceProvider::getMappers() as $mapper) {
            if ($mapper->assert($mappingDataValue)) {
                $mappedValue = $mapper->resolve($mappingDataValue);

                break;
            }
        }

        return $mappedValue;
    }
}
