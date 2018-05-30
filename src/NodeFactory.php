<?php declare(strict_types=1);

namespace DaveRandom\Jom;

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
     * @throws InvalidNodeValueException
     */
    final protected function createArrayNodeFromPackedArray(array $values, ?Document $doc): ArrayNode
    {
        try {
            $node = new ArrayNode([], $doc);

            foreach ($values as $value) {
                $node->push($this->createNodeFromValue($value, $doc));
            }

            return $node;
        } catch (InvalidNodeValueException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new \Error('Unexpected ' . \get_class($e) . ": {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * @throws InvalidNodeValueException
     */
    final protected function createObjectNodeFromPropertyMap($properties, ?Document $doc): ObjectNode
    {
        try {
            $node = new ObjectNode([], $doc);

            foreach ($properties as $name => $value) {
                $node->setProperty($name, $this->createNodeFromValue($value, $doc));
            }

            return $node;
        } catch (InvalidNodeValueException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new \Error('Unexpected ' . \get_class($e) . ": {$e->getMessage()}", 0, $e);
        }
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
     * @throws InvalidNodeValueException
     */
    abstract public function createNodeFromValue($value, ?Document $doc = null): Node;
}
