<?php

namespace Workbench\App\DataTransferObjects;

use Illuminate\Support\Collection;
use OpenSoutheners\LaravelDto\Contracts\DataTransferObject;
use Workbench\App\Models\Post;

class UpdatePostWithTags implements DataTransferObject
{
    public function __construct(
        public Post $post,
        public string $title,
        public Collection $tags
    ) {}
}
