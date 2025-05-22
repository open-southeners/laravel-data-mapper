<?php

namespace Workbench\App\DataTransferObjects;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use OpenSoutheners\LaravelDto\Attributes\AsType;
use OpenSoutheners\LaravelDto\Attributes\Authenticated;
use OpenSoutheners\LaravelDto\Attributes\ModelWith;
use OpenSoutheners\LaravelDto\Attributes\Validate;
use OpenSoutheners\LaravelDto\Contracts\DataTransferObject;
use stdClass;
use Workbench\App\Enums\PostStatus;
use Workbench\App\Http\Requests\PostUpdateFormRequest;
use Workbench\App\Models\Post;
use Workbench\App\Models\User;

#[AsType('UpdatePostFormData')]
#[Validate(PostUpdateFormRequest::class)]
class UpdatePostWithRouteBindingData implements DataTransferObject
{
    /**
     * @param  \Illuminate\Support\Collection<\Workbench\App\Models\Tag>|null  $tags
     */
    public function __construct(
        #[ModelWith(['tags'])]
        public Post $post,
        public ?string $title = null,
        public ?stdClass $content = null,
        public ?PostStatus $postStatus = null,
        public ?Collection $tags = null,
        public ?CarbonImmutable $publishedAt = null,
        #[Authenticated]
        public ?User $currentUser = null
    ) {
        //
    }
}
