<?php declare(strict_types=1);

namespace DaveRandom\Jom;

use DaveRandom\Jom\Exceptions\InvalidNodeValueException;
use DaveRandom\Jom\Exceptions\InvalidOperationException;
use DaveRandom\Jom\Exceptions\InvalidPointerException;
use DaveRandom\Jom\Exceptions\InvalidSubjectNodeException;
use DaveRandom\Jom\Exceptions\ParseFailureException;
use DaveRandom\Jom\Exceptions\PointerEvaluationFailureException;
use ExceptionalJSON\DecodeErrorException;

final class Document implements \JsonSerializable
{
    /** @var Node */
    private $rootNode;

    /**
     * @throws InvalidNodeValueException
     * @throws InvalidOperationException
     */
    private static function createArrayNodeFromParsedValue(Document $doc, array $values): ArrayNode
    {
        $node = new ArrayNode($doc);

        foreach ($values as $value) {
            $node->push(self::createNodeFromParsedValue($doc, $value));
        }

        return $node;
    }

    /**
     * @throws InvalidNodeValueException
     * @throws InvalidOperationException
     */
    private static function createObjectNodeFromParsedValue(Document $doc, object $values): ObjectNode
    {
        $node = new ObjectNode($doc);

        foreach ($values as $key => $value) {
            $node->setProperty($key, self::createNodeFromParsedValue($doc, $value));
        }

        return $node;
    }

    /**
     * @throws InvalidNodeValueException
     * @throws InvalidOperationException
     */
    private static function createNodeFromParsedValue(Document $doc, $value): Node
    {
        switch (\gettype($value)) {
            case 'NULL': return new NullNode($doc);
            case 'boolean': return new BooleanNode($doc, $value);
            case 'integer': case 'double': return new NumberNode($doc, $value);
            case 'string': return new StringNode($doc, $value);
            case 'object': return self::createObjectNodeFromParsedValue($doc, $value);
            case 'array': return self::createArrayNodeFromParsedValue($doc, $value);
        }

        throw new InvalidNodeValueException("Failed to create node from value of type '" . \gettype($value) . "'");
    }

    /**
     * @throws PointerEvaluationFailureException
     */
    private function evaluatePointerPath(Pointer $pointer, Node $current): Node
    {
        foreach ($pointer->getPath() as $component) {
            if (!($current instanceof VectorNode)) {
                throw new PointerEvaluationFailureException(
                    "Pointer '{$pointer}' does not indicate a valid path in the document"
                );
            }

            if (!$current->offsetExists($component)) {
                throw new PointerEvaluationFailureException("The referenced property or index '{$component}' does not exist");
            }

            $current = $current->offsetGet($component);
        }

        return $current;
    }

    /**
     * @throws PointerEvaluationFailureException
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
                throw new PointerEvaluationFailureException(
                    "Pointer '{$pointer}' does not indicate a valid path in the document relative to the supplied node"
                );
            }
        }

        return $this->evaluatePointerPath($pointer, $current);
    }

    /**
     * @throws ParseFailureException
     */
    public static function parse(string $json, int $depthLimit = 512, int $options = 0): Document
    {
        try {
            $data = \ExceptionalJSON\decode($json, false, $depthLimit, $options & ~\JSON_OBJECT_AS_ARRAY);

            $doc = new self();
            $doc->rootNode = self::createNodeFromParsedValue($doc, $data);

            return $doc;
        } catch (DecodeErrorException $e) {
            throw new ParseFailureException("Decoding JSON string failed: {$e->getMessage()}", $e);
        } catch (InvalidNodeValueException $e) {
            throw new ParseFailureException("Creating document tree failed: {$e->getMessage()}", $e);
        } catch (\Throwable $e) {
            throw new ParseFailureException("Unexpected error: {$e->getMessage()}", $e);
        }
    }

    public function getRootNode(): ?Node
    {
        return $this->rootNode;
    }

    /**
     * @param Pointer|string $pointer
     * @return Node|int|string
     * @throws InvalidPointerException
     * @throws PointerEvaluationFailureException
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
