<?php declare(strict_types=1);

namespace DaveRandom\Jom;

use DaveRandom\Jom\Exceptions\InvalidNodeValueException;
use DaveRandom\Jom\Exceptions\InvalidSubjectNodeException;
use DaveRandom\Jom\Exceptions\WriteOperationForbiddenException;

final class UnsafeNodeFactory extends NodeFactory
{
    /**
     * @throws WriteOperationForbiddenException
     * @throws InvalidNodeValueException
     * @throws InvalidSubjectNodeException
     */
    private function createObjectNodeFromPropertyArray(Document $doc, array $properties): ObjectNode
    {
        $node = new ObjectNode($doc);

        foreach ($properties as $name => $value) {
            $node->setProperty($name, $this->createNodeFromValue($doc, $value));
        }

        return $node;
    }

    /**
     * @throws WriteOperationForbiddenException
     * @throws InvalidNodeValueException
     * @throws InvalidSubjectNodeException
     */
    private function createVectorNodeFromArray(Document $doc, array $values): VectorNode
    {
        $i = 0;
        $packed = true;

        foreach ($values as $key => $value) {
            if ($key !== $i++) {
                $packed = false;
                break;
            }
        }

        return $packed
            ? $this->createArrayNodeFromPackedArray($doc, $values)
            : $this->createObjectNodeFromPropertyArray($doc, $values);
    }

    /**
     * @throws WriteOperationForbiddenException
     * @throws InvalidNodeValueException
     * @throws InvalidSubjectNodeException
     */
    private function createNodeFromObject(Document $doc, object $object): Node
    {
        if ($object instanceof \stdClass) {
            return $this->createObjectNodeFromStdClass($doc, $object);
        }

        if ($object instanceof \JsonSerializable) {
            return $this->createNodeFromValue($doc, $object->jsonSerialize());
        }

        return $this->createObjectNodeFromPropertyArray($doc, \get_object_vars($object));
    }

    /**
     * @inheritdoc
     */
    public function createNodeFromValue(Document $doc, $value): Node
    {
        if (null !== $node = $this->createScalarOrNullNodeFromValue($doc, $value)) {
            return $node;
        }

        if (\is_object($value)) {
            return $this->createNodeFromObject($doc, $value);
        }

        if (\is_array($value)) {
            return $this->createVectorNodeFromArray($doc, $value);
        }

        throw new InvalidNodeValueException("Failed to create node from value of type '" . \gettype($value) . "'");
    }
}
