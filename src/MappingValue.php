<?php

namespace OpenSoutheners\LaravelDataMapper;

final class MappingValue
{
    public readonly mixed $originalData;
    
    /**
     * @param  class-string|string|null  $objectClass
     * @param  class-string|string|null  $collectClass
     */
    public function __construct(
        public mixed $data,
        public readonly ?string $objectClass = null,
        public readonly ?string $collectClass = null,
    ) {
        $this->originalData = $data;
    }
}
