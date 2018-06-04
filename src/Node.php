<?php declare(strict_types=1);

namespace DaveRandom\Jom;

use DaveRandom\Jom\Exceptions\InvalidNodeValueException;

abstract class Node implements \JsonSerializable
{
    public const IGNORE_INVALID_VALUES = 0b01;
    public const PERMIT_INCORRECT_REFERENCE_TYPE = 0b10;

    protected $ownerDocument;

    /** @var string|int|null */
    protected $key;

    /** @var VectorNode|null */
    protected $parent;

    /** @var Node|null */
    protected $previousSibling;

    /** @var Node|null */
    protected $nextSibling;

    /**
     * @throws InvalidNodeValueException
     * @return static
     */
    public static function createFromValue($value, ?Document $ownerDocument = null, ?int $flags = 0): Node
    {
        static $nodeFactory;

        $flags = $flags ?? 0;

        try {
            $result = ($nodeFactory ?? $nodeFactory = new UnsafeNodeFactory)
                ->createNodeFromValue($value, $ownerDocument, $flags);

            // Always throw if root node could not be created
            if ($result === null) {
                throw new InvalidNodeValueException(\sprintf(
                    "Failed to create node from value of type '%s'",
                    \gettype($value)
                ));
            }

            // Check that the created node matches the class used to call the method
            if (!($result instanceof static) && !($flags & self::PERMIT_INCORRECT_REFERENCE_TYPE)) {
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
            throw unexpected($e);
        }
        //@codeCoverageIgnoreEnd
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

    public function containsChild(/** @noinspection PhpUnusedParameterInspection */ Node $child): bool
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

    abstract public function getValue();
    abstract public function jsonSerialize();
}
