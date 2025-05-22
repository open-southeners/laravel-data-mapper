<?php

namespace OpenSoutheners\LaravelDto\Attributes;

use Attribute;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Container\ContextualAttribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Inject implements ContextualAttribute
{
    public function __construct(public string $value)
    {
        // 
    }
    
    /**
     * Resolve the currently authenticated user.
     *
     * @param  self  $attribute
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public static function resolve(self $attribute, Container $container)
    {
        return $container->make($attribute->value);
    }
}
