<?php

namespace OpenSoutheners\LaravelDataMapper;

use ArrayAccess;
use Countable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\Conditionable;
use ReflectionClass;
use ReflectionProperty;

final class Mapper
{
    use Conditionable;
    
    protected mixed $data;

    protected ?string $dataClass = null;

    protected ?string $throughClass = null;

    public function __construct(mixed $input)
    {
        if ((is_array($input) || $input instanceof Countable) && count($input) === 1) {
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
            $input instanceof Collection => $input,
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
        
        if (!$this->throughClass && (is_array($this->data) || $this->data instanceof Collection)) {
            $this->throughClass = is_array($this->data) ? 'array' : Collection::class;
        }
        
        $mappingValue = new MappingValue(
            data: $this->data,
            objectClass: $output,
            collectClass: $this->throughClass,
        );
        
        $mapper = Collection::make(ServiceProvider::getMappers())
            ->map(fn ($mapper) => ['mapper' => $mapper, 'score' => $mapper->score($mappingValue)])
            ->sortByDesc('score')
            // ->dd()
            ->first();
        
        // dump($mappingValue);
        // dump($mapper);
        // if ($this->data instanceof Collection) {
        //     return;
        // }
        // 
        if (!$mapper || $mapper['score'] === 0) {
            return $mappingValue->data;
        }
        
        $mapper = $mapper['mapper'];

        return $mapper($mappingValue);
    }
}
