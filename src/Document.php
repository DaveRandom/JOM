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

        $newNode = new $node(null, $this);

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

        try {
            return Node::createFromValue($node->getValue(), $this);
        //@codeCoverageIgnoreStart
        } catch (\Exception $e) {
            throw new \Error('Unexpected ' . \get_class($e) . ": {$e->getMessage()}", 0, $e);
        }
        //@codeCoverageIgnoreEnd
    }

    private function __construct() { }

    /**
     * @throws DocumentTreeCreationFailedException
     * @throws ParseFailureException
     */
    public static function parse(string $json, int $depthLimit = 512, int $options = 0): Document
    {
        static $nodeFactory;

        try {
            $data = \ExceptionalJSON\decode($json, false, $depthLimit, $options & ~\JSON_OBJECT_AS_ARRAY);

            $doc = new self();
            $doc->rootNode = ($nodeFactory ?? $nodeFactory = new SafeNodeFactory)
                ->createNodeFromValue($data, $doc);

            return $doc;
        } catch (DecodeErrorException $e) {
            throw new ParseFailureException("Decoding JSON string failed: {$e->getMessage()}", $e);
        } catch (InvalidNodeValueException $e) {
            throw new DocumentTreeCreationFailedException("Creating document tree failed: {$e->getMessage()}", $e);
        //@codeCoverageIgnoreStart
        } catch (\Exception $e) {
            throw new \Error('Unexpected ' . \get_class($e) . ": {$e->getMessage()}", 0, $e);
        }
        //@codeCoverageIgnoreEnd
    }

    /**
     * @throws DocumentTreeCreationFailedException
     */
    public static function createFromValue($value): Document
    {
        try {
            $doc = new self();
            $doc->rootNode = Node::createFromValue($value, $doc);

            return $doc;
        } catch (InvalidNodeValueException $e) {
            throw new DocumentTreeCreationFailedException("Creating document tree failed: {$e->getMessage()}", $e);
        //@codeCoverageIgnoreStart
        } catch (\Exception $e) {
            throw new \Error('Unexpected ' . \get_class($e) . ": {$e->getMessage()}", 0, $e);
        }
        //@codeCoverageIgnoreEnd
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
