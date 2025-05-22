<?php

namespace Workbench\App\DataTransferObjects;

use Illuminate\Support\Collection;
use OpenSoutheners\LaravelDto\Contracts\DataTransferObject;

class CreateManyPostData implements DataTransferObject
{
    /**
     * @param  \Illuminate\Support\Collection<\Workbench\App\DataTransferObjects\CreatePostData>  $posts
     */
    public function __construct(public Collection $posts)
    {
        //
    }
}
