<?php

namespace OpenSoutheners\LaravelDataMapper\Contracts;

use OpenSoutheners\LaravelDataMapper\MappingValue;

interface MapeableObject
{
    public function mappingFrom(MappingValue $mappingValue): void;
}
