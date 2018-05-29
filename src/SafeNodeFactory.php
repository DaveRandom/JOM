<?php declare(strict_types=1);

namespace DaveRandom\Jom;

use DaveRandom\Jom\Exceptions\InvalidNodeValueException;

final class SafeNodeFactory extends NodeFactory
{
    /**
     * @inheritdoc
     */
    public function createNodeFromValue(Document $doc, $value): Node
    {
        if (null !== $node = $this->createScalarOrNullNodeFromValue($doc, $value)) {
            return $node;
        }

        if ($value instanceof \stdClass) {
            return $this->createObjectNodeFromStdClass($doc, $value);
        }

        if (\is_array($value)) {
            return $this->createArrayNodeFromPackedArray($doc, $value);
        }

        throw new InvalidNodeValueException("Failed to create node from value of type '" . \gettype($value) . "'");
    }
}
