<?php

namespace OpenSoutheners\LaravelDto;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Collection;
use OpenSoutheners\LaravelDto\DataTransferObjects\MappingValue;
use OpenSoutheners\LaravelDto\Enums\BuiltInType;
use ReflectionClass;
use ReflectionProperty;

final class Mapper
{
    protected mixed $data;

    protected ?string $dataClass = null;

    protected ?string $throughClass = null;
    
    protected ?MappingValue $fromMappingValue = null;
    
    protected ?string $property = null;

    protected array $propertyTypes = [];
    
    protected bool $runningFromMapper = false;

    public function __construct(mixed $input)
    {
        if (is_array($input) && count($input) === 1) {
            $input = reset($input);
        }
        
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
     * Map values through class.
     */
    public function through(string $class): static
    {
        $this->throughClass = $class;
        
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
        $output ??= $this->dataClass;

        // if (is_array($this->data)) {
        //     $reflectionClass = $this->fromMappingValue?->class ?? $output ? new ReflectionClass($output) : null;
        // } else {
        //     $reflectionClass = $output ? new ReflectionClass($output) : null;
        // }

        $mappingDataValue = new MappingValue(
            data: $this->data,
            allMappingData: (!$this->runningFromMapper ? $this->fromMappingValue?->allMappingData : $this->data) ?? [],
            types: $this->propertyTypes,
            objectClass: $output,
            collectClass: $this->throughClass,
        );

        return app(Pipeline::class)
            ->through(ServiceProvider::getMappers())
            ->send($mappingDataValue)
            ->then(fn (MappingValue $mappingValue) => $mappingValue->data);
    }
}
