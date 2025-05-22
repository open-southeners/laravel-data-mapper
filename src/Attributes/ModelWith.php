<?php

namespace OpenSoutheners\LaravelDto\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class ModelWith
{
    public function __construct(public string|array $relations, public ?string $type = null)
    {
        //
    }
}
