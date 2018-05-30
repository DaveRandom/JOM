<?php declare(strict_types=1);

namespace DaveRandom\Jom;

use DaveRandom\Jom\Exceptions\InvalidKeyException;
use DaveRandom\Jom\Exceptions\InvalidReferenceNodeException;
use DaveRandom\Jom\Exceptions\InvalidSubjectNodeException;
use DaveRandom\Jom\Exceptions\WriteOperationForbiddenException;

abstract class VectorNode extends Node implements \Countable, \IteratorAggregate, \ArrayAccess
{
    /** @var Node|null */
    protected $firstChild;

    /** @var Node|null */
    protected $lastChild;

    /** @var Node[] */
    protected $children = [];

    protected $activeIteratorCount = 0;

    /**
     * @throws InvalidKeyException
     */
    protected function resolveNode($nodeOrKey): Node
    {
        if ($nodeOrKey instanceof Node) {
            return $nodeOrKey;
        }

        if (isset($this->children[$nodeOrKey])) {
            return $this->children[$nodeOrKey];
        }

        throw new InvalidKeyException("{$nodeOrKey} does not reference a valid child node");
    }

    /**
     * @throws WriteOperationForbiddenException
     * @throws InvalidSubjectNodeException
     */
    protected function appendNode(Node $node, $key): Node
    {
        if ($this->activeIteratorCount !== 0) {
            throw new WriteOperationForbiddenException('Cannot modify a vector with an active iterator');
        }

        if ($node->ownerDocument !== $this->ownerDocument) {
            throw new InvalidSubjectNodeException('Node belongs to a different document');
        }

        if ($node->parent !== null) {
            throw new InvalidSubjectNodeException('Node already present in the document');
        }

        $node->parent = $this;
        $node->key = $key;
        $this->children[$key] = $node;

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
     * @throws WriteOperationForbiddenException
     * @throws InvalidSubjectNodeException
     * @throws InvalidReferenceNodeException
     */
    protected function insertNode(Node $node, $key, Node $beforeNode = null): Node
    {
        if ($beforeNode === null) {
            return $this->appendNode($node, $key);
        }

        if ($this->activeIteratorCount !== 0) {
            throw new WriteOperationForbiddenException('Cannot modify a vector with an active iterator');
        }

        if ($node->ownerDocument !== $this->ownerDocument) {
            throw new InvalidSubjectNodeException('Node belongs to a different document');
        }

        if ($node->parent !== null) {
            throw new InvalidSubjectNodeException('Node already present in the document');
        }

        if ($beforeNode->parent !== $this) {
            throw new InvalidReferenceNodeException('Reference node not present in children of this node');
        }

        $node->parent = $this;
        $node->key = $key;
        $this->children[$key] = $node;

        $node->nextSibling = $beforeNode;
        $beforeNode->previousSibling = $node;

        if ($this->firstChild === $beforeNode) {
            $this->firstChild = $node;
        }

        return $node;
    }

    /**
     * @throws WriteOperationForbiddenException
     * @throws InvalidSubjectNodeException
     * @throws InvalidReferenceNodeException
     */
    protected function replaceNode(Node $newNode, Node $oldNode): Node
    {
        if ($this->activeIteratorCount !== 0) {
            throw new WriteOperationForbiddenException('Cannot modify a vector with an active iterator');
        }

        if ($newNode->ownerDocument !== $this->ownerDocument) {
            throw new InvalidSubjectNodeException('Node belongs to a different document');
        }

        if ($newNode->parent !== null) {
            throw new InvalidSubjectNodeException('Node already present in the document');
        }

        if ($oldNode->parent !== $this) {
            throw new InvalidReferenceNodeException('Reference node not present in children of this node');
        }

        $newNode->parent = $oldNode->parent;
        $newNode->previousSibling = $oldNode->previousSibling;
        $newNode->nextSibling = $oldNode->nextSibling;

        $newNode->key = $oldNode->key;
        $this->children[$oldNode->key] = $newNode;

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
     * @throws WriteOperationForbiddenException
     * @throws InvalidSubjectNodeException
     */
    protected function removeNode(Node $node): Node
    {
        if ($this->activeIteratorCount !== 0) {
            throw new WriteOperationForbiddenException('Cannot modify a vector with an active iterator');
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

        unset($this->children[$node->key]);
        $node->key = null;

        return $node;
    }

    public function hasChildren(): bool
    {
        return !empty($this->children);
    }

    public function getFirstChild(): ?Node
    {
        return $this->firstChild;
    }

    public function getLastChild(): ?Node
    {
        return $this->lastChild;
    }

    final public function clear(): void
    {
        try {
            while ($this->lastChild !== null) {
                $this->removeNode($this->lastChild);
            }
        } catch (\Exception $e) {
            throw new \Error('Unexpected ' . \get_class($e) . ": {$e->getMessage()}", 0, $e);
        }
    }

    final public function getIterator(): NodeListIterator
    {
        return new NodeListIterator($this->firstChild, function($state) {
            $this->activeIteratorCount += $state === NodeListIterator::INACTIVE
                ? -1
                : 1;

            \assert($this->activeIteratorCount >= 0, new \Error('Vector node active iterator count is negative'));
        });
    }

    final public function count(): int
    {
        return \count($this->children);
    }

    final public function jsonSerialize(): array
    {
        return \iterator_to_array($this->getIterator());
    }

    public function offsetExists($key): bool
    {
        return isset($this->children[$key]);
    }

    /**
     * @throws WriteOperationForbiddenException
     * @throws InvalidSubjectNodeException
     */
    public function offsetUnset($key): void
    {
        if (isset($this->children[$key])) {
            $this->removeNode($this->children[$key]);
        }
    }

    abstract public function toArray(): array;
    abstract public function offsetGet($index): Node;
    abstract public function offsetSet($index, $value): void;
}
