<?php declare(strict_types=1);

namespace DaveRandom\Jom;

use DaveRandom\Jom\Exceptions\DocumentTreeCreationFailedException;
use DaveRandom\Jom\Exceptions\InvalidNodeValueException;
use DaveRandom\Jom\Exceptions\InvalidPointerException;
use DaveRandom\Jom\Exceptions\InvalidSubjectNodeException;
use DaveRandom\Jom\Exceptions\ParseFailureException;
use DaveRandom\Jom\Exceptions\PointerReferenceNotFoundException;
use DaveRandom\Jom\Exceptions\WriteOperationForbiddenException;
use ExceptionalJSON\DecodeErrorException;

final class Document implements \JsonSerializable
{
    /** @var Node */
    private $rootNode;

    private static function getSafeNodeFactory(): SafeNodeFactory
    {
        static $factory;
        return $factory ?? $factory = new SafeNodeFactory();
    }

    private static function getUnsafeNodeFactory(): UnsafeNodeFactory
    {
        static $factory;
        return $factory ?? $factory = new UnsafeNodeFactory();
    }

    /**
     * @throws PointerReferenceNotFoundException
     */
    private function evaluatePointerPath(Pointer $pointer, Node $current): Node
    {
        foreach ($pointer->getPath() as $component) {
            if (!($current instanceof VectorNode)) {
                throw new PointerReferenceNotFoundException(
                    "Pointer '{$pointer}' does not indicate a valid path in the document"
                );
            }

            if (!$current->offsetExists($component)) {
                throw new PointerReferenceNotFoundException("The referenced property or index '{$component}' does not exist");
            }

            $current = $current->offsetGet($component);
        }

        return $current;
    }

    /**
     * @throws PointerReferenceNotFoundException
     * @throws InvalidSubjectNodeException
     */
    private function evaluateRelativePointer(Pointer $pointer, Node $current): Node
    {
        if ($current->getOwnerDocument() !== $this) {
            throw new InvalidSubjectNodeException('Base node belongs to a different document');
        }

        for ($i = $pointer->getRelativeLevels(); $i > 0; $i--) {
            $current = $current->getParent();

            if ($current === null) {
                throw new PointerReferenceNotFoundException(
                    "Pointer '{$pointer}' does not indicate a valid path in the document relative to the supplied node"
                );
            }
        }

        return $this->evaluatePointerPath($pointer, $current);
    }

    /**
     * @throws InvalidNodeValueException
     * @throws InvalidSubjectNodeException
     * @throws WriteOperationForbiddenException
     */
    private function importVectorNode(VectorNode $node): VectorNode
    {
        if (!($node instanceof ArrayNode || $node instanceof ObjectNode)) {
            throw new InvalidSubjectNodeException('Source node is of unknown type ' . \get_class($node));
        }

        $newNode = new $node($this);

        foreach ($node as $key => $value) {
            $newNode[$key] = $this->import($value);
        }

        return $newNode;
    }

    /**
     * @throws InvalidSubjectNodeException
     */
    private function importScalarNode(Node $node): Node
    {
        if (!($node instanceof BooleanNode || $node instanceof NumberNode || $node instanceof StringNode)) {
            throw new InvalidSubjectNodeException('Source node is of unknown type ' . \get_class($node));
        }

        return new $node($this, $node->getValue());
    }

    /**
     * @throws DocumentTreeCreationFailedException
     * @throws ParseFailureException
     */
    public static function parse(string $json, int $depthLimit = 512, int $options = 0): Document
    {
        try {
            $data = \ExceptionalJSON\decode($json, false, $depthLimit, $options & ~\JSON_OBJECT_AS_ARRAY);

            $doc = new self();
            $doc->rootNode = self::getSafeNodeFactory()->createNodeFromValue($doc, $data);

            return $doc;
        } catch (DecodeErrorException $e) {
            throw new ParseFailureException("Decoding JSON string failed: {$e->getMessage()}", $e);
        } catch (InvalidNodeValueException $e) {
            throw new DocumentTreeCreationFailedException("Creating document tree failed: {$e->getMessage()}", $e);
        } catch (\Throwable $e) {
            throw new DocumentTreeCreationFailedException("Unexpected error: {$e->getMessage()}", $e);
        }
    }

    /**
     * @throws DocumentTreeCreationFailedException
     */
    public static function createFromData($data): Document
    {
        try {
            $doc = new self();
            $doc->rootNode = self::getUnsafeNodeFactory()->createNodeFromValue($doc, $data);

            return $doc;
        } catch (InvalidNodeValueException $e) {
            throw new DocumentTreeCreationFailedException("Creating document tree failed: {$e->getMessage()}", $e);
        } catch (\Throwable $e) {
            throw new DocumentTreeCreationFailedException("Unexpected error: {$e->getMessage()}", $e);
        }
    }

    public function getRootNode(): ?Node
    {
        return $this->rootNode;
    }

    /**
     * @throws InvalidSubjectNodeException
     * @throws WriteOperationForbiddenException
     * @throws InvalidNodeValueException
     * @throws InvalidSubjectNodeException
     */
    public function import(Node $node): Node
    {
        if ($node->getOwnerDocument() === $this) {
            throw new InvalidSubjectNodeException('Cannot import tne supplied node, already owned by this document');
        }

        if ($node instanceof NullNode) {
            return new NullNode($this);
        }

        return $node instanceof VectorNode
            ? $this->importVectorNode($node)
            : $this->importScalarNode($node);
    }

    /**
     * @param Pointer|string $pointer
     * @return Node|int|string
     * @throws InvalidPointerException
     * @throws PointerReferenceNotFoundException
     * @throws InvalidSubjectNodeException
     */
    public function evaluatePointer($pointer, Node $base = null)
    {
        if (!($pointer instanceof Pointer)) {
            $pointer = Pointer::createFromString((string)$pointer);
        }

        if (!$pointer->isRelative()) {
            return $this->evaluatePointerPath($pointer, $this->rootNode);
        }

        $target = $this->evaluateRelativePointer($pointer, $base ?? $this->rootNode);

        return $pointer->isKeyLookup()
            ? $target->getKey()
            : $target;
    }

    public function jsonSerialize()
    {
        return $this->rootNode !== null
            ? $this->rootNode->jsonSerialize()
            : null;
    }
}
