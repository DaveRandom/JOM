<?php declare(strict_types=1);

namespace DaveRandom\Jom;

use DaveRandom\Jom\Exceptions\InvalidOperationException;
use DaveRandom\Jom\Exceptions\InvalidSubjectNodeException;

abstract class VectorNode extends Node implements \Countable, \IteratorAggregate, \ArrayAccess
{
    /** @var Node|null */
    protected $firstChild;

    /** @var Node|null */
    protected $lastChild;

    /** @var Node[] */
    protected $keyMap = [];

    protected $activeIteratorCount = 0;

    /**
     * @throws InvalidOperationException
     */
    protected function appendNode(Node $node, $key): Node
    {
        if ($this->activeIteratorCount !== 0) {
            throw new InvalidOperationException('Cannot modify a vector with an active iterator');
        }

        if ($node->ownerDocument !== $this->ownerDocument) {
            throw new InvalidSubjectNodeException('Node belongs to a different document');
        }

        if ($node->parent !== null) {
            throw new InvalidSubjectNodeException('Node already present in the document');
        }

        $node->parent = $this;
        $node->key = $key;
        $this->keyMap[$key] = $node;

        $previous = $this->lastChild;

        $this->lastChild = $node;
        $this->firstChild = $this->firstChild ?? $node;

        $node->previousSibling = $previous;

        if ($previous) {
            $previous->nextSibling = $node;
        }

        return $node;
    }

    /**
     * @throws InvalidOperationException
     */
    protected function insertNode(Node $node, $key, Node $beforeNode = null): Node
    {
        if ($beforeNode === null) {
            return $this->appendNode($node, $key);
        }

        if ($this->activeIteratorCount !== 0) {
            throw new InvalidOperationException('Cannot modify a vector with an active iterator');
        }

        if ($node->ownerDocument !== $this->ownerDocument) {
            throw new InvalidSubjectNodeException('Node belongs to a different document');
        }

        if ($node->parent !== null) {
            throw new InvalidSubjectNodeException('Node already present in the document');
        }

        if ($beforeNode->parent !== $this) {
            throw new InvalidSubjectNodeException('Reference node not present in children of this node');
        }

        $node->parent = $this;
        $node->key = $key;
        $this->keyMap[$key] = $node;

        $node->nextSibling = $beforeNode;
        $beforeNode->previousSibling = $node;

        if ($this->firstChild === $beforeNode) {
            $this->firstChild = $node;
        }

        return $node;
    }

    /**
     * @throws InvalidOperationException
     */
    protected function replaceNode(Node $newNode, Node $oldNode): Node
    {
        if ($this->activeIteratorCount !== 0) {
            throw new InvalidOperationException('Cannot modify a vector with an active iterator');
        }

        if ($newNode->ownerDocument !== $this->ownerDocument) {
            throw new InvalidSubjectNodeException('Node belongs to a different document');
        }

        if ($newNode->parent !== null) {
            throw new InvalidSubjectNodeException('Node already present in the document');
        }

        if ($oldNode->parent !== $this) {
            throw new InvalidSubjectNodeException('Reference node not present in children of this node');
        }

        $newNode->parent = $oldNode->parent;
        $newNode->previousSibling = $oldNode->previousSibling;
        $newNode->nextSibling = $oldNode->nextSibling;

        $newNode->key = $oldNode->key;
        $this->keyMap[$oldNode->key] = $newNode;

        if ($oldNode->previousSibling) {
            $oldNode->previousSibling->nextSibling = $newNode;
        }

        if ($oldNode->nextSibling) {
            $oldNode->nextSibling->previousSibling = $newNode;
        }

        $oldNode->key = null;
        $oldNode->parent = null;
        $oldNode->previousSibling = null;
        $oldNode->nextSibling = null;

        return $oldNode;
    }

    /**
     * @throws InvalidOperationException
     */
    protected function removeNode(Node $node): Node
    {
        if ($this->activeIteratorCount !== 0) {
            throw new InvalidOperationException('Cannot modify a vector with an active iterator');
        }

        if ($node->parent !== $this) {
            throw new InvalidSubjectNodeException('Node not present in children of this node');
        }

        if ($this->firstChild === $node) {
            $this->firstChild = $node->nextSibling;
        }

        if ($this->lastChild === $node) {
            $this->lastChild = $node->previousSibling;
        }

        if ($node->previousSibling) {
            $node->previousSibling->nextSibling = $node->nextSibling;
        }

        if ($node->nextSibling) {
            $node->nextSibling->previousSibling = $node->previousSibling;
        }

        $node->parent = null;
        $node->previousSibling = null;
        $node->nextSibling = null;

        unset($this->keyMap[$node->key]);
        $node->key = null;

        return $node;
    }

    public function hasChildren(): bool
    {
        return !empty($this->keyMap);
    }

    public function getFirstChild(): ?Node
    {
        return $this->firstChild;
    }

    public function getLastChild(): ?Node
    {
        return $this->lastChild;
    }

    final public function getIterator(): NodeListIterator
    {
        return new NodeListIterator($this->firstChild, function($state) {
            $this->activeIteratorCount += $state
                ? 1
                : -1;

            \assert($this->activeIteratorCount >= 0, new \Error('Vector node active iterator count is negative'));
        });
    }

    final public function count(): int
    {
        return \count($this->keyMap);
    }

    final public function jsonSerialize(): array
    {
        return \iterator_to_array($this->getIterator());
    }

    public function offsetExists($index): bool
    {
        return isset($this->keyMap[$index]);
    }

    /**
     * @throws InvalidOperationException
     */
    public function offsetUnset($index): void
    {
        if (isset($this->keyMap[$index])) {
            $this->removeNode($this->keyMap[$index]);
        }
    }

    public function toArray(): array
    {
        return (array)$this->getValue();
    }

    abstract public function offsetGet($index): Node;
    abstract public function offsetSet($index, $value): void;
}
