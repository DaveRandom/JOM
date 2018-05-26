<?php declare(strict_types=1);

namespace DaveRandom\Jom;

final class StringNode extends Node
{
    private $value;

    public function __construct(?Document $ownerDocument = null, string $value = '')
    {
        parent::__construct($ownerDocument);

        $this->setValue($value);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
