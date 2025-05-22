<?php

namespace Workbench\App\DataTransferObjects;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use OpenSoutheners\LaravelDto\Attributes\Authenticated;
use OpenSoutheners\LaravelDto\Contracts\DataTransferObject;
use stdClass;
use Workbench\App\Enums\PostStatus;
use Workbench\App\Models\Post;
use Workbench\App\Models\User;

class CreatePostData implements DataTransferObject
{
    public mixed $authorEmail = 'me@d8vjork.com';

    /**
     * @param  \Illuminate\Support\Collection<\Illuminate\Support\Carbon>|null  $dates
     */
    public function __construct(
        public string $title,
        public array|null $tags,
        public PostStatus $postStatus,
        public ?Post $post = null,
        public array|string|null $country = null,
        public $description = '',
        public ?Collection $subscribers = null,
        #[Authenticated]
        public ?User $currentUser = null,
        public ?Carbon $publishedAt = null,
        public ?stdClass $content = null,
        public ?Collection $dates = null,
        $authorEmail = null
    ) {
        $this->tags ??= ['generic', 'post'];
        
        $this->authorEmail = $authorEmail;
    }
}
