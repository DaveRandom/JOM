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
 * @param mixed $value The value to describe
 * @return string The class name if the value is an object, otherwise the type name
 * @internal
 */
function describe($value): string
{
    return \is_object($value)
        ? \get_class($value)
        : \gettype($value);
}

/**
 * Invoke the private static __init() method for a class
 * @internal
 */
function initialize(string $class): void
{
    (\Closure::bind(function($target) {
        $target();
    }, null, $class))([$class, '__init']);
}
