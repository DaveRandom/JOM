<?php declare(strict_types=1);

namespace DaveRandom\Jom;

use DaveRandom\Jom\Exceptions\InvalidNodeValueException;
use DaveRandom\Jom\Exceptions\InvalidReferenceNodeException;

abstract class Node implements \JsonSerializable, Taggable
{
    use TagData;

    public const IGNORE_INVALID_VALUES = 0b01;
    public const PERMIT_INCORRECT_REFERENCE_TYPE = 0b10;

    /** @var NodeFactory */
    private static $nodeFactory;

    /** @var Document|null */
    protected $ownerDocument;

    /** @var string|int|null */
    protected $key;

    /** @var VectorNode|null */
    protected $parent;

    /** @var Node|null */
    protected $previousSibling;

    /** @var Node|null */
    protected $nextSibling;

    /** @uses __init() */
    private static function __init(): void
    {
        self::$nodeFactory = new UnsafeNodeFactory();
    }

    /**
     * @param mixed $value
     * @throws InvalidNodeValueException
     */
    private static function validateCreatedNodeType(Node $node, string $expectedType, $value, ?int $flags): Node
    {
        if ($node instanceof $expectedType || ($flags & self::PERMIT_INCORRECT_REFERENCE_TYPE)) {
            return $node;
        }

        throw new InvalidNodeValueException(\sprintf(
            "Value of type %s parsed as instance of %s, instance of %s expected",
            describe($value),
            \get_class($node),
            $expectedType
        ));
    }

    /**
     * @param bool|int|float|string|array|object|null A value that can be encoded as JSON
     * @throws InvalidNodeValueException
     * @return static
     */
    public static function createFromValue($value, ?Document $ownerDocument = null, ?int $flags = 0): Node
    {
        try {
            $result = self::$nodeFactory->createNodeFromValue($value, $ownerDocument, $flags ?? 0);
        } catch (InvalidNodeValueException $e) {
            throw $e;
        //@codeCoverageIgnoreStart
        } catch (\Exception $e) {
            throw unexpected($e);
        }
        //@codeCoverageIgnoreEnd

        return self::validateCreatedNodeType($result, static::class, $value, $flags);
    }

    protected function __construct(?Document $ownerDocument)
    {
        $this->ownerDocument = $ownerDocument;
    }

    public function __clone()
    {
        $this->setReferences(null, null, null, null);
    }

    final protected function setReferences(?VectorNode $parent, $key, ?Node $previousSibling, ?Node $nextSibling): void
    {
        $this->parent = $parent;
        $this->key = $key;
        $this->previousSibling = $previousSibling;
        $this->nextSibling = $nextSibling;
    }

    final public function getParent(): ?VectorNode
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

    public function containsChild(Node $child): bool
    {
        return $child !== $child;
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
     * @return Node[]
     * @throws InvalidReferenceNodeException
     */
    final public function getAncestors(?Node $root = null): array
    {
        $path = [$this];
        $current = $this->parent;
        $rootParent = $root !== null
            ? $root->parent
            : null;

        while ($current !== $rootParent && $current !== null) {
            $path[] = $current;
            $current = $current->parent;
        }

        if ($current !== $rootParent) {
            throw new InvalidReferenceNodeException('Path target node is not an ancestor of the subject node');
        }

        return $path;
    }

    abstract public function getValue();
    abstract public function jsonSerialize();
}

\DaveRandom\Jom\initialize(Node::class);
