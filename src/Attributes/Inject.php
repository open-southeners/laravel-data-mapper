<?php

namespace OpenSoutheners\LaravelDto\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Inject
{
    public function __construct(public string $value)
    {
        // 
    }
}
