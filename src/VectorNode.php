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

    /** @var int */
    protected $activeIteratorCount = 0;

    /**
     * @throws WriteOperationForbiddenException
     */
    private function checkWritable(): void
    {
        if ($this->activeIteratorCount !== 0) {
            throw new WriteOperationForbiddenException('Cannot modify a vector with an active iterator');
        }
    }

    /**
     * @throws InvalidSubjectNodeException
     */
    private function checkSubjectNodeIsOrphan(Node $node): void
    {
        if ($node->parent !== null) {
            throw new InvalidSubjectNodeException('Node already present in the document');
        }
    }

    /**
     * @throws InvalidSubjectNodeException
     */
    private function checkSubjectNodeHasSameOwner(Node $node): void
    {
        if ($node->ownerDocument !== $this->ownerDocument) {
            throw new InvalidSubjectNodeException('Node belongs to a different document');
        }

    }

    /**
     * @throws InvalidSubjectNodeException
     */
    private function checkSubjectNodeIsChild(Node $node): void
    {
        if ($node->parent !== $this) {
            throw new InvalidSubjectNodeException('Node not present in children of this node');
        }

    }

    /**
     * @throws InvalidReferenceNodeException
     */
    private function checkReferenceNodeIsChild(Node $node): void
    {
        if ($node->parent !== $this) {
            throw new InvalidReferenceNodeException('Reference node not present in children of this node');
        }

    }

    private function updateFirstChildIfChanged(?Node $newNode, ?Node $oldNode): void
    {
        if ($this->firstChild === $oldNode) {
            $this->firstChild = $newNode;
        }
    }

    private function updateLastChildIfChanged(?Node $newNode, ?Node $oldNode): void
    {
        if ($this->lastChild === $oldNode) {
            $this->lastChild = $newNode;
        }
    }

    private function setNodePreviousSibling(?Node $node, ?Node $newSiblingNode): void
    {
        if ($node !== null) {
            $node->previousSibling = $newSiblingNode;
        }
    }

    private function setNodeNextSibling(?Node $node, ?Node $newSiblingNode): void
    {
        if ($node !== null) {
            $node->nextSibling = $newSiblingNode;
        }
    }

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
        // Prevent modifying a collection with an active iterator
        $this->checkWritable();

        // Validate arguments
        $this->checkSubjectNodeHasSameOwner($node);
        $this->checkSubjectNodeIsOrphan($node);

        // Update first/last child pointers
        $this->updateFirstChildIfChanged($node, null);
        $previousSibling = $this->lastChild;
        $this->lastChild = $node;

        // Update next sibling pointer of old $lastChild (no next sibling node to update)
        $this->setNodeNextSibling($previousSibling, $node);

        // Add the child to the key map
        $this->children[$key] = $node;

        // Set references on new child
        $node->setReferences($this, $key, $previousSibling, null);

        return $node;
    }

    /**
     * @throws WriteOperationForbiddenException
     * @throws InvalidSubjectNodeException
     * @throws InvalidReferenceNodeException
     */
    protected function insertNode(Node $node, $key, ?Node $before = null): Node
    {
        // A null $before reference means push the node on to the end of the list
        if ($before === null) {
            return $this->appendNode($node, $key);
        }

        // Prevent modifying a collection with an active iterator
        $this->checkWritable();

        // Validate arguments
        $this->checkSubjectNodeHasSameOwner($node);
        $this->checkSubjectNodeIsOrphan($node);
        $this->checkReferenceNodeIsChild($before);

        // Update first child pointer (last child pointer is not affected)
        $this->updateFirstChildIfChanged($node, $before);

        // Update next sibling pointer of previous sibling of $before
        $this->setNodeNextSibling($before->previousSibling, $node);

        // Replace the child in the key map
        $this->children[$key] = $node;

        // Set references on new child
        $node->setReferences($this, $key, $before->previousSibling, $before);

        // Update references on ref child
        $before->setReferences($before->parent, $before->key, $node, $before->nextSibling);

        return $node;
    }

    /**
     * @throws WriteOperationForbiddenException
     * @throws InvalidSubjectNodeException
     * @throws InvalidReferenceNodeException
     */
    protected function replaceNode(Node $newNode, Node $oldNode): Node
    {
        // Prevent modifying a collection with an active iterator
        $this->checkWritable();

        // Validate arguments
        $this->checkSubjectNodeHasSameOwner($newNode);
        $this->checkSubjectNodeIsOrphan($newNode);
        $this->checkReferenceNodeIsChild($oldNode);

        // Update first/last child pointers
        $this->updateFirstChildIfChanged($newNode, $oldNode);
        $this->updateLastChildIfChanged($newNode, $oldNode);

        // Update sibling pointers of sibling nodes
        $this->setNodeNextSibling($oldNode->previousSibling, $newNode);
        $this->setNodePreviousSibling($oldNode->nextSibling, $newNode);

        // Replace the node in the key map
        $this->children[$oldNode->key] = $newNode;

        // Copy references from old node to new node
        $newNode->setReferences($oldNode->parent, $oldNode->key, $oldNode->previousSibling, $oldNode->nextSibling);

        // Clear references from old node
        $oldNode->setReferences(null, null, null, null);

        return $oldNode;
    }

    /**
     * @throws WriteOperationForbiddenException
     * @throws InvalidSubjectNodeException
     */
    protected function removeNode(Node $node): Node
    {
        // Prevent modifying a collection with an active iterator
        $this->checkWritable();

        // Validate arguments
        $this->checkSubjectNodeIsChild($node);

        // Update first/last child pointers
        $this->updateFirstChildIfChanged($node->nextSibling, $node);
        $this->updateLastChildIfChanged($node->previousSibling, $node);

        // Update sibling pointers of sibling nodes
        $this->setNodeNextSibling($node->previousSibling, $node->nextSibling);
        $this->setNodePreviousSibling($node->nextSibling, $node->previousSibling);

        // Remove the node from the key map
        unset($this->children[$node->key]);

        // Clear references from node
        $node->setReferences(null, null, null, null);

        return $node;
    }

    final public function __clone()
    {
        parent::__clone();

        // Get an iterator for the original collection's child nodes
        $children = $this->firstChild !== null
            ? $this->firstChild->parent->getIterator()
            : [];

        // Reset the child ref properties
        $this->firstChild = null;
        $this->lastChild = null;
        $this->children = [];
        $this->activeIteratorCount = 0;

        // Loop the original child nodes and append clones of them
        foreach ($children as $key => $child) {
            try {
                $this->appendNode(clone $child, $key);
            //@codeCoverageIgnoreStart
            } catch (\Exception $e) {
                throw new \Error('Unexpected ' . \get_class($e) . ": {$e->getMessage()}", $e->getCode(), $e);
            }
            //@codeCoverageIgnoreEnd
        }
    }

    final public function hasChildren(): bool
    {
        return !empty($this->children);
    }

    final public function getFirstChild(): ?Node
    {
        return $this->firstChild;
    }

    final public function getLastChild(): ?Node
    {
        return $this->lastChild;
    }

    final public function clear(): void
    {
        try {
            while ($this->lastChild !== null) {
                $this->removeNode($this->lastChild);
            }
        //@codeCoverageIgnoreStart
        } catch (\Exception $e) {
            throw new \Error('Unexpected ' . \get_class($e) . ": {$e->getMessage()}", 0, $e);
        }
        //@codeCoverageIgnoreEnd
    }

    final public function getIterator(): NodeListIterator
    {
        return new NodeListIterator($this->firstChild, function($state) {
            $this->activeIteratorCount += $state;
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

    final public function offsetExists($key): bool
    {
        return isset($this->children[$key]);
    }

    /**
     * @throws WriteOperationForbiddenException
     * @throws InvalidSubjectNodeException
     */
    final public function offsetUnset($key): void
    {
        if (isset($this->children[$key])) {
            $this->removeNode($this->children[$key]);
        }
    }

    abstract public function toArray(): array;
    abstract public function offsetGet($index): Node;
    abstract public function offsetSet($index, $value): void;
}
