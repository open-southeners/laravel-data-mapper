<?php

namespace OpenSoutheners\LaravelDto\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Validate
{
    public function __construct(public string $value)
    {
        // 
    }
}
