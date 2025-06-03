<?php

namespace OpenSoutheners\LaravelDto;

function map(mixed ...$input): Mapper
{
    return new Mapper($input);
}
