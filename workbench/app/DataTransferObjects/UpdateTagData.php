<?php

namespace Workbench\App\DataTransferObjects;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use OpenSoutheners\LaravelDto\Attributes\Authenticated;
use OpenSoutheners\LaravelDto\Attributes\BindModel;
use OpenSoutheners\LaravelDto\Attributes\ResolveModel;
use OpenSoutheners\LaravelDto\Attributes\Validate;
use OpenSoutheners\LaravelDto\Attributes\WithDefaultValue;
use OpenSoutheners\LaravelDto\Contracts\DataTransferObject;
use Workbench\App\Http\Requests\TagUpdateFormRequest;
use Workbench\App\Models\Film;
use Workbench\App\Models\Post;
use Workbench\App\Models\Tag;
use Workbench\App\Models\User;

#[Validate(TagUpdateFormRequest::class)]
class UpdateTagData implements DataTransferObject
{
    /**
     * @param \Illuminate\Support\Collection<\Workbench\App\Models\Post|\Workbench\App\Models\Film> $taggable
     */
    public function __construct(
        public Tag $tag,
        #[ResolveModel([Post::class => 'slug', Film::class])]
        public Collection $taggable,
        public array $taggableType,
        public string $name,
        #[Authenticated]
        public User $authUser
    ) {
        //
    }
}
