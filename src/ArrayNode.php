<?php declare(strict_types=1);

namespace DaveRandom\Jom;

use DaveRandom\Jom\Exceptions\InvalidKeyException;
use DaveRandom\Jom\Exceptions\InvalidOperationException;

final class ArrayNode extends VectorNode
{
    /**
     * @throws InvalidOperationException
     */
    public function push(Node $node): void
    {
        $this->appendNode($node, \count($this->keyMap));
    }

    /**
     * @throws InvalidOperationException
     */
    public function pop(): ?Node
    {
        $node = $this->lastChild;

        if ($node === null) {
            return null;
        }

        $this->remove($node);

        return $node;
    }

    /**
     * @throws InvalidOperationException
     */
    public function unshift(Node $node): void
    {
        $this->insert($node, $this->firstChild);
    }

    /**
     * @throws InvalidOperationException
     */
    public function shift(): ?Node
    {
        $node = $this->firstChild;

        if ($node === null) {
            return null;
        }

        $this->remove($node);

        return $node;
    }

    /**
     * @throws InvalidOperationException
     */
    public function insert(Node $node, ?Node $beforeNode): void
    {
        if ($beforeNode === null) {
            $this->appendNode($node, \count($this->keyMap));
            return;
        }

        $key = $beforeNode !== null
            ? $beforeNode->key
            : \count($this->keyMap);

        $this->insertNode($node, $key, $beforeNode);

        while ($beforeNode !== null) {
            $beforeNode->key++;
            $beforeNode = $beforeNode->nextSibling;
        }
    }

    /**
     * @throws InvalidOperationException
     */
    public function remove(Node $node): void
    {
        $next = $node->nextSibling;

        $this->removeNode($node);

        while ($next !== null) {
            $next->key--;
            $next = $next->nextSibling;
        }
    }

    /**
     * @throws InvalidKeyException
     */
    public function offsetGet($index): Node
    {
        if (!isset($this->keyMap[$index])) {
            throw new InvalidKeyException("Index '{$index}' is outside the bounds of the array");
        }

        return $this->keyMap[$index];
    }

    /**
     * @throws InvalidOperationException
     */
    public function offsetSet($index, $value): void
    {
        if (!($value instanceof Node)) {
            throw new \TypeError('Child must be instance of ' . Node::class);
        }

        if ($index === null) {
            $this->push($value);
            return;
        }

        if (!\is_int($index) && !\ctype_digit($index)) {
            throw new \TypeError('Index must be an integer');
        }

        $index = (int)$index;

        if (isset($this->keyMap[$index])) {
            $this->replaceNode($value, $this->keyMap[$index]);
        }

        if (!isset($this->keyMap[$index - 1])) {
            throw new InvalidKeyException("Index '{$index}' is outside the bounds of the array");
        }

        $this->push($value);
    }

    public function getValue(): array
    {
        $result = [];

        foreach ($this as $value) {
            $result[] = $value->getValue();
        }

        return $result;
    }

    public function toArray(): array
    {
        return $this->getValue();
    }
}
