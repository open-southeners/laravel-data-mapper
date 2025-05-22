<?php

namespace Workbench\App\DataTransferObjects;

use OpenSoutheners\LaravelDto\Contracts\DataTransferObject;

class CreateComment implements DataTransferObject
{
    public function __construct(
        public string $content,
        public array $tags
    ) {
        //
    }
}
