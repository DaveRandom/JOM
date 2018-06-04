<?php declare(strict_types=1);

namespace DaveRandom\Jom;

function unexpected(\Exception $e): \Error
{
    return new \Error(\sprintf(
        'Unexpected %s thrown in %s on line %d: %s',
        \get_class($e), $e->getFile(), $e->getLine(), $e->getMessage()
    ), $e->getCode(), $e);
}
