<?php declare(strict_types=1);

namespace DaveRandom\Jom;

use DaveRandom\Jom\Exceptions\InvalidNodeValueException;
use DaveRandom\Jom\Exceptions\InvalidPointerException;
use DaveRandom\Jom\Exceptions\InvalidSubjectNodeException;

abstract class Node implements \JsonSerializable
{
    protected $ownerDocument;

    /** @var string|int|null */
    protected $key;

    /** @var Node|null */
    protected $parent;

    /** @var Node|null */
    protected $previousSibling;

    /** @var Node|null */
    protected $nextSibling;

    /**
     * @return Node[]
     */
    private static function getNodePath(Node $node): array
    {
        $path = [$node];

        while (null !== $node = $node->parent) {
            $path[] = $node;
        }

        return $path;
    }

    /**
     * @throws InvalidPointerException
     */
    private function getAbsolutePointer(): Pointer
    {
        $current = $this;
        $components = [];

        while ($current->key !== null) {
            $components[] = $current->key;
            $current = $current->parent;
        }

        return new Pointer(\array_reverse($components), null, false);
    }

    /**
     * @throws InvalidSubjectNodeException
     * @throws InvalidPointerException
     */
    private function getRelativePointer(Node $base): Pointer
    {
        if ($base->ownerDocument !== $this->ownerDocument) {
            throw new InvalidSubjectNodeException('Base node belongs to a different document');
        }

        $thisPath = self::getNodePath($this);
        $basePath = self::getNodePath($base);

        // Find the nearest common ancestor
        while (\end($thisPath) === \end($basePath)) {
            \array_pop($thisPath);
            \array_pop($basePath);
        }

        $path = [];

        for ($i = \count($thisPath) - 1; $i >= 0; $i--) {
            $path[] = $thisPath[$i]->key;
        }

        return new Pointer($path, \count($basePath), false);
    }

    /**
     * @throws InvalidNodeValueException
     * @return static
     */
    public static function createFromValue($value, ?Document $ownerDocument = null): Node
    {
        static $nodeFactory;

        try {
            $result = ($nodeFactory ?? $nodeFactory = new UnsafeNodeFactory)
                ->createNodeFromValue($value, $ownerDocument);

            if (!($result instanceof static)) {
                throw new InvalidNodeValueException(\sprintf(
                    "Value of type %s parsed as %s, %s expected",
                    \gettype($value),
                    \get_class($result),
                    static::class
                ));
            }

            return $result;
        } catch (InvalidNodeValueException $e) {
            throw $e;
        //@codeCoverageIgnoreStart
        } catch (\Exception $e) {
            throw new \Error('Unexpected ' . \get_class($e) . ": {$e->getMessage()}", 0, $e);
        }
        //@codeCoverageIgnoreEnd
    }

    protected function __construct(?Document $ownerDocument)
    {
        $this->ownerDocument = $ownerDocument;
    }

    final protected function setReferences(?Node $parent, $key, ?Node $previousSibling, ?Node $nextSibling): void
    {
        $this->parent = $parent;
        $this->key = $key;
        $this->previousSibling = $previousSibling;
        $this->nextSibling = $nextSibling;
    }

    final public function getParent(): ?Node
    {
        return $this->parent;
    }

    final public function getPreviousSibling(): ?Node
    {
        return $this->previousSibling;
    }

    final public function getNextSibling(): ?Node
    {
        return $this->nextSibling;
    }

    public function hasChildren(): bool
    {
        return false;
    }

    public function getFirstChild(): ?Node
    {
        return null;
    }

    public function getLastChild(): ?Node
    {
        return null;
    }

    final public function getOwnerDocument(): ?Document
    {
        return $this->ownerDocument;
    }

    /**
     * @return string|int|null
     */
    final public function getKey()
    {
        return $this->key;
    }

    /**
     * @throws InvalidPointerException
     * @throws InvalidSubjectNodeException
     */
    final public function getPointer(Node $base = null): Pointer
    {
        return $base === null
            ? $this->getAbsolutePointer()
            : $this->getRelativePointer($base);
    }

    abstract public function getValue();
    abstract public function jsonSerialize();
}
