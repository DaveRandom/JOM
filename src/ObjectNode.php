<?php declare(strict_types=1);

namespace DaveRandom\Jom;

use DaveRandom\Jom\Exceptions\InvalidKeyException;
use DaveRandom\Jom\Exceptions\InvalidOperationException;

final class ObjectNode extends VectorNode
{
    public function hasProperty(string $name): bool
    {
        return isset($this->keyMap[$name]);
    }

    /**
     * @throws InvalidKeyException
     */
    public function getProperty(string $name): Node
    {
        if (!isset($this->keyMap[$name])) {
            throw new InvalidKeyException("Property '{$name}' does not exist on the object");
        }

        return $this->keyMap[$name];
    }

    /**
     * @throws InvalidOperationException
     */
    public function setProperty(string $name, Node $value): void
    {
        if (isset($this->keyMap[$name])) {
            $this->replaceNode($value, $this->keyMap[$name]);
        } else {
            $this->appendNode($value, $name);
        }
    }

    /**
     * @throws InvalidOperationException
     */
    public function removeProperty(string $name): void
    {
        if (!isset($this->keyMap[$name])) {
            throw new InvalidKeyException("Property '{$name}' does not exist on the object");
        }

        $this->removeNode($this->keyMap[$name]);
    }

    /**
     * @throws InvalidKeyException
     */
    public function offsetGet($propertyName): Node
    {
        return $this->getProperty((string)$propertyName);
    }

    /**
     * @throws InvalidOperationException
     */
    public function offsetSet($propertyName, $value): void
    {
        if (!($value instanceof Node)) {
            throw new \TypeError('Child must be instance of ' . Node::class);
        }

        $this->setProperty((string)$propertyName, $value);
    }

    public function getValue(): \stdClass
    {
        $result = new \stdClass;

        foreach ($this as $name => $value) {
            $result->$name = $value->getValue();
        }

        return $result;
    }

    public function toArray(): array
    {
        $result = [];

        foreach ($this as $value) {
            $result[] = $value instanceof VectorNode
                ? $value->toArray()
                : $value->getValue();
        }

        return $result;
    }
}
