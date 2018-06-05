<?php declare(strict_types=1);

namespace DaveRandom\Jom;

/**
 * @internal
 */
function unexpected(\Exception $e): \Error
{
    return new \Error(\sprintf(
        'Unexpected %s thrown in %s on line %d: %s',
        \get_class($e), $e->getFile(), $e->getLine(), $e->getMessage()
    ), $e->getCode(), $e);
}

/**
 * @internal
 */
function describe($value): string
{
    return \is_object($value)
        ? \get_class($value)
        : \gettype($value);
}
