<?php declare(strict_types=1);

namespace DaveRandom\Jom\Exceptions;

use DaveRandom\Jom\Pointer;

final class PointerReferenceNotFoundException extends Exception
{
    public function __construct(string $message, Pointer $pointer, ?int $level = null, ?\Throwable $previous = null)
    {
        $message = \sprintf("%s while evaluating pointer '%s'", $message, (string)$pointer);

        if ($level !== null) {
            $message .= \sprintf(" at component '%s' (path component index %d)", $pointer->getPath()[$level], $level);
        }

        parent::__construct($message, 0, $previous);
    }
}
