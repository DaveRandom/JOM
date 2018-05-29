<?php declare(strict_types=1);

namespace DaveRandom\Jom;

use DaveRandom\Jom\Exceptions\InvalidNodeValueException;
use DaveRandom\Jom\Exceptions\InvalidSubjectNodeException;
use DaveRandom\Jom\Exceptions\WriteOperationForbiddenException;

abstract class NodeFactory
{
    private const SCALAR_VALUE_NODE_CLASSES = [
        'boolean' => BooleanNode::class,
        'integer' => NumberNode::class,
        'double' => NumberNode::class,
        'string' => StringNode::class,
    ];

    /**
     * @throws WriteOperationForbiddenException
     * @throws InvalidNodeValueException
     * @throws InvalidSubjectNodeException
     */
    final protected function createArrayNodeFromPackedArray(Document $doc, array $values): ArrayNode
    {
        $node = new ArrayNode($doc);

        foreach ($values as $value) {
            $node->push($this->createNodeFromValue($doc, $value));
        }

        return $node;
    }

    /**
     * @throws WriteOperationForbiddenException
     * @throws InvalidNodeValueException
     * @throws InvalidSubjectNodeException
     */
    final protected function createObjectNodeFromStdClass(Document $doc, \stdClass $values): ObjectNode
    {
        $node = new ObjectNode($doc);

        foreach ($values as $key => $value) {
            $node->setProperty($key, $this->createNodeFromValue($doc, $value));
        }

        return $node;
    }

    final protected function createScalarOrNullNodeFromValue(Document $doc, $value): ?Node
    {
        if ($value === null) {
            return new NullNode($doc);
        }

        $className = self::SCALAR_VALUE_NODE_CLASSES[\gettype($value)] ?? null;

        if ($className !== null) {
            return new $className($doc, $value);
        }

        return null;
    }

    /**
     * @throws WriteOperationForbiddenException
     * @throws InvalidNodeValueException
     * @throws InvalidSubjectNodeException
     */
    abstract public function createNodeFromValue(Document $doc, $value): Node;
}
