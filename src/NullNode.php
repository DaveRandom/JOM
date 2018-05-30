<?php declare(strict_types=1);

namespace DaveRandom\Jom;

final class NullNode extends Node
{
    public function __construct(?Document $ownerDocument = null)
    {
        parent::__construct($ownerDocument);
    }

    public function jsonSerialize()
    {
        return null;
    }

    public function getValue()
    {
        return null;
    }
}
