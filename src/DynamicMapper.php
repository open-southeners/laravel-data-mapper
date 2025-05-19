<?php

namespace OpenSoutheners\LaravelDto;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use ReflectionClass;
use ReflectionProperty;

final class DynamicMapper
{
    protected array $data;
    
    protected ?string $dataClass = null;
    
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
            $extraction[$property->getName()] = $property->getValue($input);
        }
        
        return $extraction;
    }
    
    protected function takeDataFrom(mixed $input): array
    {
        return match (true) {
            $input instanceof Request => array_merge(
                is_object($input->route()) ? $input->route()->parameters() : [],
                $input instanceof FormRequest ? $input->validated() : $input->all()
            ),
            is_object($input) => $this->extractProperties($input),
            default => (array) $input,
        };
    }
    
    /**
     * @template T
     * @param class-string<T>
     * @return T
     */
    public function to(?string $output = null)
    {
        if ($output && is_a($output, Model::class, true)) {
            /** @var Model $model */
            $model = new $output;
            
            foreach ($this->data as $key => $value) {
                if ($model->isRelation($key) && $model->$key() instanceof BelongsTo) {
                    $model->$key()->associate($value);
                }
                
                $model->fill([$key => $value]);
            }
            
            return $model;
        }
        
        $output ??= $this->dataClass;
        
        $propertiesMapper = new ObjectMapper($this->data, $output);
        
        return new $output(...$propertiesMapper->run());
    }
}
