<?php declare(strict_types=1);

namespace DaveRandom\Jom;

use DaveRandom\Jom\Exceptions\Exception;
use DaveRandom\Jom\Exceptions\InvalidNodeValueException;

abstract class NodeFactory
{
    private const ENABLE_INVALID_VALUE_IGNORE = 0x8000;
    private const IGNORE_INVALID_VALUES = Node::IGNORE_INVALID_VALUES | self::ENABLE_INVALID_VALUE_IGNORE;

    /**
     * @throws InvalidNodeValueException
     */
    private function throwInvalidValue($value): void
    {
        /** @noinspection PhpInternalEntityUsedInspection */
        throw new InvalidNodeValueException(\sprintf(
            "Failed to create node from value of type '%s'",
            describe($value)
        ));
    }

    /**
     * @uses createNullNode
     */
    private function createNullNode(?Document $ownerDoc): NullNode
    {
        return new NullNode($ownerDoc);
    }

    /**
     * @uses createNodeFromScalarValue
     */
    private function createNodeFromScalarValue(?Document $ownerDoc, $value): Node
    {
        $className = [
            'boolean' => BooleanNode::class,
            'integer' => NumberNode::class,
            'double' => NumberNode::class,
            'string' => StringNode::class,
        ][\gettype($value)];

        return new $className($value, $ownerDoc);
    }

    /**
     * @throws InvalidNodeValueException
     * @throws Exception
     */
    final protected function tryCreateNodeFromValue($value, ?Document $ownerDoc, int $flags): ?Node
    {
        $type = \gettype($value);

        $factory = [
            'NULL'    => 'createNullNode',
            'boolean' => 'createNodeFromScalarValue',
            'integer' => 'createNodeFromScalarValue',
            'double'  => 'createNodeFromScalarValue',
            'string'  => 'createNodeFromScalarValue',
            'array'   => 'createNodeFromArrayValue',
            'object'  => 'createNodeFromObjectValue',
        ][$type] ?? null;

        if ($factory !== null) {
            $node = $this->{$factory}($ownerDoc, $value, $flags);
        }

        if (isset($node)) {
            return $node;
        }

        if (!($flags & Node::IGNORE_INVALID_VALUES)) {
            $this->throwInvalidValue($value);
        }

        return null;
    }

    /**
     * @throws InvalidNodeValueException
     * @throws Exception
     */
    final protected function createArrayNodeFromPackedArray(array $values, ?Document $ownerDoc, int $flags): ArrayNode
    {
        $node = new ArrayNode([], $ownerDoc);

        foreach ($values as $value) {
            if (null !== $valueNode = $this->tryCreateNodeFromValue($value, $ownerDoc, $flags)) {
                $node->push($valueNode);
            }
        }

        return $node;
    }

    /**
     * @throws InvalidNodeValueException
     * @throws Exception
     */
    final protected function createObjectNodeFromPropertyMap($properties, ?Document $ownerDoc, int $flags): ObjectNode
    {
        $node = new ObjectNode([], $ownerDoc);

        foreach ($properties as $name => $value) {
            if (null !== $valueNode = $this->tryCreateNodeFromValue($value, $ownerDoc, $flags)) {
                $node->setProperty($name, $valueNode);
            }
        }

        return $node;
    }

    /**
     * @throws InvalidNodeValueException
     * @throws Exception
     */
    abstract protected function createNodeFromArrayValue(?Document $ownerDoc, array $array, int $flags): VectorNode;

    /**
     * @throws InvalidNodeValueException
     * @throws Exception
     */
    abstract protected function createNodeFromObjectValue(?Document $ownerDoc, object $object, int $flags): ?Node;

    /**
     * @throws InvalidNodeValueException
     * @throws Exception
     */
    final public function createNodeFromValue($value, ?Document $ownerDoc, int $flags): Node
    {
        $node = $this->tryCreateNodeFromValue($value, $ownerDoc, $flags);

        if ($node === null) {
            $this->throwInvalidValue($value);
        }

        return $node;
    }
}
