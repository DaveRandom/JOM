<?php declare(strict_types=1);

namespace DaveRandom\Jom;

final class NullNode extends Node
{
    public function jsonSerialize()
    {
        return null;
    }

    public function getValue()
    {
        return null;
    }
}
