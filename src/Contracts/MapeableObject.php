<?php

namespace OpenSoutheners\LaravelDto\Contracts;

use OpenSoutheners\LaravelDto\DataTransferObjects\MappingValue;

interface MapeableObject
{
    public function mappingFrom(MappingValue $mappingValue): void;
}
