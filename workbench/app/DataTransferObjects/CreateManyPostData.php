<?php

namespace Workbench\App\DataTransferObjects;

use Illuminate\Support\Collection;

class CreateManyPostData
{
    /**
     * @param  \Illuminate\Support\Collection<\Workbench\App\DataTransferObjects\CreatePostData>  $posts
     */
    public function __construct(public Collection $posts)
    {
        //
    }
}
