<?php declare(strict_types=1);

namespace DaveRandom\Jom;

use DaveRandom\Jom\Exceptions\Exception;
use DaveRandom\Jom\Exceptions\InvalidNodeValueException;

abstract class NodeFactory
{
    private const SCALAR_VALUE_NODE_CLASSES = [
        'boolean' => BooleanNode::class,
        'integer' => NumberNode::class,
        'double' => NumberNode::class,
        'string' => StringNode::class,
    ];

    /**
     * @throws Exception
     * @throws InvalidNodeValueException
     */
    final protected function createArrayNodeFromPackedArray(array $values, ?Document $doc, int $flags): ArrayNode
    {
        $node = new ArrayNode([], $doc);

        foreach ($values as $value) {
            if (null !== $valueNode = $this->createNodeFromValue($value, $doc, $flags)) {
                $node->push($valueNode);
            }
        }

        return $node;
    }

    /**
     * @throws Exception
     * @throws InvalidNodeValueException
     */
    final protected function createObjectNodeFromPropertyMap($properties, ?Document $doc, int $flags): ObjectNode
    {
        $node = new ObjectNode([], $doc);

        foreach ($properties as $name => $value) {
            if (null !== $valueNode = $this->createNodeFromValue($value, $doc, $flags)) {
                $node->setProperty($name, $valueNode);
            }
        }

        return $node;
    }

    final protected function createScalarOrNullNodeFromValue($value, ?Document $doc): ?Node
    {
        if ($value === null) {
            return new NullNode($doc);
        }

        $className = self::SCALAR_VALUE_NODE_CLASSES[\gettype($value)] ?? null;

        if ($className !== null) {
            return new $className($value, $doc);
        }

        return null;
    }

    /**
     * @throws Exception
     * @throws InvalidNodeValueException
     */
    abstract protected function createVectorNodeFromValue($value, ?Document $doc, int $flags): ?Node;

    /**
     * @throws InvalidNodeValueException
     */
    final public function createNodeFromValue($value, ?Document $doc, int $flags): ?Node
    {
        try {
            $node = $this->createScalarOrNullNodeFromValue($value, $doc)
                ?? $this->createVectorNodeFromValue($value, $doc, $flags);

            if ($node !== null || ($flags & Node::IGNORE_INVALID_VALUES)) {
                return $node;
            }
        } catch (InvalidNodeValueException $e) {
            throw $e;
        //@codeCoverageIgnoreStart
        } catch (\Exception $e) {
            throw unexpected($e);
        }
        //@codeCoverageIgnoreEnd

        throw new InvalidNodeValueException("Failed to create node from value of type '" . \gettype($value) . "'");
    }
}
