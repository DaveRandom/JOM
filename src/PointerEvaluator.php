<?php declare(strict_types=1);

namespace DaveRandom\Jom;

use DaveRandom\Jom\Exceptions\InvalidKeyException;
use DaveRandom\Jom\Exceptions\InvalidPointerException;
use DaveRandom\Jom\Exceptions\InvalidReferenceNodeException;
use DaveRandom\Jom\Exceptions\PointerReferenceNotFoundException;

final class PointerEvaluator
{
    private const NODE_TYPE_NAMES  = [
        BooleanNode::class => 'boolean',
        NumberNode::class => 'number',
        StringNode::class => 'string',
        NullNode::class => 'null',
    ];

    private $root;

    /**
     * @throws PointerReferenceNotFoundException
     */
    private function getValidArrayIndex(string $index, Pointer $pointer, int $level): int
    {
        if (\preg_match('/^(?:0|[1-9][0-9]*)$/', $index)) {
            return (int)$index;
        }

        throw new PointerReferenceNotFoundException(
            "Array member must be referenced by integer index without leading zero", $pointer, $level
        );
    }

    /**
     * @throws PointerReferenceNotFoundException
     */
    private function getArrayIndexFromPathComponent(Pointer $pointer, ArrayNode $node, string $index, int $level)
    {
        try {
            return $node->item($this->getValidArrayIndex($index, $pointer, $level));
        } catch (InvalidKeyException $e) {
            throw new PointerReferenceNotFoundException(
                "The referenced index does not exist", $pointer, $level, $e
            );
        }
    }

    /**
     * @throws PointerReferenceNotFoundException
     */
    private function getObjectPropertyFromPathComponent(Pointer $pointer, ObjectNode $node, string $name, int $level)
    {
        try {
            return $node->getProperty($name);
        } catch (InvalidKeyException $e) {
            throw new PointerReferenceNotFoundException(
                'The referenced property does not exist', $pointer, $level, $e
            );
        }
    }

    /**
     * @throws PointerReferenceNotFoundException
     */
    private function evaluatePointerPath(Pointer $pointer, Node $current): Node
    {
        foreach ($pointer->getPath() as $level => $component) {
            if ($current instanceof ObjectNode) {
                $current = $this->getObjectPropertyFromPathComponent($pointer, $current, $component, $level);
                continue;
            }

            if ($current instanceof ArrayNode) {
                $current = $this->getArrayIndexFromPathComponent($pointer, $current, $component, $level);
                continue;
            }

            $typeName = self::NODE_TYPE_NAMES[\get_class($current)] ?? 'unknown type';

            throw new PointerReferenceNotFoundException(
                "Expecting object or array, got {$typeName}", $pointer, $level
            );
        }

        return $current;
    }

    /**
     * @throws InvalidReferenceNodeException
     */
    private function validateRelativePointerContextNode(Pointer $pointer, Node $context): void
    {
        if ($context->getOwnerDocument() !== $this->root->getOwnerDocument()) {
            throw new InvalidReferenceNodeException(
                'Context node for relative pointer evaluation does not belong to the same document'
                . ' as the evaluator root node'
            );
        }

        if ($pointer->getRelativeLevels() > 0 && !$this->root->containsChild($context)) {
            throw new InvalidReferenceNodeException(
                'Context node for relative pointer evaluation is not a child of the root node'
            );
        }
    }

    /**
     * @throws PointerReferenceNotFoundException
     * @throws InvalidReferenceNodeException
     */
    private function evaluateRelativePointer(Pointer $pointer, Node $current): Node
    {
        if ($current !== $this->root) {
            $this->validateRelativePointerContextNode($pointer, $current);
        }

        for ($i = 0, $levels = $pointer->getRelativeLevels(); $i < $levels; $i++) {
            if (($current ?? $this->root) === $this->root) {
                throw new PointerReferenceNotFoundException(
                    "Relative pointer prefix overflows context node nesting level {$i}", $pointer
                );
            }

            $current = $current->getParent();
        }

        return $this->evaluatePointerPath($pointer, $current);
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
                'Pointer evaluator root node must be instance of ' . Node::class . ' or ' . Document::class
            );
        }

        $this->root = $root;
    }

    public function getRootNode(): Node
    {
        return $this->root;
    }

    /**
     * @param Pointer|string $pointer
     * @return Node|int|string
     * @throws InvalidPointerException
     * @throws InvalidReferenceNodeException
     * @throws PointerReferenceNotFoundException
     */
    public function evaluatePointer($pointer, ?Node $context = null)
    {
        if (!($pointer instanceof Pointer)) {
            $pointer = Pointer::createFromString((string)$pointer);
        }

        if (!$pointer->isRelative()) {
            return $this->evaluatePointerPath($pointer, $this->root);
        }

        $target = $this->evaluateRelativePointer($pointer, $context ?? $this->root);

        return $pointer->isKeyLookup()
            ? $target->getKey()
            : $target;
    }
}
