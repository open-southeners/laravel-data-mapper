<?php

namespace OpenSoutheners\LaravelDto\Enums;

enum BuiltInType: string
{
    case Object = 'object';
    case Array = 'array';
    case Iterable = 'iterable';

    case Integer = 'integer';
    case Float = 'float';

    case String = 'string';

    case Boolean = 'bool';
    case True = 'true';
    case False = 'false';

    case Null = 'null';
    case Never = 'never';
    case Void = 'void';

    case Mixed = 'mixed';

    case Callable = 'callable';
    case Resource = 'resource';

    public static function guess($value): static
    {
        return self::tryFrom(gettype($value)) ?? self::Mixed;
    }

    public function assert(...$types): bool
    {
        $loops = 0;
        $truth = false;

        while ($truth === false && count($types) > $loops) {
            $type = $types[$loops];

            $truth = $type instanceof static ? $type === $this : $type === $this->value;
            $loops++;
        }

        return $truth;
    }
}
