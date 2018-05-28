<?php declare(strict_types=1);

namespace DaveRandom\Jom;

use DaveRandom\Jom\Exceptions\InvalidKeyException;
use DaveRandom\Jom\Exceptions\WriteOperationForbiddenException;
use DaveRandom\Jom\Exceptions\InvalidSubjectNodeException;

final class ArrayNode extends VectorNode
{
    private function incrementKeys(?Node $current): void
    {
        while ($current !== null) {
            $this->keyMap[++$current->key] = $current;
            $current = $current->nextSibling;
        }
    }

    private function decrementKeys(?Node $current): void
    {
        while ($current !== null) {
            unset($this->keyMap[$current->key]);
            $this->keyMap[--$current->key] = $current;
            $current = $current->nextSibling;
        }
    }

    /**
     * @throws WriteOperationForbiddenException
     * @throws InvalidSubjectNodeException
     */
    public function push(Node $node): void
    {
        $this->appendNode($node, \count($this->keyMap));
    }

    /**
     * @throws WriteOperationForbiddenException
     */
    public function pop(): ?Node
    {
        $node = $this->lastChild;

        if ($node === null) {
            return null;
        }

        try {
            $this->remove($node);
        } catch (InvalidSubjectNodeException $e) {
            \assert(false, new \Error("Unexpected InvalidSubjectNodeException", 0, $e));
        }

        return $node;
    }

    /**
     * @throws WriteOperationForbiddenException
     * @throws InvalidSubjectNodeException
     */
    public function unshift(Node $node): void
    {
        $this->insert($node, $this->firstChild);
    }

    /**
     * @throws WriteOperationForbiddenException
     */
    public function shift(): ?Node
    {
        $node = $this->firstChild;

        if ($node === null) {
            return null;
        }

        try {
            $this->remove($node);
        } catch (InvalidSubjectNodeException $e) {
            \assert(false, new \Error("Unexpected InvalidSubjectNodeException", 0, $e));
        }

        return $node;
    }

    /**
     * @throws WriteOperationForbiddenException
     * @throws InvalidSubjectNodeException
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

        $this->incrementKeys($beforeNode);
    }

    /**
     * @param Node|int $nodeOrIndex
     * @throws WriteOperationForbiddenException
     * @throws InvalidSubjectNodeException
     * @throws InvalidKeyException
     */
    public function replace($nodeOrIndex, Node $newNode): void
    {
        $this->replaceNode($this->resolveNode($nodeOrIndex), $newNode);
    }

    /**
     * @throws WriteOperationForbiddenException
     * @throws InvalidSubjectNodeException
     */
    public function remove(Node $node): void
    {
        $next = $node->nextSibling;

        $this->removeNode($node);

        $this->decrementKeys($next);
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
     * @throws WriteOperationForbiddenException
     * @throws InvalidSubjectNodeException
     * @throws InvalidKeyException
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
