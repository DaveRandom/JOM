<?php declare(strict_types=1);

namespace DaveRandom\Jom;

use DaveRandom\Jom\Exceptions\EmptySubjectNodeListException;
use DaveRandom\Jom\Exceptions\InvalidKeyException;
use DaveRandom\Jom\Exceptions\InvalidReferenceNodeException;
use DaveRandom\Jom\Exceptions\WriteOperationForbiddenException;
use DaveRandom\Jom\Exceptions\InvalidSubjectNodeException;

final class ArrayNode extends VectorNode
{
    private function incrementKeys(?Node $current, int $amount = 1): void
    {
        while ($current !== null) {
            $current->key += $amount;
            $this->children[$current->key] = $current;
            $current = $current->nextSibling;
        }
    }

    private function decrementKeys(?Node $current): void
    {
        while ($current !== null) {
            unset($this->children[$current->key]);
            $this->children[--$current->key] = $current;
            $current = $current->nextSibling;
        }
    }

    private function normalizeIndex($index): int
    {
        if (!\is_int($index) && !\ctype_digit($index)) {
            throw new \TypeError('Index must be an integer');
        }

        return (int)$index;
    }

    /**
     * @throws EmptySubjectNodeListException
     * @throws InvalidKeyException
     * @throws InvalidReferenceNodeException
     * @throws InvalidSubjectNodeException
     * @throws WriteOperationForbiddenException
     */
    private function setNodeAtIndex($index, Node $node): void
    {
        if ($index === null || $this->normalizeIndex($index) === \count($this->children)) {
            $this->push($node);
            return;
        }

        if (isset($this->children[$index])) {
            $this->replaceNode($node, $this->children[$index]);
            return;
        }

        throw new InvalidKeyException("Index '{$index}' is outside the bounds of the array");
    }

    /**
     * @throws EmptySubjectNodeListException
     */
    private function assertNodeListNotEmpty(array $nodes): void
    {
        if (empty($nodes)) {
            throw new EmptySubjectNodeListException("List of nodes to add must contain at least one node");
        }
    }

    /**
     * @throws InvalidSubjectNodeException
     */
    public function __construct(?array $children = [], ?Document $ownerDocument = null)
    {
        parent::__construct($ownerDocument);

        try {
            $i = 0;

            foreach ($children ?? [] as $child) {
                $this->appendNode($child, $i++);
            }
        } catch (InvalidSubjectNodeException $e) {
            throw $e;
        //@codeCoverageIgnoreStart
        } catch (\Exception $e) {
            /** @noinspection PhpInternalEntityUsedInspection */
            throw unexpected($e);
        }
        //@codeCoverageIgnoreEnd
    }

    /**
     * @throws InvalidKeyException
     */
    public function item(int $index): Node
    {
        return $this->offsetGet($index);
    }

    /**
     * @throws WriteOperationForbiddenException
     * @throws InvalidSubjectNodeException
     * @throws EmptySubjectNodeListException
     */
    public function push(Node ...$nodes): void
    {
        $this->assertNodeListNotEmpty($nodes);

        foreach ($nodes as $node) {
            $this->appendNode($node, \count($this->children));
        }
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
        } catch (WriteOperationForbiddenException $e) {
            throw $e;
        //@codeCoverageIgnoreStart
        } catch (\Exception $e) {
            /** @noinspection PhpInternalEntityUsedInspection */
            throw unexpected($e);
        }
        //@codeCoverageIgnoreEnd

        return $node;
    }

    /**
     * @throws WriteOperationForbiddenException
     * @throws InvalidSubjectNodeException
     * @throws EmptySubjectNodeListException
     */
    public function unshift(Node ...$nodes): void
    {
        $this->assertNodeListNotEmpty($nodes);

        try {
            $beforeNode = $this->firstChild;

            foreach ($nodes as $key => $node) {
                $this->insertNode($node, $key, $beforeNode);
            }

            $this->incrementKeys($beforeNode, \count($nodes));
        } catch (WriteOperationForbiddenException | InvalidSubjectNodeException $e) {
            throw $e;
        //@codeCoverageIgnoreStart
        } catch (\Exception $e) {
            /** @noinspection PhpInternalEntityUsedInspection */
            throw unexpected($e);
        }
        //@codeCoverageIgnoreEnd
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
        } catch (WriteOperationForbiddenException $e) {
            throw $e;
        //@codeCoverageIgnoreStart
        } catch (\Exception $e) {
            /** @noinspection PhpInternalEntityUsedInspection */
            throw unexpected($e);
        }
        //@codeCoverageIgnoreEnd

        return $node;
    }

    /**
     * @throws WriteOperationForbiddenException
     * @throws InvalidSubjectNodeException
     * @throws InvalidReferenceNodeException
     */
    public function insert(Node $node, ?Node $beforeNode): void
    {
        if ($beforeNode === null) {
            $this->appendNode($node, \count($this->children));
            return;
        }

        $key = $beforeNode !== null
            ? $beforeNode->key
            : \count($this->children);

        $this->insertNode($node, $key, $beforeNode);

        $this->incrementKeys($beforeNode);
    }

    /**
     * @param Node|int $nodeOrIndex
     * @throws InvalidKeyException
     * @throws InvalidReferenceNodeException
     * @throws InvalidSubjectNodeException
     * @throws WriteOperationForbiddenException
     */
    public function replace(Node $newNode, $nodeOrIndex): void
    {
        $this->replaceNode($newNode, $this->resolveNode($nodeOrIndex));
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
        $index = $this->normalizeIndex($index);

        if (!isset($this->children[$index])) {
            throw new InvalidKeyException("Index '{$index}' is outside the bounds of the array");
        }

        return $this->children[$index];
    }

    /**
     * @throws WriteOperationForbiddenException
     * @throws InvalidSubjectNodeException
     * @throws InvalidKeyException
     */
    public function offsetSet($index, $value): void
    {
        try {
            $this->setNodeAtIndex($index, $value);
        } catch (WriteOperationForbiddenException | InvalidSubjectNodeException | InvalidKeyException $e) {
            throw $e;
        //@codeCoverageIgnoreStart
        } catch (\Exception $e) {
            /** @noinspection PhpInternalEntityUsedInspection */
            throw unexpected($e);
        }
        //@codeCoverageIgnoreEnd
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
