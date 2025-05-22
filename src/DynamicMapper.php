<?php

namespace OpenSoutheners\LaravelDto;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use OpenSoutheners\LaravelDto\DataTransferObjects\MappingValue;
use OpenSoutheners\LaravelDto\Enums\BuiltInType;
use ReflectionClass;
use ReflectionProperty;

final class DynamicMapper
{
    protected mixed $data;
    
    protected ?string $dataClass = null;
    
    protected ?string $parentClass = null;
    
    protected ?string $property = null;
    
    protected array $propertyTypes = [];
    
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
            is_object($input) => $this->extractProperties($input),
            default => $input,
        };
    }
    
    public function through(string|object $value, string $property, array $types): static
    {
        $this->parentClass = is_object($value) ? get_class($value) : $value;
        
        $this->property = $property;
        
        $this->propertyTypes = $types;
        
        return $this;
    }
    
    /**
     * @template T of object
     * @param class-string<T> $output
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
        
        $reflectionClass = $this->parentClass || $output ? new ReflectionClass($this->parentClass ?: $output) : null;
        $reflectionProperty = $reflectionClass && $this->property ? $reflectionClass->getProperty($this->property) : null;
        
        $mappingDataValue = new MappingValue(
            data: $this->data,
            typeFromData: BuiltInType::guess($this->data),
            types: $this->propertyTypes,
            objectClass: $output,
            class: $reflectionClass,
            property: $reflectionProperty,
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
