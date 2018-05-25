<?php declare(strict_types=1);

namespace DaveRandom\Jom;

final class NumberNode extends Node
{
    private $value;

    /**
     * @param int|float $value
     */
    public function __construct(Document $ownerDocument, $value)
    {
        parent::__construct($ownerDocument);

        $this->setValue($value);
    }

    /**
     * @return int|float
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param int|float $value
     */
    public function setValue($value): void
    {
        if (!\is_int($value) && !\is_float($value)) {
            throw new \TypeError('Number node value must be an integer or a double');
        }

        $this->value = $value;
    }

    /**
     * @return int|float
     */
    public function jsonSerialize()
    {
        return $this->value;
    }
}
