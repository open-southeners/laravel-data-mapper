<?php

namespace OpenSoutheners\LaravelDto;

use Symfony\Component\PropertyInfo\Extractor\PhpStanExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor as Extractor;
use Symfony\Component\TypeInfo\Type;

final class PropertyInfoExtractor
{
    private Extractor $extractor;

    public function __construct()
    {
        $phpStanExtractor = new PhpStanExtractor();
        $reflectionExtractor = new ReflectionExtractor();
    
        $this->extractor = new Extractor(
            [$reflectionExtractor],
            [$phpStanExtractor, $reflectionExtractor],
        );
    }
    
    public function typeInfo(string $class, string $property, array $context = []): ?Type
    {
        return $this->extractor->getType($class, $property, $context);
    }
}
