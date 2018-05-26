<?php declare(strict_types=1);

namespace DaveRandom\Jom;

final class BooleanNode extends Node
{
    private $value;

    public function __construct(?Document $ownerDocument = null, bool $value = false)
    {
        parent::__construct($ownerDocument);

        $this->setValue($value);
    }

    public function getValue(): bool
    {
        return $this->value;
    }

    public function setValue(bool $value): void
    {
        $this->value = $value;
    }

    public function jsonSerialize(): bool
    {
        return $this->value;
    }
}
