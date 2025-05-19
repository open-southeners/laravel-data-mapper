<?php

namespace OpenSoutheners\LaravelDto;

function map(mixed $input): DynamicMapper {
    return new DynamicMapper($input);
}
