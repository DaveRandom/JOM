<?php declare(strict_types=1);

namespace DaveRandom\Jom;

use DaveRandom\Jom\Exceptions\InvalidReferenceNodeException;
use DaveRandom\Jom\Exceptions\InvalidSubjectNodeException;

/** @noinspection PhpInconsistentReturnPointsInspection */

final class PointerGenerator
{
    /**
     * @var Node
     */
    private $root;

    /**
     * @var Node|null
     */
    private $rootParent;

    /**
     * @var Document
     */
    private $ownerDocument;

    /**
     * Create an array of the node's path to root in reverse order, including the root node
     *
     * @return Node[]
     */
    private function getNodePath(Node $node): array
    {
        $path = [];

        do {
            $path[] = $node;
            $node = $node->getParent();
        } while ($node !== $this->rootParent && $node !== null);

        return $path;
    }

    /**
     * @param Node[] $nodePath
     * @return string[]
     */
    private function nodePathToPointerPath(array $nodePath): array
    {
        $result = [];

        for ($i = \count($nodePath) - 1; $i >= 0; $i--) {
            $result[] = $nodePath[$i]->getKey();
        }

        return $result;
    }

    /**
     * @throws InvalidSubjectNodeException
     * @throws InvalidReferenceNodeException
     */
    private function validateAndRemoveNodePathRoots(array &$targetPath, array &$basePath = null): void
    {
        if (\array_pop($targetPath) !== $this->root) {
            throw new InvalidSubjectNodeException('Target node for pointer is not a child of the generator root node');
        }

        if ($basePath !== null && \array_pop($basePath) !== $this->root) {
            throw new InvalidReferenceNodeException('Base node for pointer is not a child of the generator root node');
        }
    }

    /**
     * @param Node|Document $root
     * @throws InvalidReferenceNodeException
     */
    public function __construct($root)
    {
        if ($root instanceof Document) {
            $root = $root->getRootNode();
        }

        if (!($root instanceof Node)) {
            throw new InvalidReferenceNodeException(
                'Pointer generator root node must be instance of ' . Node::class . ' or ' . Document::class
            );
        }

        $this->root = $root;
        $this->rootParent = $root->getParent();
        $this->ownerDocument = $root->getOwnerDocument();
    }

    public function getRootNode(): Node
    {
        return $this->root;
    }

    /**
     * @throws InvalidSubjectNodeException
     */
    public function generateAbsolutePointer(Node $target): Pointer
    {
        try {
            $targetPath = self::getNodePath($target);

            $this->validateAndRemoveNodePathRoots($targetPath);

            return Pointer::createFromParameters($this->nodePathToPointerPath($targetPath));
        } catch (InvalidSubjectNodeException $e) {
            throw $e;
        //@codeCoverageIgnoreStart
        } catch (\Exception $e) {
            throw unexpected($e);
        }
        //@codeCoverageIgnoreEnd
    }

    /**
     * @throws InvalidSubjectNodeException
     * @throws InvalidReferenceNodeException
     */
    public function generateRelativePointer(Node $target, Node $base): Pointer
    {
        $targetPath = self::getNodePath($target);
        $basePath = self::getNodePath($base);

        $this->validateAndRemoveNodePathRoots($targetPath, $basePath);

        while (!empty($targetPath) && \end($targetPath) === \end($basePath)) {
            \array_pop($targetPath);
            \array_pop($basePath);
        }

        try {
            return Pointer::createFromParameters($this->nodePathToPointerPath($targetPath), \count($basePath));
        //@codeCoverageIgnoreStart
        } catch (\Exception $e) {
            throw unexpected($e);
        }
        //@codeCoverageIgnoreEnd
    }
}
